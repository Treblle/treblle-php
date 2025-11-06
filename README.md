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
- [Auto-generated API Docs](https://treblle.com/product/api-documentation)
- [API analytics](https://www.treblle.com/features/api-analytics)
- [Treblle API Score](https://www.treblle.com/features/api-quality-score)


## How Treblle Works
Once you’ve integrated a Treblle SDK in your codebase, this SDK will send requests and response data to your Treblle Dashboard.

In your Treblle Dashboard you get to see real-time requests to your API, auto-generated API docs, API analytics like how fast the response was for an endpoint, the load size of the response, etc.

Treblle also uses the requests sent to your Dashboard to calculate your API score which is a quality score that’s calculated based on the performance, quality, and security best practices for your API.

> Visit [https://docs.treblle.com](http://docs.treblle.com) for the complete documentation.

## Security

### Masking fields
Masking fields ensure certain sensitive data are removed before being sent to Treblle.

To make sure masking is done before any data leaves your server [we built it into all our SDKs](https://docs.treblle.com/treblle/data-masking/).

This means data masking is super fast and happens on a programming level before the API request is sent to Treblle. You can [customize](https://docs.treblle.com/treblle/data-masking/) exactly which fields are masked when you’re integrating the SDK.

> Visit the [Masked fields](https://docs.treblle.com/treblle/data-masking/) section of the [docs](https://docs.sailscasts.com) for the complete documentation.


## Get Started

1. Sign in to [Treblle](https://platform.treblle.com).
2. [Create a Treblle project](https://docs.treblle.com/guides/getting-started/).
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

## Requirements

- PHP 8.2 or higher
- `ext-mbstring` extension (required)
- `ext-pcntl` extension (optional, for background processing)
- Composer

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
    excludedHeaders: ['X-Internal-*', 'X-Debug-Token'],  // Headers to exclude
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
    excludedHeaders: [
        'X-Internal-*',        // Wildcard: excludes all X-Internal-* headers
        'X-Debug-Token',       // Exact match: excludes only X-Debug-Token
        '/^Authorization$/i',  // Regex: case-insensitive Authorization header
    ],
    config: [
        'client' => new Client(),  // Custom HTTP client
        'url' => 'https://custom.endpoint.com',  // Custom Treblle endpoint
        'fork_process' => extension_loaded('pcntl'),  // Enable background processing
        'register_handlers' => true,  // Auto-register error handlers (default: true)

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

### 2. Sensitive Data Masking

Sensitive data is automatically masked before sending to Treblle. Default masked fields include:

- `password`, `pwd`, `secret`, `password_confirmation`
- `cc`, `card_number`, `ccv`
- `ssn`, `credit_score`

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

**Automatic masking:**
The SDK automatically masks sensitive data regardless of configuration:
- **Authorization headers**: Bearer, Basic, Digest tokens
- **API key headers**: `x-api-key`, `api-key`
- **Base64 images**: Replaced with `[image]` placeholder
- **All masked values**: Replaced with `*****`

Custom masked fields are merged with defaults, so you don't need to redefine the default fields.

### 3. Header Filtering

Exclude specific headers from being sent to Treblle using pattern matching:

```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    excludedHeaders: [
        'X-Internal-*',        // Wildcard: excludes all headers starting with X-Internal-
        'X-Debug-Token',       // Exact: excludes only this specific header
        '/^X-(Debug|Test)-/i', // Regex: excludes X-Debug-* and X-Test-* headers
    ]
);
```

**Matching strategies:**
- **Exact match**: `"X-Custom-Header"` matches only that specific header (case-insensitive)
- **Wildcard**: `"X-Internal-*"` matches all headers starting with prefix
- **Regex**: Patterns wrapped in `/` are treated as regular expressions

All header matching is case-insensitive by default.

### 4. Error & Exception Tracking

The SDK automatically captures all PHP errors and exceptions:

- **PHP Errors**: `E_ERROR`, `E_WARNING`, `E_NOTICE`, `E_DEPRECATED`, etc.
- **Exceptions**: Uncaught exceptions with full stack traces
- **Shutdown Errors**: Fatal errors caught during PHP shutdown

All error types are automatically translated from integers to readable strings (e.g., `E_WARNING`).

```php
// Errors are automatically tracked when handlers are registered (default behavior)
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: ['register_handlers' => true]  // Default: true
);
```

**Disable automatic error handling:**
```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: ['register_handlers' => false]
);

// Manually register handlers if needed
set_error_handler([$treblle, 'onError']);
set_exception_handler([$treblle, 'onException']);
register_shutdown_function([$treblle, 'onShutdown']);
```

### 5. Debug Mode

Enable debug mode during development to see detailed error messages:

```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: true  // Throws exceptions instead of silently failing
);
```

**Debug mode behavior:**
- **OFF (default)**: SDK errors are silently caught and ignored, ensuring your application continues running
- **ON**: SDK throws exceptions for easier troubleshooting during development

**Recommended usage:**
```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: $_ENV['APP_ENV'] === 'development'
);
```

### 6. Background Processing

Improve performance by sending data to Treblle in a background process:

```php
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: [ 'fork_process' => extension_loaded('pcntl') ]
);
```

**How it works:**
- When enabled, the SDK uses `pcntl_fork()` to create a child process
- The child process sends data to Treblle while the parent continues/exits immediately
- Falls back to blocking transmission if forking fails
- Requires the `pcntl` extension (not available on Windows)

**Performance impact:**
- **With fork**: ~0ms added to response time (non-blocking)
- **Without fork**: ~3-6ms added to response time (3-second timeout)

**Note**: The `pcntl` extension is typically available on Linux/macOS but not on Windows.

### 7. Custom Data Providers

Override default data providers for custom implementations:

```php
use Treblle\Php\Contract\RequestDataProvider;
use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\Helpers\SensitiveDataMasker;

class CustomRequestProvider implements RequestDataProvider
{
    public function __construct(
        private SensitiveDataMasker $masker,
        private array $excludedHeaders = []
    ) {}

    public function getRequest(): Request
    {
        // Your custom implementation
        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            url: 'https://api.example.com/endpoint',
            ip: '192.168.1.1',
            user_agent: 'Custom Client',
            method: 'POST',
            headers: ['Content-Type' => 'application/json'],
            body: $this->masker->mask(['data' => 'value'])
        );
    }
}

$masker = new SensitiveDataMasker(['password', 'secret']);
$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    config: [
        'request_provider' => new CustomRequestProvider($masker, ['X-Internal-*']),
    ]
);
```

**Available provider interfaces:**
- `ServerDataProvider` - Server/OS information
- `LanguageDataProvider` - PHP version and runtime
- `RequestDataProvider` - HTTP request data
- `ResponseDataProvider` - HTTP response data
- `ErrorDataProvider` - Error and exception storage

**Default implementations:**
- `SuperGlobalsServerDataProvider` - Uses `$_SERVER` superglobal
- `PhpLanguageDataProvider` - Uses `PHP_VERSION` constant
- `SuperGlobalsRequestDataProvider` - Uses `$_SERVER`, `$_REQUEST`, `getallheaders()`
- `OutputBufferingResponseDataProvider` - Uses output buffering (`ob_*` functions)
- `InMemoryErrorDataProvider` - In-memory array storage

## Important Considerations

### Output Buffering

The SDK requires output buffering to capture response data:

```php
// REQUIRED: Start output buffering before creating Treblle instance
ob_start();

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN']
);
```

**Response limitations:**
- Responses >= 2MB: Logged as error, empty body sent to Treblle
- Invalid JSON: Logged as error, empty body sent to Treblle
- The SDK uses `ob_get_flush()` which both retrieves and flushes the buffer

### Response Data Format

The SDK expects JSON responses. If your API returns JSON:
- Response body will be decoded and masked automatically
- Non-JSON responses will result in an empty body being sent

### IP Detection

The SDK automatically detects client IP addresses with proxy support:
1. Checks `HTTP_CLIENT_IP` first
2. Falls back to `HTTP_X_FORWARDED_FOR` (for proxied requests)
3. Finally uses `REMOTE_ADDR` (direct connection)
4. Defaults to `'bogon'` if no IP found

### Load Balancing

The SDK randomly selects from 3 Treblle endpoints for load balancing:
- `https://rocknrolla.treblle.com`
- `https://punisher.treblle.com`
- `https://sicario.treblle.com`

Each request to Treblle has:
- **Connection timeout**: 3 seconds
- **Request timeout**: 3 seconds
- **SSL verification**: Disabled for flexibility
- **HTTP errors**: Suppressed (non-blocking)

## Usage Examples

### Basic PHP Application

```php
<?php

declare(strict_types=1);

use Treblle\Php\Factory\TreblleFactory;

require_once __DIR__ . '/vendor/autoload.php';

// IMPORTANT: Start output buffering before Treblle initialization
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

### Production Configuration

```php
<?php

declare(strict_types=1);

use Treblle\Php\Factory\TreblleFactory;

require_once __DIR__ . '/vendor/autoload.php';

ob_start();

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: false,  // Silent failures in production
    maskedFields: [
        'internal_token',
        'private_data',
        'admin_password',
        'api_secret',
    ],
    excludedHeaders: [
        'X-Internal-*',    // Exclude internal headers
        'X-Debug-*',       // Exclude debug headers
    ],
    config: [
        'fork_process' => extension_loaded('pcntl'),  // Non-blocking on Linux/macOS
    ]
);

// Your application code
header('Content-Type: application/json');
$data = ['users' => [...], 'meta' => [...]];
echo json_encode($data);
```

### Development Configuration

```php
<?php

declare(strict_types=1);

use Treblle\Php\Factory\TreblleFactory;

require_once __DIR__ . '/vendor/autoload.php';

ob_start();

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: true,  // Throw exceptions for easier debugging
    maskedFields: ['password', 'secret'],  // Minimal masking for debugging
    config: [
        'fork_process' => false,  // Blocking mode for easier debugging
    ]
);

// Your application code
```

### Conditional Configuration

```php
<?php

use Treblle\Php\Factory\TreblleFactory;

$isProduction = $_ENV['APP_ENV'] === 'production';

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    debug: !$isProduction,  // Debug only in non-production
    maskedFields: $isProduction
        ? ['password', 'secret', 'internal_token', 'private_key']
        : ['password', 'secret'],
    excludedHeaders: $isProduction
        ? ['X-Internal-*', 'X-Debug-*']
        : [],
    config: [
        'fork_process' => $isProduction && extension_loaded('pcntl'),
    ]
);
```

## Troubleshooting

### Output Buffering Not Enabled

**Error:** `RuntimeException: Output buffering must be enabled to collect responses. Have you called 'ob_start()'?`

**Solution:** Call `ob_start()` before creating the Treblle instance:
```php
ob_start();  // Add this line
$treblle = TreblleFactory::create(...);
```

### No Data Appearing in Dashboard

1. **Check API credentials:**
   ```php
   var_dump($_ENV['TREBLLE_API_KEY'], $_ENV['TREBLLE_SDK_TOKEN']);
   ```

2. **Enable debug mode to see errors:**
   ```php
   $treblle = TreblleFactory::create(
       apiKey: $_ENV['TREBLLE_API_KEY'],
       sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
       debug: true  // Will throw exceptions if something fails
   );
   ```

3. **Check if handlers are registered:**
   ```php
   $treblle = TreblleFactory::create(
       apiKey: $_ENV['TREBLLE_API_KEY'],
       sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
       config: ['register_handlers' => true]  // Should be true (default)
   );
   ```

### Response Body Empty

**Possible causes:**
- Response size >= 2MB (check your response size)
- Invalid JSON in response (check JSON encoding)
- Output buffering not capturing response (ensure `ob_start()` is called early)

**Solution:** Check error logs when debug mode is enabled.

### Fork Process Not Working

**Issue:** Background processing not working on your system.

**Check if pcntl is available:**
```php
if (extension_loaded('pcntl')) {
    echo "PCNTL available";
} else {
    echo "PCNTL not available - will use blocking mode";
}
```

**Note:** The `pcntl` extension is not available on Windows. The SDK will automatically fall back to blocking mode.

### Custom Headers Not Being Excluded

**Issue:** Headers still appearing in Treblle despite being in `excludedHeaders`.

**Solution:** Check pattern syntax:
```php
excludedHeaders: [
    'X-Debug-Token',      // Exact match (case-insensitive)
    'X-Internal-*',       // Wildcard pattern
    '/^Authorization$/i'  // Regex pattern (wrapped in /)
]
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

> See the [docs](https://docs.treblle.com/integrations/php/laravel/) for this SDK to learn more.

## Available SDKs

Treblle provides [open-source SDKs](https://docs.treblle.com/en/integrations) that let you seamlessly integrate Treblle with your REST-based APIs.

- [`treblle-php`](https://github.com/Treblle/treblle-php): SDK for PHP
- [`treblle-laravel`](https://github.com/Treblle/treblle-laravel): SDK for Laravel
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

## Community 💙

First and foremost: **Star and watch this repository** to stay up-to-date.

Also, follow our [Blog](https://blog.treblle.com), and on [Twitter](https://twitter.com/treblleapi).

You can chat with the team and other members on [Discord](https://treblle.com/chat) and follow our tutorials and other video material at [YouTube](https://youtube.com/@treblle).

[![Treblle YouTube](https://img.shields.io/badge/Treblle%20YouTube-Subscribe%20on%20YouTube-F3F5FC?labelColor=c4302b&style=for-the-badge&logo=YouTube&logoColor=F3F5FC&link=https://youtube.com/@treblle)](https://youtube.com/@treblle)

[![Treblle on Twitter](https://img.shields.io/badge/Treblle%20on%20Twitter-Follow%20Us-F3F5FC?labelColor=1DA1F2&style=for-the-badge&logo=Twitter&logoColor=F3F5FC&link=https://twitter.com/treblleapi)](https://twitter.com/treblleapi)

### How to contribute

Here are some ways of contributing to making Treblle better:

- **[Try out Treblle](https://docs.treblle.com/guides/getting-started/)**, and let us know ways to make Treblle better for you.
- Send a pull request to any of our [open source repositories](https://github.com/Treblle) on Github. Check the contribution guide on the repo you want to contribute to for more details about how to contribute. We're looking forward to your contribution!

### Contributors
<a href="https://github.com/Treblle/treblle-php/graphs/contributors">
  <p align="center">
    <img  src="https://contrib.rocks/image?repo=Treblle/treblle-php" alt="A table of avatars from the project's contributors" />
  </p>
</a>
