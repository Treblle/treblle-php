<div align="center">
  <img src="https://github.com/user-attachments/assets/5b63bd2c-39ec-46cc-b1d5-42b7d79460c4"/>
</div>
<div align="center">

# Treblle

<a href="https://docs.treblle.com/en/integrations" target="_blank">Integrations</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="http://treblle.com/" target="_blank">Website</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://docs.treblle.com" target="_blank">Docs</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://blog.treblle.com" target="_blank">Blog</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://twitter.com/treblleapi" target="_blank">Twitter</a>
<span>&nbsp;&nbsp;•&nbsp;&nbsp;</span>
<a href="https://treblle.com/chat" target="_blank">Discord</a>
<br />

  <hr />
</div>

API Intelligence Platform. 🚀

Treblle is a lightweight SDK that helps Engineering and Product teams build, ship & maintain REST-based APIs faster.

## Features

<div align="center">
  <br />
  <img src="https://github.com/user-attachments/assets/558f9b23-3a9d-42f4-94d0-f2d8d4956bdf"/>
  <br />
  <br />
</div>

- [API Monitoring & Observability](https://www.treblle.com/features/api-monitoring-observability)
- [Auto-generated API Docs](https://www.treblle.com/features/auto-generated-api-docs)
- [API analytics](https://www.treblle.com/features/api-analytics)
- [Treblle API Score](https://www.treblle.com/features/api-quality-score)
- [API Lifecycle Collaboration](https://www.treblle.com/features/api-lifecycle)
- [Native Treblle Apps](https://www.treblle.com/features/native-apps)


## How Treblle Works
Once you’ve integrated a Treblle SDK in your codebase, this SDK will send requests and response data to your Treblle Dashboard.

In your Treblle Dashboard you get to see real-time requests to your API, auto-generated API docs, API analytics like how fast the response was for an endpoint, the load size of the response, etc.

Treblle also uses the requests sent to your Dashboard to calculate your API score which is a quality score that’s calculated based on the performance, quality, and security best practices for your API.

> Visit [https://docs.treblle.com](http://docs.treblle.com) for the complete documentation.

## Security

### Masking fields
Masking fields ensure certain sensitive data are removed before being sent to Treblle.

To make sure masking is done before any data leaves your server [we built it into all our SDKs](https://docs.treblle.com/en/security/masked-fields#fields-masked-by-default).

This means data masking is super fast and happens on a programming level before the API request is sent to Treblle. You can [customize](https://docs.treblle.com/en/security/masked-fields#custom-masked-fields) exactly which fields are masked when you’re integrating the SDK.

> Visit the [Masked fields](https://docs.treblle.com/en/security/masked-fields) section of the [docs](https://docs.sailscasts.com) for the complete documentation.


## Get Started

1. Sign in to [Treblle](https://platform.treblle.com).
2. [Create a Treblle project](https://docs.treblle.com/en/dashboard/projects#creating-a-project).
3. [Setup the SDK](#install-the-sdk) for your platform.

### Install the SDK

```sh
composer require treblle/treblle-php
```

After retrieving your API key and SDK token from the Treblle dashboard, initialize Treblle in your API code:

```php
<?php

declare(strict_types=1);

use Treblle\Php\Factory\TreblleFactory;

require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
ob_start();

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN']
);
```

That's it! Your API requests and responses are now being sent to your Treblle project.

## Configuration

### Basic Configuration

The SDK can be configured with various options:

```php
use Treblle\Php\Factory\TreblleFactory;

$treblle = TreblleFactory::create(
    apiKey: 'your-api-key',
    sdkToken: 'your-sdk-token',
    debug: false,  // Enable debug mode for development
    maskedFields: ['custom_secret', 'internal_token'],  // Additional fields to mask
    config: []  // Advanced configuration options
);
```

### Environment Variables

For production applications, use environment variables:

```bash
export TREBLLE_API_KEY="your-api-key"
export TREBLLE_SDK_TOKEN="your-sdk-token"
```

### Advanced Configuration

You can customize the SDK behavior with advanced configuration options:

```php
use GuzzleHttp\Client;
use Treblle\Php\Factory\TreblleFactory;

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: false,
    maskedFields: ['password', 'secret_key', 'token'],
    config: [
        'client' => new Client(),  // Custom HTTP client
        'url' => 'https://custom.endpoint.com',  // Custom Treblle endpoint
        'fork_process' => false,  // Enable background processing
        'register_handlers' => true,  // Auto-register error handlers

        // Custom data providers
        'server_provider' => null,
        'language_provider' => null,
        'request_provider' => null,
        'response_provider' => null,
        'error_provider' => null,
    ]
);
```

## Features

### 1. Automatic Data Collection

The SDK automatically captures and sends:

- **Server Information**: OS, protocol, timezone, software
- **Request Data**: URL, method, headers, body, query parameters
- **Response Data**: Status code, headers, body, load time
- **Language Info**: PHP version and environment
- **Error Tracking**: Exceptions and PHP errors

### 2. Field Masking

Sensitive data is automatically masked before sending to Treblle. Default masked fields include:

- `password`
- `pwd`
- `secret`
- `password_confirmation`
- `cc`
- `card_number`
- `ccv`
- `ssn`
- `credit_score`

**Add custom masked fields:**

```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    maskedFields: [
        'api_secret',
        'internal_token',
        'private_key',
    ]
);
```

The masker also automatically masks:
- Authorization headers (Bearer, Basic, Digest)
- API key headers (`x-api-key`)
- Base64 encoded images

### 3. Error & Exception Tracking

The SDK automatically captures:

- **PHP Errors**: Notices, warnings, fatal errors
- **Exceptions**: Uncaught exceptions
- **Shutdown Errors**: Fatal errors during shutdown

```php
// Errors are automatically tracked when handlers are registered
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: ['register_handlers' => true]  // Default: true
);
```

### 4. Debug Mode

Enable debug mode during development to see detailed error messages:

```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: true  // Throws exceptions instead of silently failing
);
```

### 5. Background Processing

For better performance, enable background processing using PHP's `pcntl_fork`:

```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: ['fork_process' => true]
);
```

**Note**: Requires the `pcntl` extension to be enabled.

### 6. Custom Data Providers

Override default data providers for custom implementations:

```php
use Treblle\Php\Contract\RequestDataProvider;
use Treblle\Php\DataTransferObject\Request;

class CustomRequestProvider implements RequestDataProvider
{
    public function getRequest(): Request
    {
        // Your custom implementation
    }
}

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: [
        'request_provider' => new CustomRequestProvider(),
    ]
);
```

Available provider interfaces:
- `ServerDataProvider`
- `LanguageDataProvider`
- `RequestDataProvider`
- `ResponseDataProvider`
- `ErrorDataProvider`

## Usage Examples

### Basic PHP Application

```php
<?php

declare(strict_types=1);

use Treblle\Php\Factory\TreblleFactory;

require_once __DIR__ . '/vendor/autoload.php';

// Start output buffering to capture response
ob_start();

// Initialize Treblle
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN']
);

// Your API logic
header('Content-Type: application/json');
echo json_encode(['message' => 'Hello, World!']);

// Output buffering will be captured automatically on shutdown
```

### With Custom Configuration

```php
<?php

use Treblle\Php\Factory\TreblleFactory;

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: $_ENV['APP_ENV'] === 'development',
    maskedFields: [
        'internal_token',
        'private_data',
        'admin_password',
    ],
    config: [
        'fork_process' => extension_loaded('pcntl'),
    ]
);

// Your application code
```

### Disable Auto-Registration

If you want to manually control when handlers are registered:

```php
<?php

use Treblle\Php\Factory\TreblleFactory;

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: [
        'register_handlers' => false,
    ]
);

// Manually register handlers
set_error_handler([$treblle, 'onError']);
set_exception_handler([$treblle, 'onException']);
register_shutdown_function([$treblle, 'onShutdown']);
```

## Migration Guide (v4.x to v5.x)

Version 5.0 introduces breaking changes to parameter naming for better clarity:

### What Changed

- **Parameter names renamed**:
  - `projectId` → `apiKey` (env: `TREBLLE_PROJECT_ID` → `TREBLLE_API_KEY`)
  - `apiKey` → `sdkToken` (env: `TREBLLE_API_KEY` → `TREBLLE_SDK_TOKEN`)

### Migration Steps

**Before (v4.x):**
```php
$treblle = TreblleFactory::create(
    apiKey: 'your-api-key',
    projectId: 'your-project-id'
);
```

**After (v5.x):**
```php
$treblle = TreblleFactory::create(
    apiKey: 'your-project-id',  // This is now your API key
    sdkToken: 'your-api-key'    // This is now your SDK token
);
```

**Environment Variables:**
```bash
# Before (v4.x)
export TREBLLE_API_KEY="your-api-key"
export TREBLLE_PROJECT_ID="your-project-id"

# After (v5.x)
export TREBLLE_API_KEY="your-project-id"
export TREBLLE_SDK_TOKEN="your-api-key"
```

> See the [docs](https://docs.treblle.com/en/integrations/php) for this SDK to learn more.

## Available SDKs

Treblle provides [open-source SDKs](https://docs.treblle.com/en/integrations) that let you seamlessly integrate Treblle with your REST-based APIs.

- [`treblle-laravel`](https://github.com/Treblle/treblle-laravel): SDK for Laravel
- [`treblle-php`](https://github.com/Treblle/treblle-php): SDK for PHP
- [`treblle-symfony`](https://github.com/Treblle/treblle-symfony): SDK for Symfony
- [`treblle-lumen`](https://github.com/Treblle/treblle-lumen): SDK for Lumen
- [`treblle-sails`](https://github.com/Treblle/treblle-sails): SDK for Sails
- [`treblle-adonisjs`](https://github.com/Treblle/treblle-adonisjs): SDK for AdonisJS
- [`treblle-fastify`](https://github.com/Treblle/treblle-fastify): SDK for Fastify
- [`treblle-directus`](https://github.com/Treblle/treblle-directus): SDK for Directus
- [`treblle-strapi`](https://github.com/Treblle/treblle-strapi): SDK for Strapi
- [`treblle-express`](https://github.com/Treblle/treblle-express): SDK for Express
- [`treblle-koa`](https://github.com/Treblle/treblle-koa): SDK for Koa
- [`treblle-go`](https://github.com/Treblle/treblle-go): SDK for Go
- [`treblle-ruby`](https://github.com/Treblle/treblle-ruby): SDK for Ruby on Rails
- [`treblle-python`](https://github.com/Treblle/treblle-python): SDK for Python/Django

> See the [docs](https://docs.treblle.com/en/integrations) for more on SDKs and Integrations.

## Other Packages

Besides the SDKs, we also provide helpers and configuration used for SDK
development. If you're thinking about contributing to or creating a SDK, have a look at the resources
below:

- [`treblle-utils`](https://github.com/Treblle/treblle-utils):  A set of helpers and
  utility functions useful for the JavaScript SDKs.
- [`php-utils`](https://github.com/Treblle/php-utils):   A set of helpers and
  utility functions useful for the PHP SDKs.

## Community 💙

First and foremost: **Star and watch this repository** to stay up-to-date.

Also, follow our [Blog](https://blog.treblle.com), and on [Twitter](https://twitter.com/treblleapi).

You can chat with the team and other members on [Discord](https://treblle.com/chat) and follow our tutorials and other video material at [YouTube](https://youtube.com/@treblle).

[![Treblle Discord](https://img.shields.io/badge/Treblle%20Discord-Join%20our%20Discord-F3F5FC?labelColor=7289DA&style=for-the-badge&logo=discord&logoColor=F3F5FC&link=https://treblle.com/chat)](https://treblle.com/chat)

[![Treblle YouTube](https://img.shields.io/badge/Treblle%20YouTube-Subscribe%20on%20YouTube-F3F5FC?labelColor=c4302b&style=for-the-badge&logo=YouTube&logoColor=F3F5FC&link=https://youtube.com/@treblle)](https://youtube.com/@treblle)

[![Treblle on Twitter](https://img.shields.io/badge/Treblle%20on%20Twitter-Follow%20Us-F3F5FC?labelColor=1DA1F2&style=for-the-badge&logo=Twitter&logoColor=F3F5FC&link=https://twitter.com/treblleapi)](https://twitter.com/treblleapi)

### How to contribute

Here are some ways of contributing to making Treblle better:

- **[Try out Treblle](https://docs.treblle.com/en/introduction#getting-started)**, and let us know ways to make Treblle better for you. Let us know here on [Discord](https://treblle.com/chat).
- Join our [Discord](https://treblle.com/chat) and connect with other members to share and learn from.
- Send a pull request to any of our [open source repositories](https://github.com/Treblle) on Github. Check the contribution guide on the repo you want to contribute to for more details about how to contribute. We're looking forward to your contribution!

### Contributors
<a href="https://github.com/Treblle/treblle-php/graphs/contributors">
  <p align="center">
    <img  src="https://contrib.rocks/image?repo=Treblle/treblle-php" alt="A table of avatars from the project's contributors" />
  </p>
</a>
