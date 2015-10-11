<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Header\UserAgentHeader;

class UserAgentHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function userAgentBrowser()
    {
        return [
            ['Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0', 'Windows', 'Firefox 41.0', 'desktop'],
            ['Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:21.0) Gecko/20130331 Firefox/21.0', 'Linux', 'Firefox 21.0', 'desktop'],
            ['Mozilla/5.0 (Macintosh; I; Intel Mac OS X 11_7_9; de-LI; rv:1.9b4) Gecko/2012010317 Firefox/10.0a4', 'Mac', 'Firefox 10.0', 'desktop'],
            ['Mozilla/5.0 (Android; U; Android; pl; rv:1.9.2.8) Gecko/20100202 Firefox/3.5.8', 'Android', 'Firefox 3.5', 'mobile'],
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A', 'Mac', 'Safari 7.0', 'desktop'],
            ['Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_3 like Mac OS X; ja-jp) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5', 'iOS', 'Safari 5.0', 'mobile'],
            ['Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko', 'Windows', 'Internet Explorer 11.0', 'desktop'],
            ['Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322)', 'Windows', 'Internet Explorer 7.0', 'desktop'],
            ['Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.52 Safari/537.36 OPR/15.0.1147.100', 'Windows', 'Opera 15.0', 'desktop'],
            ['Mozilla/5.0 (Linux; U; Android 4.0.3; ko-kr; LG-L160L Build/IML74K) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30', 'Android', 'stock browser 4.0', 'mobile'],
            ['Opera/9.80 (J2ME/MIDP; Opera Mini/9 (Compatible; MSIE:9.0; iPhone; BlackBerry9700; AppleWebKit/24.746; U; en) Presto/2.5.25 Version/10.54', 'Android', 'Opera Mini 9.0', 'mobile'],
            ['Opera/12.02 (Android 4.1; Linux; Opera Mobi/ADR-1111101157; U; en-US) Presto/2.9.201 Version/12.02', 'Android', 'Opera Mobile 12.02', 'mobile'],
            ['Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0)', 'Windows Phone', 'Internet Explorer 9.0', 'mobile'],
        ];
    }

    /**
     * @dataProvider userAgentBrowser
     */
    public function test_browser_detection($userAgent, $expectedPlatform, $expectedBrowser, $expectedDeviceType)
    {
        $header = new UserAgentHeader($userAgent);
        $this->assertEquals($expectedPlatform, $header->getPlatform(), $userAgent);
        $this->assertEquals($expectedBrowser, $header->getBrowser(), $userAgent);
        $this->assertEquals($expectedDeviceType, $header->getDeviceType(), $userAgent);
    }

    /**
     * @dataProvider userAgentBrowser
     */
    public function test_isBot_detects_browsers($userAgent)
    {
        $this->assertFalse((new UserAgentHeader($userAgent))->isBot(), $userAgent);
    }

    public function userAgentBot()
    {
        return [
            ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            ['Googlebot-Image/1.0'],
            ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            ['Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)'],
            ['curl/7.21.3 (x86_64-redhat-linux-gnu) libcurl/7.21.3 NSS/3.13.1.0 zlib/1.2.5 libidn/1.19 libssh2/1.2.7'],
            ['Java/1.6.0_26'],
            ['libwww-perl/5.816'],
            ['Microsoft URL Control - 6.01.9782'],
            ['PHP/5.2.9'],
            ['PycURL/7.23.1'],
            ['Python-urllib/3.1'],
        ];
    }

    /**
     * @dataProvider userAgentBot
     */
    public function test_isBot_detects_bots($userAgent)
    {
        $this->assertTrue((new UserAgentHeader($userAgent))->isBot(), $userAgent);
    }

    /**
     * @dataProvider userAgentBrowser
     */
    public function test_value_cache($userAgent, $expectedPlatform, $expectedBrowser, $expectedDeviceType)
    {
        $header = new UserAgentHeader($userAgent);
        $header->getPlatform();
        $header->getBrowser();
        $header->getDeviceType();
        $header->isBot();

        $this->assertEquals($expectedPlatform, $header->getPlatform(), $userAgent);
        $this->assertEquals($expectedBrowser, $header->getBrowser(), $userAgent);
        $this->assertEquals($expectedDeviceType, $header->getDeviceType(), $userAgent);
        $this->assertFalse($header->isBot(), $userAgent);
    }
}
