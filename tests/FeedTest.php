<?php

use PHPUnit\Framework\TestCase;
use Grunjol\Feed\Feed;
use Grunjol\Feed\FeedException;

final class FeedTest extends TestCase
{

    public $rssUrl = 'https://www.floridaphoenix.com/feed/';
    public $atomUrl = 'https://www.miamiherald.com/?widgetName=rssfeed&widgetContentId=6199&getXmlFeed=true';
    public $dcDateUrl = 'https://gist.githubusercontent.com/ayukawa/c5975851112c54fb536b/raw/72d2c81761a225cd0cb1fb8cb34b3898b5d34297/FeedTest.xml';
    public $noFeedUrl = 'https://github.com/dg/rss-php';
    public $authRssUrl = 'http://peter279k.com/auth-rss/';

    public function testLoad()
    {
        $rss = Feed::load($this->rssUrl);

        $this->assertInstanceOf('\Grunjol\Feed\Feed', $rss);

        $rss = Feed::load($this->atomUrl);

        $this->assertInstanceOf('\Grunjol\Feed\Feed', $rss);
    }

    public function testInvalidRss()
    {
        $this->expectException(FeedException::class);

        try {
            $rss = Feed::loadRss($this->atomUrl);
        } catch (FeedException $e) {
            throw $e;
        }
    }

    public function testInvalidAtom()
    {
        $this->expectException(FeedException::class);

        try {
            $rss = Feed::loadAtom($this->rssUrl);
        } catch (FeedException $e) {
            throw $e;
        }
    }

    public function testRssDcDate()
    {
        $rss = Feed::loadRss($this->dcDateUrl);

        $this->assertInstanceOf('Grunjol\Feed\Feed', $rss);
    }

    public function testGetXml()
    {
        $rss = Feed::loadRss($this->dcDateUrl);
        $objVal = $rss->__get('dc:date');

        $this->assertInstanceOf('\SimpleXMLElement', $objVal);
    }

    public function testSet()
    {
        $this->expectException(\Exception::class);
        $rss = Feed::loadRss($this->dcDateUrl);

        try {
            $rss->__set('cutomTag', 'customValue');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function testToArray()
    {
        $rss = Feed::loadRss($this->dcDateUrl);
        $feedArr = $rss->toArray($rss->__get('item'));

        $this->assertIsArray($feedArr);

        $feedArr = $rss->toArray();

        $this->assertIsArray($feedArr);
    }

    public function testAuthRss()
    {
        $rss = Feed::loadRss($this->authRssUrl, ['auth' => ['user-testing-get-rss', 'do-rss-unit-testing']]);

        $this->assertInstanceOf('\Grunjol\Feed\Feed', $rss);
    }

    public function testUnAuthorization()
    {
        $this->expectException(FeedException::class);

        try {
            $rss = Feed::loadRss($this->authRssUrl, ['auth' => ['error-user', 'error-password']]);
        } catch (FeedException $e) {
            throw $e;
        }
    }
}
