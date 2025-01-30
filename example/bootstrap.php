<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Treblle\Factory\TreblleFactory;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'Off');
error_reporting(E_ALL);
ob_start();

ini_set('xdebug.var_display_max_children', '-1');
ini_set('xdebug.var_display_max_data', '-1');
ini_set('xdebug.var_display_max_depth', '-1');

$treblle = TreblleFactory::create(
    apiKey: 'Your API key',
    projectId: 'Your project ID',
    debug: true,
    maskedFields: ['some_Value'],
    config: [
        'client' => new Client(),
    ]
);
