<?php
namespace Grunjol\Feed;
/**
 * RSS for PHP - small and easy-to-use library for consuming an RSS Feed
 *
 * @copyright  Copyright (c) 2008 David Grudl, 2017 grunjol
 * @license    New BSD License
 * @version    1.3
 */

class Feed
{
	/** @var SimpleXMLElement */
	protected $xml;


	/**
	 * Loads RSS or Atom feed.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return Feed
	 * @throws FeedException
	 */
	public static function load($url, $user = NULL, $pass = NULL)
	{
		$xml = self::loadXml($url, $url, $pass);

		if ($xml->channel) {
			return self::fromRss($xml);
		} else {
			return self::fromAtom($xml);
		}
	}


	/**
	 * Loads RSS feed.
	 * @param  string  RSS feed URL
	 * @param  string
	 * @param  string
	 * @return Feed
	 * @throws FeedException
	 */
	public static function loadRss($url, $user = NULL, $pass = NULL)
	{
		return self::fromRss(self::loadXml($url, $user, $pass));
	}


	/**
	 * Loads Atom feed.
	 * @param  string  Atom feed URL
	 * @param  string
	 * @param  string
	 * @return Feed
	 * @throws FeedException
	 */
	public static function loadAtom($url, $user = NULL, $pass = NULL)
	{
		return self::fromAtom(self::loadXml($url, $user, $pass));
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

			// generate 'timestamp' tag
			if (isset($item->{'dc:date'})) {
				$item->timestamp = strtotime($item->{'dc:date'});
			} elseif (isset($item->pubDate)) {
				$clean_date = str_replace('Pubdate:','',$item->pubDate);
				$item->timestamp = strtotime($clean_date);
			}
			if(isset($item->abstract) && ! isset($item->description)){
				$item->description = $item->abstract;
			}
		}
		$feed = new self;
		$feed->xml = $xml->channel;
		return $feed;
	}


	private static function fromAtom(\SimpleXMLElement $xml)
	{
		if (!in_array('http://www.w3.org/2005/Atom', $xml->getDocNamespaces(), TRUE)
			&& !in_array('http://purl.org/atom/ns#', $xml->getDocNamespaces(), TRUE)
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
	public function toArray(\SimpleXMLElement $xml = NULL)
	{
		if ($xml === NULL) {
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
	private static function loadXml($url, $user, $pass)
	{
		if ($data = trim(self::httpRequest($url, $user, $pass))) {
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
	private static function httpRequest($url, $user, $pass)
	{
		if (extension_loaded('curl')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Safari/605.1.15');
			if ($user !== NULL || $pass !== NULL) {
				curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");
			}
			curl_setopt($curl, CURLOPT_HEADER, FALSE);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_ENCODING , '');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); // no echo, just return result
			
			$result = curl_exec($curl);
			return curl_errno($curl) === 0 && curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200
				? $result
				: FALSE;
		} else {
			throw new FeedException('PHP extension CURL is not loaded.');
		}
	}


	/**
	 * Generates better accessible namespaced tags.
	 * @param  SimpleXMLElement
	 * @return void
	 */
	private static function adjustNamespaces($el)
	{
		foreach ($el->getNamespaces(TRUE) as $prefix => $ns) {
			$children = $el->children($ns);
			foreach ($children as $tag => $content) {
				$el->{$prefix . ':' . $tag} = $content;
			}
		}
	}

}
