<?php

use PHPUnit\Framework\TestCase;

final class FeedTest extends TestCase
{

    public $rssUrl = 'http://news.ycombinator.com/rss';
    public $atomUrl = 'https://raw.githubusercontent.com/yongjhih/yongjhih.github.com/master/atom.xml';
    public $dcDateUrl = 'https://gist.githubusercontent.com/ayukawa/c5975851112c54fb536b/raw/72d2c81761a225cd0cb1fb8cb34b3898b5d34297/FeedTest.xml';
    public $noFeedUrl = 'https://github.com/dg/rss-php';
    public $authRssUrl = 'http://peter279k.com/auth-rss/';
    
    public function testLoad()
    {
        $rss = Feed::load($this->rssUrl);

        $this->assertInstanceOf('\Feed', $rss);

        $rss = Feed::load($this->atomUrl);

        $this->assertInstanceOf('\Feed', $rss);
    }

    public function testLoadRss()
    {
        $rss = Feed::loadRss($this->rssUrl);

        $this->assertInstanceOf('\Feed', $rss);
    }

    public function testLoadAtom()
    {
        $rss = Feed::loadAtom($this->atomUrl);

        $this->assertInstanceOf('\Feed', $rss);
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

        $this->assertInstanceOf('\Feed', $rss);
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

        $this->assertInternalType('array', $feedArr);

        $feedArr = $rss->toArray();

        $this->assertInternalType('array', $feedArr);
    }

    public function testAuthRss()
    {
        $this->deleteCacheFiles();

        Feed::$cacheDir = __DIR__;
        $rss = Feed::loadRss($this->authRssUrl, 'user-testing-get-rss', 'do-rss-unit-testing');

        $this->assertInstanceOf('\Feed', $rss);

        Feed::$cacheDir = __DIR__;
        $rss = Feed::loadRss($this->authRssUrl, 'user-testing-get-rss', 'do-rss-unit-testing');

        $this->assertInstanceOf('\Feed', $rss);
    }

    public function testUnAuthorization()
    {
        $this->expectException(FeedException::class);

        try {
            Feed::$cacheDir = null;
            $rss = Feed::loadRss($this->authRssUrl, 'error-user', 'error-password');
        } catch (FeedException $e) {
            throw $e;
        }
    }

    public function deleteCacheFiles()
    {
        $xmlFiles = scandir(__DIR__);
        foreach($xmlFiles as $value) {
            $extendName = pathinfo(__DIR__.'/'.$value);
            if($extendName['extension'] === 'xml') {
                @unlink(__DIR__.'/'.$value);
            }
        }
    }
}
