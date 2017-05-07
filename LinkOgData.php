<?php

namespace Statamic\Addons\LinkOgData;

use Statamic\Extend\Addon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;

class LinkOgData extends Addon
{
    public function getOgData($url) {
	// Remove all illegal characters from a url
	$url = filter_var($url, FILTER_SANITIZE_URL);

	if (!filter_var($url, FILTER_VALIDATE_URL))
	{
            return [];
	}

        // create http client instance
        $client = new Client();

        // Make an async request
        $request = new Request('GET', $url);
        $result = [
            'og' => [],
            'twitter' => [],
        ];

        $response = $client->send($request);

        $crawler = new Crawler(mb_convert_encoding($response->getBody()->getContents(), 'HTML-ENTITIES', "UTF-8"));

	// Title
        $result['title'] = $crawler->filter('title')->text();

	// Other meta tags including description, keywords and author
        $meta = $crawler->filter('meta[name="description"], meta[name="keywords"], meta[name="author"]');
        foreach ($meta as $i => $tag) {
            $result[$tag->getAttribute('name')] = $tag->getAttribute('content');
        }

	// OG Tags
        $og_tags = $crawler->filter('meta[property^="og"]');
        foreach ($og_tags as $i => $tag) {
            // extract the values needed
            // get the name of the property, removing `og:` and replacing any remaining `:` with `_`
            $property = preg_replace('/:/', '_', preg_replace('/^og:/', '', $tag->getAttribute('property')));
            // get the value of the property
            $value = $tag->getAttribute('content');
            // add the property to the array
            $result['og'][$property] = $value;
        }

	// Twitter Tags
        $twitter_tags = $crawler->filter('meta[name^="twitter"]');
        foreach ($twitter_tags as $i => $tag) {
            // extract the values needed
            // get the name of the property, removing `og:` and replacing any remaining `:` with `_`
            $property = preg_replace('/:/', '_', preg_replace('/^twitter:/', '', $tag->getAttribute('name')));
            // get the value of the property
            $value = $tag->getAttribute('content');
            // add the property to the array
            $result['twitter'][$property] = $value;
        }

        return $result;
    }
}