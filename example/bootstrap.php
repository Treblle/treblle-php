<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Treblle\Factory\TreblleFactory;

require_once __DIR__.'/../vendor/autoload.php';

ini_set('display_errors', 'Off');
error_reporting(E_ALL);
ob_start();

ini_set('xdebug.var_display_max_children', '-1');
ini_set('xdebug.var_display_max_data', '-1');
ini_set('xdebug.var_display_max_depth', '-1');

$treblle = TreblleFactory::create(
    'Your API key',
    'Your project ID',
    true,
    ['some_Value'],
    [
        'client' => new Client(),
        'url' => getenv('URL') ?: null,
    ]
);
