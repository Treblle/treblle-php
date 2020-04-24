# Treblle for PHP
Treblle makes it super easy to understand what’s going on with your APIs and the apps that use them. To get started with Treblle create a FREE account on <https://treblle.com>.

## Requirements
* PHP 5.5+

## Dependencies
* [`guzzlehttp/guzzle`](https://packagist.org/packages/guzzlehttp/guzzle)

## Installation
You can install Treblle via [Composer](http://getcomposer.org/). Simply run the following command:
```bash
$ composer require treblle/treblle-php
```
Don't forget to [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading) composer to your project by including the following code:

```php
require_once('vendor/autoload.php');
```

## Getting started
The first thing you need to do is create a FREE account on <https://treblle.com> to get an API key and Project ID. After that all you need to do is add the following line of code to your PHP API project: 

```php
$treblle = new Treblle\Treblle('YOUR_API_KEY', 'YOUR_PROJECT_ID');
```
That's it. Your API requests and responses are now being sent to your Treblle project. Just by adding that line of code you get features like: auto-documentation, real-time request/response monitoring, error tracking and so much more.

Treblle will catch everything that is sent to your API endpoints as well as everything that the endpoints return. In case you wish to add even more information to track specific things in your API but NOT return them in the response you can call add meta information to a specific API endpoint or all endpoints. To do so you can do the following:

The fourth parameter is an `$options` array. The additional options are:

```php
$treblle = new Treblle\Treblle('YOUR_API_KEY', 'YOUR_PROJECT_ID');
$treblle->addMeta('pricing', array('price_per_item' => 100, 'number_of_items' => '2', 'total' => 200));
```

The setMeta method takes in two parameters. The first one is the name of your meta information and the second one is an array where you can add ANY information you want. Treblle will make sure that this is attached to the request and you will always be able to see it and search for it.

## Support
If you have problems adding, installng or using Treblle feel free to reach out via <https://treblle.com> or contact vedran@flip.hr and we will make sure to do a FREE integration for you. 

## License
Copyright 2020, Treblle. Licensed under the MIT license:
http://www.opensource.org/licenses/mit-license.php
