# net-http-request

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

This package provides a Request class which extends the PSR-7 ServerRequest class with header and role objects and simplifies access to attributes, query and post parameters.
 
The included RequestFactory class can build requests automatically from PHP superglobals or from given data. It uses the factory interfaces defined in [binsoul/net-http-message-bridge](https://github.com/binsoul/net-http-message-bridge) to build URIs and streams. Headers typically set by load balancers or proxies are used to build requests.


## Install

Via composer:

``` bash
$ composer require binsoul/net-http-request
```

## Usage

Build a request from PHP superglobals: 

``` php
<?php

use BinSoul\Bridge\Http\Message\DefaultStreamFactory;
use BinSoul\Bridge\Http\Message\DefaultUriFactory;
use BinSoul\Net\Http\Request\RequestFactory;

require 'vendor/autoload.php';

$factory = new RequestFactory(new DefaultUriFactory(), new DefaultStreamFactory());
$request = $factory->buildFromEnvironment();
```

Build a request from provided data:

``` php
<?php

use BinSoul\Bridge\Http\Message\DefaultStreamFactory;
use BinSoul\Bridge\Http\Message\DefaultUriFactory;
use BinSoul\Bridge\Http\Message\Stream;
use BinSoul\Net\Http\Request\RequestFactory;

require 'vendor/autoload.php';

$stream = new Stream('php://memory', 'r+');
$stream->write('Hello world!');

$factory = new RequestFactory(new DefaultUriFactory(), new DefaultStreamFactory());
$request = $factory->buildFromData($stream, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

echo (string) $request->getBody(); // Hello world!
```

Use the URI implementation from [league/uri](https://github.com/thephpleague/uri):
``` php
<?php

use BinSoul\Bridge\Http\Message\DefaultStreamFactory;
use BinSoul\Bridge\Http\Message\UriFactory;
use BinSoul\Net\Http\Request\RequestFactory;
use League\Uri\Schemes\Http as HttpUri;

require 'vendor/autoload.php';

class LeagueUriFactory implements UriFactory
{
    public function build($uri)
    {
        return HttpUri::createFromString($uri);
    }
}

$factory = new RequestFactory(new LeagueUriFactory(), new DefaultStreamFactory());
$request = $factory->buildFromEnvironment();
```

## Example

Output a HTML page with information about the current request:

``` php
<?php

use BinSoul\Bridge\Http\Message\DefaultStreamFactory;
use BinSoul\Bridge\Http\Message\DefaultUriFactory;
use BinSoul\Net\Http\Request\RequestFactory;

require 'vendor/autoload.php';

$factory = new RequestFactory(new DefaultUriFactory(), new DefaultStreamFactory());
$request = $factory->buildFromEnvironment();

// Roles
$client = $request->getClient();
$server = $request->getServer();

// Headers
$userAgent = $request->getUserAgent();
$cacheControl = $request->getCacheControl();
$acceptMediaType = $request->getAcceptMediaType();
$acceptEncoding = $request->getAcceptEncoding();
$acceptLanguage = $request->getAcceptLanguage();
$acceptCharset = $request->getAcceptCharset();

// make var_export HTML friendly
function dump($value) {
    return htmlentities(preg_replace("/\s+=>\s+/m", ' => ', var_export($value, true)));
}

?>

<!DOCTYPE html>
<html>
<head>
    <style type="text/css">
        h1 { font-size: 150%; margin: 1em 0 0.25em 0; border-bottom: 1px solid #888;}
        td { vertical-align: top; padding: 2px 6px; }
        td:first-child {white-space: nowrap;}
    </style>
</head>
<body>
<h1>Request</h1>
<table>
    <tr>
        <td>protocol:</td>
        <td>HTTP/<?= $request->getProtocolVersion() ?></td>
    </tr>
    <tr>
        <td>method:</td>
        <td><?= $request->getMethod() ?></td>
    </tr>
    <tr>
        <td>URI:</td>
        <td><?= $request->getUri() ?></td>
    </tr>
    <tr>
        <td>query:</td>
        <td>
            <pre><?= dump($request->getQueryParams()) ?></pre>
        </td>
    </tr>
    <tr>
        <td>post:</td>
        <td>
            <pre><?= dump($request->getParsedBody()) ?></pre>
        </td>
    </tr>
    <tr>
        <td>files:</td>
        <td>
            <pre><?= dump($request->getUploadedFiles()) ?></pre>
        </td>
    </tr>
    <tr>
        <td>headers:</td>
        <td>
            <pre><?= dump($request->getHeaders()) ?></pre>
        </td>
    </tr>
    <tr>
        <td>Is SSL?</td>
        <td><?= $request->isSSL() ? 'yes' : 'no' ?></td>
    </tr>
    <tr>
        <td>Is DNT?</td>
        <td><?= $request->isDoNotTrack() ? 'yes' : 'no' ?></td>
    </tr>
    <tr>
        <td>Is Javascript?</td>
        <td><?= $request->isJavascript() ? 'yes' : 'no' ?></td>
    </tr>
</table>

<h1>Server</h1>
<table>
    <tr>
        <td>IP:</td>
        <td><?= $server->getIP() ?></td>
    </tr>
    <tr>
        <td>port:</td>
        <td><?= $server->getPort() ?></td>
    </tr>
</table>

<h1>Client</h1>
<table>
    <tr>
        <td>IP:</td>
        <td><?= $client->getIP() ?></td>
    </tr>
    <tr>
        <td>port:</td>
        <td><?= $client->getPort() ?></td>
    </tr>
    <tr>
        <td>Is headless?</td>
        <td><?= $client->isHeadless() ? 'yes' : 'no' ?></td>
    </tr>
</table>

<h1>User-Agent</h1>
<table>
    <tr>
        <td>browser:</td>
        <td><?= $userAgent->getBrowser() ?></td>
    </tr>
    <tr>
        <td>platform:</td>
        <td><?= $userAgent->getPlatform() ?></td>
    </tr>
    <tr>
        <td>device:</td>
        <td><?= $userAgent->getDeviceType() ?></td>
    </tr>
    <tr>
        <td>Is bot?</td>
        <td><?= $userAgent->isBot() ? 'yes' : 'no' ?></td>
    </tr>
</table>

<h1>Cache-Control</h1>
<table>
    <tr>
        <td>Has max-age?</td>
        <td><?= $cacheControl->hasMaxAge() ? 'yes' : 'no' ?></td>
    </tr>
    <tr>
        <td>max-age:</td>
        <td><?= $cacheControl->getMaxAge() ?></td>
    </tr>
    <tr>
        <td>Is refresh?</td>
        <td><?= $cacheControl->isRefresh() ? 'yes' : 'no' ?></td>
    </tr>
    <tr>
        <td>Is reload?</td>
        <td><?= $cacheControl->isReload() ? 'yes' : 'no' ?></td>
    </tr>
</table>

<h1>Accept</h1>
<ol>
    <?php foreach ($acceptMediaType->getMediaTypes() as $mediaType): ?>
        <li><?= $mediaType ?></li>
    <?php endforeach; ?>
</ol>

<h1>Accept-Encoding</h1>
<ol>
    <?php foreach ($acceptEncoding->getEncodings() as $encoding) : ?>
        <li><?= $encoding ?></li>
    <?php endforeach; ?>
</ol>

<h1>Accept-Language</h1>
<ol>
    <?php foreach ($acceptLanguage->getLanguages() as $language): ?>
        <li><?= $language ?></li>
    <?php endforeach; ?>
</ol>

<h1>Accept-Charset</h1>
<ol>
    <?php foreach ($acceptCharset->getCharsets() as $charset): ?>
        <li><?= $charset ?></li>
    <?php endforeach; ?>
</ol>
</body>
</html>
```
## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/binsoul/net-http-request.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/binsoul/net-http-request.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/binsoul/net-http-request
[link-downloads]: https://packagist.org/packages/binsoul/net-http-request
[link-author]: https://github.com/binsoul
