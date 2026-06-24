# Treblle - Runtime Intelligence Platform

[Website](http://treblle.com/) • [Documentation](https://docs.treblle.com/) • [Pricing](https://treblle.com/pricing)

Discover, Govern, and Secure APIs, Agents, and AI Across Any Cloud, Gateway or Technology.

## Treblle PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/treblle/treblle-php)](https://packagist.org/packages/treblle/treblle-php)
[![Total Downloads](https://img.shields.io/packagist/dt/treblle/treblle-php)](https://packagist.org/packages/treblle/treblle-php)

---

## Requirements

| Dependency  | Version |
|-------------|---------|
| PHP         | ^8.2    |
| ext-curl    | any     |
| ext-json    | any     |
| ext-mbstring | any    |
| ext-zlib    | any     |

---

## Installation

```bash
composer require treblle/treblle-php
```

---

## Setup

Add one line at the top of your entry point (e.g. `index.php`) before any output:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Treblle\Php\Treblle;

Treblle::create(
    sdkToken: 'your-sdk-token-from-treblle-dashboard',
    apiKey:   'your-api-key-from-treblle-dashboard',
);

// Your application code continues here...
```

Both credentials are available in your [Treblle Dashboard](https://treblle.com).

That's it. Treblle will automatically capture request and response data and ship it to the ingress asynchronously after your response has been sent.

---

## Configuration

Pass options as the third argument to `Treblle::create()`:

```php
use Treblle\Php\Treblle;

Treblle::create(
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    apiKey:   $_ENV['TREBLLE_API_KEY'],
    options: [
        'debug'           => false,
        'enabled'         => true,
        'masked_keywords' => [
            'password', 'pwd', 'secret', 'password_confirmation',
            'passwordConfirmation', 'cc', 'card_number', 'cardNumber',
            'ccv', 'ssn', 'credit_score', 'creditScore',
        ],
        'excluded_paths'  => [],
        'custom_ingress'  => null,
    ],
);
```

Or build a config object directly for more control:

```php
use Treblle\Php\Config\TreblleConfig;
use Treblle\Php\Treblle;

$config = new TreblleConfig(
    sdkToken:        $_ENV['TREBLLE_SDK_TOKEN'],
    apiKey:          $_ENV['TREBLLE_API_KEY'],
    debug:           false,
    maskedKeywords:  TreblleConfig::DEFAULT_MASKED_KEYWORDS,
    excludedPaths:   [],
    customIngress:   null,
    enabled:         true,
);

Treblle::start($config);
```

### `sdkToken`

Your Treblle SDK Token obtained from the Treblle Dashboard.

### `apiKey`

Your Treblle API Key obtained from the Treblle Dashboard.

### `enabled`

Controls whether Treblle is active. Defaults to `true`. Set to `false` to disable the SDK in specific environments without removing your credentials.

```php
use Treblle\Php\Treblle;

Treblle::create(
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    apiKey:   $_ENV['TREBLLE_API_KEY'],
    options: [
        'enabled' => $_ENV['APP_ENV'] === 'production',
    ],
);
```

### `debug`

When `true`, the SDK writes diagnostic messages to the PHP error log prefixed with `[TREBLLE]`. All messages are suppressed by default.

Useful for:
- Verifying your credentials are correct
- Seeing which requests are skipped and why
- Diagnosing ingress connectivity issues

```php
'debug' => true,
```

Example debug output:

```
[TREBLLE] Async: using fastcgi_finish_request
[TREBLLE] Request excluded by path: /health
[TREBLLE] Received 4xx from Treblle ingress: 422
[TREBLLE] curl error: Could not resolve host
```

Debug output goes to wherever PHP's `error_log` is configured - typically your web server error log or a file defined in `php.ini`.

### `masked_keywords`

Field names to mask in request bodies, response bodies, request headers, and response headers. Masking replaces every character of the value with `*`, preserving the original length, and is applied recursively to nested objects and arrays.

The SDK ships with a sensible default list. You control the list entirely - extend it, replace it, or set it to `[]` to disable masking.

```php
use Treblle\Php\Config\TreblleConfig;

// Extend the defaults
'masked_keywords' => array_merge(
    TreblleConfig::DEFAULT_MASKED_KEYWORDS,
    ['access_token', 'refresh_token', 'api_secret'],
),
```

```php
// Disable masking entirely
'masked_keywords' => [],
```

The `Authorization` header is masked with scheme-preserving formatting (e.g. `Bearer ****`) when it appears in the keyword list.

### `excluded_paths`

Paths that should not be tracked by Treblle. Supports exact matches, wildcards, and regular expressions.

```php
'excluded_paths' => [
    '/health',          // exact match
    '/uptime',          // exact match
    '/status',          // exact match
    'admin/*',          // wildcard: matches /admin/users, /admin/settings, etc.
    '/^\/debug\//i',    // regex: matches any path starting with /debug/
],
```

**Exact match** - the full path must match character for character (case-insensitive):

```php
'/health'   // matches /health only
```

**Wildcard** - use `*` (any sequence) or `?` (any single character):

```php
'admin/*'   // matches admin/users, admin/settings/edit, etc.
'api/v?/*'  // matches api/v1/anything, api/v2/anything, etc.
```

**Regex** - any string that starts and ends with `/` is treated as a regex:

```php
'/^\/api\/v\d+\/internal/'   // matches /api/v1/internal, /api/v2/internal, etc.
```

### `custom_ingress`

Override the default ingress endpoint. Useful for EU-hosted or self-hosted Treblle deployments:

```php
'custom_ingress' => 'https://ingress-eu.treblle.com',
```

---

## Endpoint Detection

In plain PHP we are unable to effectively determine the endpoint path for a request. Example for: `articles/12345` the endpoint path would be: `articles/{id}`. This allows us to build a much more accurate representation of how your API works. 

However there are two ways to supply it to us:

### Option 1 - `Treblle::setRoutePath()`

Call this after your router has resolved the route, before the request completes:

```php
use Treblle\Php\Treblle;

Treblle::setRoutePath('articles/{id}');
```

Works with any routing library. Example with [nikic/fast-route](https://github.com/nikic/FastRoute):

```php
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/v1/articles/{id:\d+}', 'ArticleHandler');
});

[$status, $handler, $vars] = $dispatcher->dispatch($method, $path);

if ($status === FastRoute\Dispatcher::FOUND) {
    Treblle::setRoutePath('/v1/articles/{id}');
}
```

### Option 2 - `$_SERVER['TREBLLE_ROUTE_PATH']`

Set the server variable anywhere before the shutdown handler runs. Useful when Treblle is initialised in a bootstrap file and the route path is resolved in a different layer:

```php
$_SERVER['TREBLLE_ROUTE_PATH'] = 'articles/{id}';
```

You can also set it from `.htaccess` for simple setups where a rewrite rule maps to a known pattern:

```apache
RewriteRule ^v1/articles/([0-9]+)$ index.php [L,E=TREBLLE_ROUTE_PATH:articles/{id}]
```

`setRoutePath()` always takes priority over `$_SERVER['TREBLLE_ROUTE_PATH']`.

---

## Enrich Requests With Metadata

Attach custom key/value pairs to any request that will show up on the Treblle dashboard and you can filter/search for requests with specific metadata. 

Note: `user-id` is a reserved keyword for helping us build entire customer dashboards and track customer usage across your API.

```php
use Treblle\Php\Treblle;

Treblle::metadata([
    'user-id' => 'john@acmecorp.com',
    'tenant' => 'acme-corp',
    'plan' => 'enterprise',
]);
```

Call `Treblle::metadata()` anywhere during the request lifecycle - in a middleware, after authentication, inside a controller. Multiple calls are merged together:

```php
// In an auth middleware
Treblle::metadata(['user-id' => $user->id, 'role' => $user->role]);

// Later, in a controller
Treblle::metadata(['feature_flag' => 'new-checkout']);
```

The final payload will contain both sets of keys:

```json
{
  "data": {
    "metadata": {
      "user-id": 42,
      "role": "admin",
      "feature_flag": "new-checkout"
    }
  }
}
```

Metadata values can be any JSON-serializable type: strings, integers, booleans, or nested arrays.

In persistent runtimes (Swoole, RoadRunner, FrankenPHP), call `Treblle::reset()` at the start of each request cycle to clear metadata from the previous request alongside all other request state.

---

## PSR-15 Middleware

If your application uses a PSR-15 compatible framework (Slim, Mezzio, Laravel via `league/route`, etc.) you can use `TreblleMiddleware` instead of `Treblle::create()`. The middleware integrates directly into the PSR-15 stack, reads request and response data from the PSR-7 objects (no output buffering), and ships the payload asynchronously via a shutdown function after the response has been delivered.

```php
use Treblle\Php\Middleware\TreblleMiddleware;

$middleware = TreblleMiddleware::create(
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    apiKey:   $_ENV['TREBLLE_API_KEY'],
);

// Slim 4
$app->add($middleware);

// Mezzio / Laminas
$app->pipe($middleware);
```

Register it as the **outermost** middleware so it wraps the full request/response cycle and captures all headers and the complete response body.

All options from `Treblle::create()` are supported as the third argument:

```php
$middleware = TreblleMiddleware::create(
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    apiKey:   $_ENV['TREBLLE_API_KEY'],
    options: [
        'enabled'         => $_ENV['APP_ENV'] === 'production',
        'masked_keywords' => array_merge(TreblleConfig::DEFAULT_MASKED_KEYWORDS, ['access_token']),
        'excluded_paths'  => ['/health', '/metrics'],
    ],
);
```

Or construct with a config object directly:

```php
use Treblle\Php\Config\TreblleConfig;
use Treblle\Php\Middleware\TreblleMiddleware;

$middleware = new TreblleMiddleware(new TreblleConfig(
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],
    apiKey:   $_ENV['TREBLLE_API_KEY'],
));
```

### Route path in PSR-15

Supply the parameterised route pattern via a PSR-7 request attribute named `_treblle_route_path`. Most routers let you set attributes on the request object before calling the handler:

```php
$request = $request->withAttribute('_treblle_route_path', 'articles/{id}');
```

`Treblle::setRoutePath()` also works from inside the handler if you prefer that approach.

### Metadata in PSR-15

`Treblle::metadata()` works the same way - call it anywhere inside the handler or its downstream middleware:

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $user = $this->auth->authenticate($request);
    Treblle::metadata(['user_id' => $user->id, 'plan' => $user->plan]);

    // ... handle request ...
}
```

