<?php
namespace Grunjol\Feed;

/**
 * RSS for PHP - small and easy-to-use library for consuming an RSS Feed
 *
 * @copyright  Copyright (c) 2008 David Grudl, 2017 grunjol
 * @license    New BSD License
 * @version    1.3
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Feed
{
    /** @var int */
    public static $client;

    /** @var SimpleXMLElement */
    protected $xml;


    /**
     * Loads RSS or Atom feed.
     * @param  string
     * @param  array  optional guzzle request options
     * @return Feed
     * @throws FeedException
     */
    public static function load($url, $options = [])
    {
        $xml = self::loadXml($url, $options);

        if ($xml->channel) {
            return self::fromRss($xml);
        } else {
            return self::fromAtom($xml);
        }
    }


    /**
     * Loads RSS feed.
     * @param  string  RSS feed URL
     * @param  array  optional guzzle request options
     * @return Feed
     * @throws FeedException
     */
    public static function loadRss($url, $options = [])
    {
        return self::fromRss(self::loadXml($url, $options));
    }


    /**
     * Loads Atom feed.
     * @param  string  Atom feed URL
     * @param  array  optional guzzle request options
     * @return Feed
     * @throws FeedException
     */
    public static function loadAtom($url, $options = [])
    {
        return self::fromAtom(self::loadXml($url, $options));
    }


    private static function fromRss(\SimpleXMLElement $xml)
    {
        if (!$xml->channel) {
            throw new FeedException('Invalid feed.');
        }

        self::adjustNamespaces($xml);

        foreach ($xml->channel->item as $item) {
            // converts namespaces to dotted tags
            self::adjustNamespaces($item);
            $pub_date = str_replace('Pubdate:', '', $item->pubDate);

            // generate 'timestamp' tag
            if (isset($item->{'dc:date'})) {
                $pub_date = $item->{'dc:date'};
            }

            //bad format try to remove PM
            if (! strtotime($pub_date)) {
                $pub_date = str_replace('PM', '', $pub_date);
            }
            $item->timestamp = strtotime($pub_date);

            if (isset($item->abstract) && ! isset($item->description)) {
                $item->description = $item->abstract;
            }
        }
        $feed = new self;
        $feed->xml = $xml->channel;
        return $feed;
    }


    private static function fromAtom(\SimpleXMLElement $xml)
    {
        if (!in_array('http://www.w3.org/2005/Atom', $xml->getDocNamespaces(), true)
            && !in_array('http://purl.org/atom/ns#', $xml->getDocNamespaces(), true)
        ) {
            throw new FeedException('Invalid feed.');
        }

        // generate 'timestamp' tag
        foreach ($xml->entry as $entry) {
            $entry->timestamp = strtotime($entry->updated);
        }
        $feed = new self;
        $feed->xml = $xml;
        return $feed;
    }


    /**
     * Returns property value. Do not call directly.
     * @param  string  tag name
     * @return SimpleXMLElement
     */
    public function __get($name)
    {
        return $this->xml->{$name};
    }


    /**
     * Sets value of a property. Do not call directly.
     * @param  string  property name
     * @param  mixed   property value
     * @return void
     */
    public function __set($name, $value)
    {
        throw new \Exception("Cannot assign to a read-only property '$name'.");
    }


    /**
     * Converts a SimpleXMLElement into an array.
     * @param  SimpleXMLElement
     * @return array
     */
    public function toArray(\SimpleXMLElement $xml = null)
    {
        if ($xml === null) {
            $xml = $this->xml;
        }

        if (!$xml->children()) {
            return (string) $xml;
        }

        $arr = array();
        foreach ($xml->children() as $tag => $child) {
            if (count($xml->$tag) === 1) {
                $arr[$tag] = $this->toArray($child);
            } else {
                $arr[$tag][] = $this->toArray($child);
            }
        }

        return $arr;
    }


    /**
     * Load XML from cache or HTTP.
     * @param  string
     * @param  array
     * @return SimpleXMLElement
     * @throws FeedException
     */
    private static function loadXml($url, $options)
    {
        if ($data = trim(self::httpRequest($url, $options))) {
            return new \SimpleXMLElement($data, LIBXML_NOWARNING | LIBXML_NOERROR);
        }

        throw new FeedException('Cannot load feed.');
    }


    /**
     * Process HTTP request.
     * @param  string
     * @param  array
     * @return string|FALSE
     * @throws FeedException
     */
    private static function httpRequest($url, $options)
    {
        $client = static::$client ? static::$client : new Client();
        $requestOptions = [];
        $requestOptions['verify'] = __DIR__ . '/cacert.pem';
        $result = null;
        $requestOptions = array_merge($requestOptions, $options);
        try {
            $response = $client->request('GET', $url, $requestOptions);
            $result = $response->getBody()->getContents();
        } catch (ClientException $e) {
            $result = false;
        }

        return $result;
    }


    /**
     * Generates better accessible namespaced tags.
     * @param  SimpleXMLElement
     * @return void
     */
    private static function adjustNamespaces($el)
    {
        foreach ($el->getNamespaces(true) as $prefix => $ns) {
            $children = $el->children($ns);
            foreach ($children as $tag => $content) {
                $el->{$prefix . ':' . $tag} = $content;
            }
        }
    }
}
