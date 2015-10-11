<?php

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represent the "User-Agent" header.
 */
class UserAgentHeader
{
    /**
     * list of known user-agent parts.
     *
     * @var string[]
     */
    private static $bots = [
        'bot',
        'spider',
        'crawl',
        'prtg',
        'pingman',
        'nagios',
        'slurp',
        'mysyndicaat',
        'ia_archiver',
        'facebookexternalhit',
        'java/1.',
        'wordpress',
        'twitterfeed',
        'shopping.com',
        'rssgraffiti',
        'squidwall',
        'feedfetcher-google',
        'facebookplatform',
        'offline explorer',
        'microsoft url control',
        'jakarta commons',
        'desktop',
        'wget',
        'curl',
        'httperf',
        'libwww-perl',
        'php',
        'webcopier',
        'google web preview',
        'bingpreview',
        'eventmachine httpclient',
        'flipboardproxy',
        'spy/2.1',
        'python-urllib',
        'check_http',
        'scrapy',
    ];

    /** @var string */
    private $userAgent;
    /** @var string */
    private $platform;
    /** @var string */
    private $browser;
    /** @var string */
    private $deviceType;
    /** @var bool */
    private $isBot;

    /**
     * Constructs an instance of this class.
     *
     * @param string $userAgent raw value of the "User-Agent" header
     */
    public function __construct($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Returns the platform of the user agent or "unknown".
     *
     * @return string
     */
    public function getPlatform()
    {
        if ($this->platform !== null) {
            return $this->platform;
        }

        $this->platform = 'unknown';

        if (preg_match('/(ipad|iphone|ipod)/i', $this->userAgent) && !preg_match('/Opera/i', $this->userAgent)) {
            $this->platform = 'iOS';
        } elseif (preg_match('/android/i', $this->userAgent) || preg_match('/(opera m)/i', $this->userAgent)) {
            $this->platform = 'Android';
        } elseif (preg_match('/(macintosh|mac os x)/i', $this->userAgent)) {
            $this->platform = 'Mac';
        } elseif (preg_match('/linux/i', $this->userAgent)) {
            $this->platform = 'Linux';
        } elseif (preg_match('/windows phone/i', $this->userAgent)) {
            $this->platform = 'Windows Phone';
        } elseif (preg_match('/windows|win32/i', $this->userAgent)) {
            $this->platform = 'Windows';
        }

        return $this->platform;
    }

    /**
     * Returns the browser name and the browser version.
     *
     * @param string $userAgent raw value of the "User-Agent" header
     *
     * @return string[]
     */
    private function extractBrowser($userAgent)
    {
        $displayName = 'unknown';
        $uaBrowser = '';

        if (preg_match('/MSIE|Trident/i', $userAgent) && !preg_match('/Opera/i', $userAgent)) {
            $displayName = 'Internet Explorer';
            $uaBrowser = 'MSIE';
        } elseif (preg_match('/OPR/i', $userAgent)) {
            $displayName = 'Opera';
            $uaBrowser = 'OPR';
        } elseif (preg_match('/Opera Mobi/i', $userAgent)) {
            $displayName = 'Opera Mobile';
            $uaBrowser = 'Opera Mobi';
        } else {
            $browsers = [
                'Firefox',
                'Chrome',
                'Safari',
                'Opera Mini',
                'Opera',
                'Seamonkey',
                'Konqueror',
                'Netscape',
                'Lynx',
                'Amaya',
                'Omniweb',
                'Avant',
                'Camino',
                'Flock',
            ];

            foreach ($browsers as $browser) {
                if (preg_match('/'.$browser.'/i', $userAgent)) {
                    $displayName = $browser;
                    $uaBrowser = $browser;

                    break;
                }
            }
        }

        if ($uaBrowser == '' &&
            preg_match('/(ipad|iphone|ipod).*applewebkit/i', $userAgent) &&
            !preg_match('/Opera/i', $userAgent)
        ) {
            $displayName = 'Safari';
        }

        $known = [
            'Version',
            $uaBrowser,
            'rv',
            'other',
        ];

        $version = '';

        $pattern = '#(?<browser>'.implode('|', $known).')[/: ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (preg_match_all($pattern, $userAgent, $matches)) {
            if (count($matches['browser']) != 1) {
                $pos = strripos($userAgent, 'Version');
                if (($pos !== false && $pos < strripos($userAgent, $uaBrowser)) || $uaBrowser == 'Opera Mini') {
                    $version = $matches['version'][0];
                } else {
                    $version = $matches['version'][1];
                }
            } else {
                $version = $matches['version'][0];
            }

            $version = preg_replace('/([0-9]+)[a-z][a-z0-9]*$/i', '$1', $version);
            $parts = explode('.', $version);
            if (count($parts) == 1) {
                $version = $version.'.0';
            } elseif (count($parts) > 2) {
                $version = $parts[0].'.'.$parts[1];
            }
        }

        if ($uaBrowser == 'Safari' && stripos($userAgent, 'android') !== false) {
            $displayName = 'stock browser';
        }

        return [
            'name' => $displayName,
            'version' => $version,
        ];
    }

    /**
     * Returns the browser name and the browser version of the user agent or "unknown".
     *
     * If the browser version follows semantic versioning only major and minor version of the browser are returned.
     * This means the "User-Agent" header of Firefox 38.0.5 will return "Firefox 38.0".
     *
     * @return string
     */
    public function getBrowser()
    {
        if ($this->browser !== null) {
            return $this->browser;
        }

        $info = $this->extractBrowser($this->userAgent);
        $this->browser = $info['name'].($info['version'] != '' ? ' '.$info['version'] : '');

        return $this->browser;
    }

    /**
     * Returns the device type of the user agent.
     *
     * Possible values are:
     * - desktop
     * - tablet
     * - mobile
     *
     * Tablet devices are only detected if the MobileDetect package is available.
     *
     * @return string
     */
    public function getDeviceType()
    {
        if ($this->deviceType !== null) {
            return $this->deviceType;
        }

        if (class_exists('\Mobile_Detect')) {
            $detect = new \Mobile_Detect();

            $this->deviceType = 'desktop';
            if ($detect->isTablet($this->userAgent)) {
                $this->deviceType = 'tablet';
            } elseif ($detect->isMobile($this->userAgent)) {
                $this->deviceType = 'mobile';
            }
        } else {
            $platform = $this->getPlatform();

            switch ($platform) {
                case 'Android':
                case 'iOS':
                case 'Windows Phone':
                    $this->deviceType = 'mobile';

                    break;
                default:
                    $this->deviceType = 'desktop';
            }
        }

        return $this->deviceType;
    }

    /**
     * Checks if the user agent can be identified as bot.
     *
     * @return bool
     */
    public function isBot()
    {
        if ($this->isBot !== null) {
            return $this->isBot;
        }

        $this->isBot = false;

        $botID = strtolower($this->userAgent);
        foreach (self::$bots as $bot) {
            if (strstr($botID, $bot)) {
                $this->isBot = true;
                break;
            }
        }

        return $this->isBot;
    }
}