---

## Async Mode

The SDK sends data to Treblle after your response has been delivered, using the first available strategy:

1. **`fastcgi_finish_request()`** (PHP-FPM) - flushes the HTTP response to the client and continues running in the background. The Treblle call is completely invisible to your end users. This is the default in most production PHP deployments.

2. **`pcntl_fork()`** (Linux/Unix CLI and FPM where `pcntl` is available) - forks a child process to send the data. The parent process returns immediately.

3. **Sync fallback** - if neither of the above is available, the data is sent synchronously after the response is flushed. This adds network latency to your shutdown phase but does not affect the response your users receive.

No configuration is required. The SDK detects the environment at runtime and picks the best available strategy.

---

## Migrating from v5 to v6

### 1. Update the package

```bash
composer require treblle/treblle-php:^6.0
```

### 2. Remove the Guzzle dependency

v6 uses native PHP curl - Guzzle is no longer required. If your project only pulled it in for Treblle, you can remove it:

```bash
composer remove guzzlehttp/guzzle
```

If other packages in your project depend on Guzzle, leave it - removing it won't break anything, it's simply no longer used by this SDK.

### 3. Replace `TreblleFactory::create()` with `Treblle::create()`

The factory class is gone. Initialisation now goes through `Treblle::create()` directly.

**Before (v5):**

