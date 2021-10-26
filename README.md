# Treblle for PHP

[![Latest Version](https://img.shields.io/packagist/v/treblle/treblle-php)](https://packagist.org/packages/treblle/treblle-php)
[![Total Downloads](https://img.shields.io/packagist/dt/treblle/treblle-php)](https://packagist.org/packages/treblle/treblle-php)
[![MIT Licence](https://img.shields.io/packagist/l/treblle/treblle-php)](LICENSE.md)

Treblle makes it super easy to understand what’s going on with your APIs and the apps that use them. Just by adding
Treblle to your API out of the box you get:

* Real-time API monitoring and logging
* Auto-generated API docs with OAS support
* API analytics
* Quality scoring
* One-click testing
* API management on the go
* and more...

## Requirements

* PHP 7.4+

## Dependencies

* [`guzzlehttp/guzzle`](https://packagist.org/packages/guzzlehttp/guzzle)

## Installation

You can install Treblle for PHP via [Composer](http://getcomposer.org/). Simply run the following command:

```bash
$ composer require treblle/treblle-php
```

## Getting started

Next, create a FREE account on <https://treblle.com> to get an API key and Project ID. After you have those simply
initialize Treblle in your API code like so:

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Treblle\Factory\TreblleFactory;

require_once __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL);
ob_start();

$treblle = TreblleFactory::create('_YOUR_API_KEY_', '_YOUR_PROJECT_ID_']);
```

That's it. Your API requests and responses are now being sent to your Treblle project. Just by adding that line of code
you get features like: auto-documentation, real-time request/response monitoring, error tracking and so much more.

## Configuration options

### Debug mode

The third parameter sent to `TreblleFactory::create` factory method is boolean flag indicating whether we want to use
Treblle in a debug mode. Enabling debug mode is helpful when you want to understand what's happening under-the-hood and
allow Treblle errors to bubble up.

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Treblle\Factory\TreblleFactory;

// DON'T FORGET TO AUTOLOAD COMPOSER DEPENDENCIES
require_once __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL);
ob_start();

$treblle = TreblleFactory::create(
    '_YOUR_API_KEY_', 
    '_YOUR_PROJECT_ID_', 
    false, // Debug mode
);
```

### Masking sensitive information

Treblle **masks sensitive information** from the request parameters **before it even leaves your server**. The following
parameters are automatically
masked: `password, pwd, secret, password_confirmation, cc, card_number, ccv, ssn, credit_score`. You can extend this
list by providing your own custom keywords by doing the following:

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Treblle\Factory\TreblleFactory;

// DON'T FORGET TO AUTOLOAD COMPOSER DEPENDENCIES
require_once __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL);
ob_start();

/*
* Pass an array of words that you would like to be masked
* as a fourth parameter when initializing Treblle
*/
$treblle = TreblleFactory::create(
    '_YOUR_API_KEY_', 
    '_YOUR_PROJECT_ID_', 
    false, // Debug mode
    ['keyword', 'maskme', 'sensitive']
);
```

### Overriding HTTP client and Treblle endpoint URL

The fifth parameter passed to `TreblleFactory::create` factory method is an array of config options allowing you to
override some components of Treblle.

The following values are supported:

- `client` - an instance of Guzzle client configured to behive as you want (e.g. controling the timeout or other
  aspects)
- `url` - Treblle API endpoint URL you want to use

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;use Treblle\Factory\TreblleFactory;

// DON'T FORGET TO AUTOLOAD COMPOSER DEPENDENCIES
require_once __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL);
ob_start();

/*
* Pass an array of words that you would like to be masked
* as a fourth parameter when initializing Treblle
*/
$treblle = TreblleFactory::create(
    '_YOUR_API_KEY_', 
    '_YOUR_PROJECT_ID_', 
    false, // Debug mode
    ['keyword', 'maskme', 'sensitive'],
    ['client' => new Client(), 'url' => 'https://custom.treblle.com']
);
```

## Support

If you have problems of any kind feel free to reach out via <https://treblle.com> or email vedran@treblle.com and we'll
do our best to help you out.

## License

Copyright 2021, Treblle Limited. Licensed under the MIT license:
http://www.opensource.org/licenses/mit-license.php
