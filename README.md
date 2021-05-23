
# Treblle for PHP

[![Latest Version](https://img.shields.io/packagist/v/treblle/treblle-php)](https://packagist.org/packages/treblle/treblle-php)
[![Total Downloads](https://img.shields.io/packagist/dt/treblle/treblle-php)](https://packagist.org/packages/treblle/treblle-php)
[![MIT Licence](https://img.shields.io/packagist/l/treblle/treblle-php)](LICENSE.md)

Treblle makes it super easy to understand what’s going on with your APIs and the apps that use them. Just by adding Treblle to your API out of the box you get:
* Real-time API monitoring and logging
* Auto-generated API docs with OAS support
* API analytics
* Quality scoring
* One-click testing
* API management on the go
* and more...

## Requirements
* PHP 5.5+

## Dependencies
* [`guzzlehttp/guzzle`](https://packagist.org/packages/guzzlehttp/guzzle)

## Installation
You can install Treblle for PHP via [Composer](http://getcomposer.org/). Simply run the following command:
```bash
$ composer require treblle/treblle-php
```

## Getting started
Next, create a FREE account on <https://treblle.com> to get an API key and Project ID. After you have those simply initialize Treblle in your API code like so:

```php
<?php
// DON'T FORGET TO AUTOLOAD COMPOSER DEPENDENCIES
require_once("vendor/autoload.php");

// INITIALIZE TREBLLE
$treblle = new Treblle\Treblle('_YOUR_API_KEY_', '_YOUR_PROJECT_ID_');
```
That's it. Your API requests and responses are now being sent to your Treblle project. Just by adding that line of code you get features like: auto-documentation, real-time request/response monitoring, error tracking and so much more.

## Configuration options
Treblle **masks sensitive information** from the request parameters **before it even leaves your server**. The following parameters are automatically masked: password, pwd, secret, password_confirmation, cc, card_number, ccv, ssn, credit_score. You can extend this list by providing your own custom keywords by doing the following:

```php
<?php
// DON'T FORGET TO AUTOLOAD COMPOSER DEPENDENCIES
require_once("vendor/autoload.php");

/*
* Pass an array of words that you would like to be masked
* as a third parameter when initializing Treblle
*/
$treblle = new Treblle\Treblle(
	'_YOUR_API_KEY_', 
	'_YOUR_PROJECT_ID_', 
	['keyword', 'maskme', 'sensitive']
);
```

## Support
If you have problems of any kind feel free to reach out via <https://treblle.com> or email vedran@treblle.com and we'll do our best to help you out.

## License
Copyright 2021, Treblle Limited. Licensed under the MIT license:
http://www.opensource.org/licenses/mit-license.php