```php
use Treblle\Php\Factory\TreblleFactory;

TreblleFactory::create(
    apiKey:   'your-api-key',
    sdkToken: 'your-sdk-token',
);
```

**After (v6):**

```php
use Treblle\Php\Treblle;

Treblle::create(
    sdkToken: 'your-sdk-token',
    apiKey:   'your-api-key',
);
```

Note that the parameter order is reversed - `sdkToken` is now first.

### 4. Update renamed and moved options

| v5 | v6 | Notes |
|---|---|---|
| `$maskedFields` (3rd positional arg) | `options['masked_keywords']` | Renamed; now controls the full list, not just additions |
| `$excludedHeaders` (4th positional arg) | _(removed)_ | See below |
| `$config['url']` | `options['custom_ingress']` | Renamed |
| `$config['fork_process']` | _(removed)_ | Async is now automatic |
| `$config['register_handlers']` | _(removed)_ | Handlers are always registered |
| _(new)_ | `options['enabled']` | Global on/off switch |

**Full before/after example:**

```php
// Before (v5)
use Treblle\Php\Factory\TreblleFactory;

TreblleFactory::create(
    apiKey:          'your-api-key',
    sdkToken:        'your-sdk-token',
    debug:           false,
    maskedFields:    ['access_token', 'refresh_token'],
    excludedHeaders: ['X-Internal-Header'],
    config: [
        'url'          => 'https://ingress-eu.treblle.com',
        'fork_process' => true,
    ],
);
```

```php
// After (v6)
use Treblle\Php\Treblle;

Treblle::create(
    sdkToken: 'your-sdk-token',
    apiKey:   'your-api-key',
    options: [
        'debug'           => false,
        'masked_keywords' => ['password', 'pwd', 'secret', 'access_token', 'refresh_token'],
        'custom_ingress'  => 'https://ingress-eu.treblle.com',
    ],
);
```

### 5. Review `maskedFields` vs `masked_keywords`

In v5, `$maskedFields` was **additive** - the SDK merged your list with its own defaults.

In v6, `masked_keywords` is the **full list**. If you pass a value, it replaces the defaults entirely. To extend the defaults, merge them explicitly:

```php
use Treblle\Php\Config\TreblleConfig;
use Treblle\Php\Treblle;

Treblle::create(
    sdkToken: 'your-sdk-token',
    apiKey:   'your-api-key',
    options: [
        'masked_keywords' => array_merge(
            TreblleConfig::DEFAULT_MASKED_KEYWORDS,
            ['access_token', 'refresh_token'],
        ),
    ],
);
```

### 6. `excludedHeaders` is removed

v5's `$excludedHeaders` excluded header **names** from being sent to Treblle at all. v6 does not have this option. Instead:

- To hide the **value** of a sensitive header, add its name to `masked_keywords` - the value will be replaced with `*` characters.

### 7. Async is now automatic

In v5, background sending required explicitly setting `config['fork_process' => true]` and having the `pcntl` extension available.

In v6, the SDK automatically uses the best available async strategy at runtime - `fastcgi_finish_request()` in PHP-FPM environments, `pcntl_fork()` on Unix where available, and a sync fallback otherwise. No configuration needed.

---

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
