RSS & Atom Feeds for PHP
========================

[![Downloads this Month](https://img.shields.io/packagist/dm/grunjol/rss-php.svg)](https://packagist.org/packages/grunjol/rss-php)
[![Latest Stable Version](https://poser.pugx.org/grunjol/rss-php/v/stable)](https://github.com/grunjol/rss-php/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/grunjol/rss-php/blob/master/license.md)

RSS & Atom Feeds for PHP is a very small and easy-to-use library for consuming an RSS and Atom feeds.
This project is a forked of David Grudl's rs-php https://github.com/dg/rss-php

It requires PHP 5.5 and Guzzle 6.1
and is licensed under the New BSD License. You can obtain the latest version from
our [GitHub repository](https://github.com/grunjol/rss-php/releases) or install it via Composer:

```
php composer.phar require grunjol/rss-php
```

Usage
-----

Download RSS feed from URL:

```php
$rss = Feed::loadRss($url);
```

The returned properties are SimpleXMLElement objects. Extracting
the information from the channel is easy:

```php
echo 'Title: ', $rss->title;
echo 'Description: ', $rss->description;
echo 'Link: ', $rss->link;

foreach ($rss->item as $item) {
	echo 'Title: ', $item->title;
	echo 'Link: ', $item->link;
	echo 'Timestamp: ', $item->timestamp;
	echo 'Description ', $item->description;
	echo 'HTML encoded content: ', $item->{'content:encoded'};
}
```

Download Atom feed from URL:

```php
$atom = Feed::loadAtom($url);
```
You can set your own Guzzle instance to the static client property which
```php
Feed::$client = new GuzzleHttp\Client(['headers' => ['User-Agent' => 'FeedPHP/1.0']]);
```
and it will be reused.
  
You can pass Guzzle request options (including auth user/password) on each call
```php
$atom = Feed::loadAtom($url, ['auth' => ['peter', 'secret']);
```
 
You can also enable caching using https://github.com/Kevinrob/guzzle-cache-middleware  
```php
//  Simple volatile memory cache example check docs for more options 
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;

// Create default HandlerStack
$stack = HandlerStack::create();

// Add this middleware to the top with `push`
$stack->push(new CacheMiddleware(), 'cache');

// Initialize the client with the handler option
Feed::$client = new Client(['handler' => $stack]);
```

-----
(c) David Grudl, 2008 (http://davidgrudl.com)
(c) grunjol, 2017 (https://github.com/grunjol)