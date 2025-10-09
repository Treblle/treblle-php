# Treblle PHP SDK

## Project Overview

This is the official Treblle PHP SDK - a lightweight library that sends API request/response data to the Treblle API Intelligence Platform for monitoring, analytics, and auto-generated documentation.

**Version**: 5.0
**PHP Requirements**: ^8.2
**License**: MIT

## Architecture

### Core Components

1. **Treblle** (`src/Treblle.php`) - Main class that:
   - Registers error/exception handlers
   - Captures PHP errors and exceptions
   - Builds and sends payload to Treblle on shutdown
   - Supports optional background processing via `pcntl_fork`

2. **TreblleFactory** (`src/Factory/TreblleFactory.php`) - Factory for creating Treblle instances with:
   - Default configuration
   - Field masking setup
   - Automatic handler registration

3. **Data Providers** (Provider pattern):
   - `ServerDataProvider` - Server/OS information (`SuperGlobalsServerDataProvider`)
   - `LanguageDataProvider` - PHP version info (`PhpLanguageDataProvider`)
   - `RequestDataProvider` - HTTP request data (`SuperGlobalsRequestDataProvider`)
   - `ResponseDataProvider` - HTTP response data (`OutputBufferingResponseDataProvider`)
   - `ErrorDataProvider` - PHP errors/exceptions (`InMemoryErrorDataProvider`)

4. **DTOs** (`src/DataTransferObject/`):
   - `Data` - Top-level payload wrapper
   - `Server` - Server information
   - `Language` - Language/runtime info
   - `Request` - HTTP request details
   - `Response` - HTTP response details
   - `Error` - Error/exception details
   - `Os` - Operating system info

5. **Sensitive Data Masking** (`src/Helpers/SensitiveDataMasker.php`):
   - Masks sensitive fields in request/response data
   - Default masked fields: password, secret, card_number, ssn, etc.
   - Custom masked fields support
   - Handles authorization headers, API keys, and base64-encoded images

6. **Header Filtering** (`src/Helpers/HeaderFilter.php`):
   - Filters HTTP headers based on exclusion patterns
   - Supports exact matching, wildcard patterns, and regex
   - Custom excluded headers support

7. **Error Type Translation** (`src/Helpers/ErrorTypeTranslator.php`):
   - Translates PHP error type integers to string constant names
   - Handles all PHP error types (E_ERROR, E_WARNING, etc.)

### Key Features

- **Automatic data collection**: Captures server, request, response, language, and error data
- **Sensitive data masking**: Sensitive fields and data removed before sending to Treblle
- **Header filtering**: Exclude specific headers based on patterns before sending to Treblle
- **Error tracking**: PHP errors, exceptions, and shutdown errors
- **Background processing**: Optional `pcntl_fork` for non-blocking data transmission
- **Debug mode**: Throws exceptions instead of silent failures
- **Custom providers**: Inject custom data providers for special environments

### Data Flow

```
1. User creates Treblle instance via TreblleFactory
2. Handlers registered: error_handler, exception_handler, shutdown_function
3. During request processing:
   - Errors/exceptions captured by handlers
   - Response captured via output buffering
4. On shutdown:
   - Build payload from all providers
   - Mask sensitive fields
   - Filter excluded headers
   - Send to Treblle API (optionally in forked process)
```

### Payload Structure

The complete JSON payload sent to Treblle has this structure:

```json
{
  "api_key": "your-api-key",
  "sdk_token": "your-sdk-token",
  "sdk": "php",
  "version": 5.0,
  "data": {
    "server": {
      "protocol": "HTTP/1.1",
      "software": "Apache/2.4.41",
      "signature": "...",
      "timezone": "UTC",
      "os": {
        "name": "Linux",
        "release": "5.10.0",
        "architecture": "x86_64"
      }
    },
    "language": {
      "name": "php",
      "version": "8.2.15"
    },
    "request": {
      "timestamp": "2025-01-15 10:30:45",
      "url": "https://api.example.com/users?page=1",
      "ip": "192.168.1.100",
      "user_agent": "Mozilla/5.0...",
      "method": "POST",
      "headers": {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      "body": {
        "email": "user@example.com",
        "password": "*****"
      }
    },
    "response": {
      "code": 200,
      "size": 1024,
      "load_time": 123.45,
      "headers": {
        "Content-Type": "application/json"
      },
      "body": {
        "id": 123,
        "email": "user@example.com"
      }
    },
    "errors": [
      {
        "message": "Undefined variable: foo",
        "file": "/var/www/app.php",
        "line": 42,
        "source": "onError",
        "type": "E_WARNING"
      }
    ]
  }
}
```

All DTOs implement `JsonSerializable` for automatic JSON encoding.

## Configuration

### Basic Usage

```php
use Treblle\Php\Factory\TreblleFactory;

$treblle = TreblleFactory::create(
    apiKey: $_ENV['TREBLLE_API_KEY'],      // Your project API key
    sdkToken: $_ENV['TREBLLE_SDK_TOKEN'],  // Your SDK token
);
```

### Advanced Configuration

```php
$treblle = TreblleFactory::create(
    apiKey: 'your-api-key',
    sdkToken: 'your-sdk-token',
    debug: false,                           // Enable for dev (throws exceptions)
    maskedFields: ['custom_secret'],        // Additional fields to mask
    excludedHeaders: [                      // Headers to exclude from Treblle
        'X-Internal-*',                     // Wildcard patterns supported
        'X-Debug-Token',                    // Exact matches
        '/^Authorization$/i',               // Regex patterns
    ],
    config: [
        'client' => new Client(),           // Custom Guzzle client
        'url' => 'https://custom.endpoint', // Custom Treblle endpoint
        'fork_process' => false,            // Enable background processing
        'register_handlers' => true,        // Auto-register handlers

        // Custom providers
        'server_provider' => null,
        'language_provider' => null,
        'request_provider' => null,
        'response_provider' => null,
        'error_provider' => null,
    ]
);
```

### Environment Variables

- `TREBLLE_API_KEY` - Your Treblle API key
- `TREBLLE_SDK_TOKEN` - Your Treblle SDK token

## Coding Standards

### Style Guide

- **PSR-12** coding standard (enforced via Laravel Pint)
- **Strict types** declaration in all files: `declare(strict_types=1);`
- **Type hints** for all parameters and return types
- **Final classes** by default (see `Treblle`, `TreblleFactory`)
- **Named arguments** in factory/constructor calls for clarity

### File Organization

```
src/
├── Contract/              # Interfaces for data providers
│   ├── ErrorDataProvider.php
│   ├── LanguageDataProvider.php
│   ├── RequestDataProvider.php
│   ├── ResponseDataProvider.php
│   └── ServerDataProvider.php
├── DataTransferObject/    # DTOs for structured data (all implement JsonSerializable)
│   ├── Data.php          # Top-level payload wrapper
│   ├── Error.php         # Error/exception representation
│   ├── Language.php      # PHP runtime information
│   ├── Os.php            # Operating system details
│   ├── Request.php       # HTTP request data
│   ├── Response.php      # HTTP response data
│   └── Server.php        # Server/environment information
├── DataProviders/         # Concrete provider implementations
│   ├── InMemoryErrorDataProvider.php
│   ├── OutputBufferingResponseDataProvider.php
│   ├── PhpLanguageDataProvider.php
│   ├── SuperGlobalsRequestDataProvider.php
│   └── SuperGlobalsServerDataProvider.php
├── Factory/               # Factory classes
│   └── TreblleFactory.php
├── Helpers/               # Utility classes
│   ├── ErrorTypeTranslator.php    # PHP error type to string conversion
│   ├── HeaderFilter.php           # HTTP header filtering
│   └── SensitiveDataMasker.php    # Sensitive data masking
└── Treblle.php           # Core SDK class
```

### Running Code Quality Tools

```bash
# Format code with Laravel Pint
composer pint
```

## Important Implementation Details

### Sensitive Data Masking

Default masked fields (case-insensitive):
- `password`, `pwd`, `secret`, `password_confirmation`
- `cc`, `card_number`, `ccv`
- `ssn`, `credit_score`

Also automatically masks:
- Authorization headers (Bearer, Basic, Digest)
- API key headers (`x-api-key`)
- Base64 encoded images

### Header Filtering

Headers can be excluded before sending to Treblle using patterns:
- **Exact match**: `"X-Custom-Header"` excludes only that header
- **Wildcard**: `"X-Internal-*"` excludes all headers starting with "X-Internal-"
- **Regex**: `"/^Authorization$/i"` uses regular expression matching
- All matching is case-insensitive by default

### Output Buffering

The SDK uses `ob_start()` to capture response output. The `OutputBufferingResponseDataProvider` requires output buffering to be enabled and will throw a `RuntimeException` if `ob_get_level() < 1`.

**Important limitations**:
- Responses >= 2MB: Logged as error, returns empty body
- Invalid JSON: Logged as error, returns empty body
- Uses `ob_get_flush()` to retrieve and flush buffered output
- Calculates response size and load time from `REQUEST_TIME_FLOAT`

### Background Processing

When `fork_process: true` and `pcntl_fork` is available:
- Forks child process to send data using `pcntl_fork()`
- Main process continues/exits immediately (non-blocking)
- Child process sends data then terminates itself via platform-specific kill command
- Windows: `taskkill /F /T /PID {pid}`
- Unix/Linux/macOS: `kill -9 {pid}`
- Falls back to blocking transmission if fork fails (returns -1)

### Error Handling

- **Debug mode OFF** (default): Silently catches and ignores SDK errors
- **Debug mode ON**: Throws exceptions for easier debugging

### Multiple Treblle Endpoints

The SDK randomly selects from 3 Treblle endpoints for load balancing:
- `https://rocknrolla.treblle.com`
- `https://punisher.treblle.com`
- `https://sicario.treblle.com`

Override with `config['url']` if needed.

**HTTP Request Configuration**:
- Method: POST
- Connection timeout: 3 seconds
- Request timeout: 3 seconds
- SSL verification: Disabled (`verify: false`)
- HTTP errors: Suppressed (`http_errors: false`)
- Headers: `Content-Type: application/json`, `x-api-key: {sdk_token}`
- Short timeouts ensure minimal performance impact

## Testing

Currently no test suite is included in this repository. When adding tests:
- Use PHPUnit
- Add tests to `tests/` directory
- Follow PSR-4 autoloading: `Treblle\Php\Tests\`

## Migration Notes

### v4.x to v5.x

**Breaking Changes**:
- Parameter names renamed for clarity:
  - `projectId` → `apiKey`
  - `apiKey` → `sdkToken`
- Environment variables renamed:
  - `TREBLLE_PROJECT_ID` → `TREBLLE_API_KEY`
  - `TREBLLE_API_KEY` → `TREBLLE_SDK_TOKEN`

## Data Provider Details

### SuperGlobalsServerDataProvider
Collects server and OS information from `$_SERVER` superglobal:
- Protocol (HTTP/HTTPS from `$_SERVER['SERVER_PROTOCOL']`)
- Software (from `$_SERVER['SERVER_SOFTWARE']`)
- Signature (from `$_SERVER['SERVER_SIGNATURE']`)
- Timezone (from `date_default_timezone_get()`)
- OS information (name, release, architecture via `php_uname()`)

### PhpLanguageDataProvider
Returns PHP runtime information:
- Name: Always 'php'
- Version: From `PHP_VERSION` constant

### SuperGlobalsRequestDataProvider
Collects HTTP request data:
- Timestamp in UTC (Y-m-d H:i:s format)
- Full URL with protocol detection (HTTPS check)
- Client IP with proxy detection (`HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, `REMOTE_ADDR`)
- User-Agent from `$_SERVER['HTTP_USER_AGENT']`
- HTTP method from `$_SERVER['REQUEST_METHOD']`
- Headers from `getallheaders()` (filtered via HeaderFilter)
- Request body from `$_REQUEST` (masked via SensitiveDataMasker)

### OutputBufferingResponseDataProvider
Collects HTTP response data:
- HTTP status code from `http_response_code()` (defaults to 200)
- Response size from `ob_get_length()`
- Load time in milliseconds (from `REQUEST_TIME_FLOAT` to current time)
- Response body from `ob_get_flush()` decoded as JSON (masked)
- Response headers from `headers_list()` (filtered)
- Requires output buffering enabled before instantiation

### InMemoryErrorDataProvider
Stores errors in memory during request lifecycle:
- Simple array-based storage
- Errors added via `addError()` during request processing
- All errors retrieved via `getErrors()` during shutdown
- Stores Error DTOs created by error/exception handlers

## Common Tasks

### Adding a New Data Provider

1. Create interface in `src/Contract/`
2. Implement provider class in `src/DataProviders/`
3. Update `TreblleFactory` with default implementation
4. Allow override via `config` array parameter
5. Add comprehensive PHPDoc blocks following project standards

### Modifying Masked Fields

Add to default list in `TreblleFactory::create()` or pass via `maskedFields` parameter.

Custom fields are merged with defaults:
```php
$treblle = TreblleFactory::create(
    apiKey: 'key',
    sdkToken: 'token',
    maskedFields: ['custom_secret', 'internal_token']
);
```

### Adding Excluded Headers

Pass header patterns via `excludedHeaders` parameter:
```php
$treblle = TreblleFactory::create(
    apiKey: 'key',
    sdkToken: 'token',
    excludedHeaders: [
        'X-Debug-*',              // Wildcard
        'X-Internal-Token',       // Exact match
        '/^X-Custom-.*/i'         // Regex
    ]
);
```

### Debugging SDK Issues

1. Enable debug mode: `debug: true`
2. Check error logs for exceptions
3. Verify API key and SDK token
4. Test with custom endpoint: `config['url']`
5. Check output buffering is enabled: `ob_get_level() >= 1`
6. Verify handler registration: `config['register_handlers'] = true`

## Dependencies

- **guzzlehttp/guzzle**: `^7.4.5 || ^8.0 || ^9.0` - HTTP client for sending data (supports latest Guzzle versions)
- **ext-mbstring**: Required for string operations
- **ext-pcntl**: Optional, for background processing

### Guzzle Version Support

The SDK is tested and compatible with:
- **Guzzle 7.x** (currently 7.10.0) - Latest stable version
- **Guzzle 8.x** - Future-proof for when released
- **Guzzle 9.x** - Future-proof for when released

The SDK uses standard Guzzle PSR-18 client interfaces, ensuring compatibility across versions.

## Development Dependencies

- **laravel/pint**: `^1.15` - Code formatter (PSR-12)

## Links

- **Platform**: https://platform.treblle.com
- **Documentation**: https://docs.treblle.com
- **Integration Docs**: https://docs.treblle.com/en/integrations/php
- **Support**: https://treblle.com/chat (Discord)

## Helper Classes Deep Dive

### SensitiveDataMasker (`src/Helpers/SensitiveDataMasker.php`)

**Purpose**: Recursively masks sensitive data in arrays before transmission to Treblle.

**Features**:
- Case-insensitive field name matching
- Recursive processing of nested arrays
- Special handling for authorization headers (Bearer, Basic, Digest)
- Detection and masking of API key headers (`x-api-key`)
- Base64-encoded image detection and replacement with `[image]`

**Usage**:
```php
$masker = new SensitiveDataMasker(['password', 'secret']);
$masked = $masker->mask($data);
```

**Masking strategy**:
- String values: Replaced with `'*****'`
- Non-string values: Replaced with empty string
- Base64 images: Replaced with `'[image]'`
- Authorization headers: Completely masked regardless of pattern

### HeaderFilter (`src/Helpers/HeaderFilter.php`)

**Purpose**: Filters HTTP headers based on exclusion patterns.

**Matching strategies**:
1. **Exact matching**: `"X-Custom-Header"` matches only that specific header (case-insensitive)
2. **Wildcard patterns**: `"X-Internal-*"` matches all headers starting with "X-Internal-"
3. **Regex patterns**: `"/^Authorization$/i"` uses full regex matching

**Usage**:
```php
$filtered = HeaderFilter::filter($headers, ['X-Debug-*', 'Authorization']);
```

**Pattern detection**:
- Patterns starting with `/` and ending with `/` or `/i`: Treated as regex
- Patterns containing `*`: Treated as wildcards (converted to regex)
- All other patterns: Exact match (case-insensitive)

### ErrorTypeTranslator (`src/Helpers/ErrorTypeTranslator.php`)

**Purpose**: Translates PHP error type integers to human-readable string constants.

**Supported error types**:
- Fatal errors: `E_ERROR`, `E_CORE_ERROR`, `E_COMPILE_ERROR`, `E_USER_ERROR`
- Warnings: `E_WARNING`, `E_CORE_WARNING`, `E_COMPILE_WARNING`, `E_USER_WARNING`
- Parse errors: `E_PARSE`
- Notices: `E_NOTICE`, `E_USER_NOTICE`
- Strict standards: `E_STRICT`
- Recoverable errors: `E_RECOVERABLE_ERROR`
- Deprecations: `E_DEPRECATED`, `E_USER_DEPRECATED`
- All errors: `E_ALL`

**Usage**:
```php
$errorString = ErrorTypeTranslator::translateErrorType(E_WARNING); // Returns 'E_WARNING'
```

**Unknown types**: Returns `'UNKNOWN'` for unrecognized error type constants.

## Documentation Standards

All classes in the codebase follow comprehensive PHPDoc standards:

### Class-level Documentation
- Purpose and responsibility
- Key features and capabilities
- Usage notes and examples
- Special behaviors or limitations
- Package declaration

### Method Documentation
- Clear description of what the method does
- All parameters with types and descriptions
- Return type and description
- Thrown exceptions (when applicable)
- Usage examples for complex methods

### Property Documentation
- Type declarations
- Purpose and usage
- Default values when relevant

### Code Examples
- Use `<code>` tags instead of triple backticks in PHPDoc to avoid parse errors
- Keep examples concise and focused
- Show real-world usage patterns

## Notes for Claude

- When making changes, always run `composer pint` before committing
- Maintain strict type declarations and final classes
- Follow the provider pattern for extensibility
- Keep field masking comprehensive for security
- Test with both debug mode ON and OFF
- Consider backward compatibility when modifying factory parameters
- Use named arguments for better readability in factory methods
- All helper classes use static methods for stateless operations
- Use `readonly` properties where applicable (PHP 8.1+)
- Maintain comprehensive PHPDoc blocks for all classes, methods, and properties
- When documenting regex patterns in PHPDoc, use `<code>` tags instead of ``` to avoid parse errors
- Follow PSR-12 strictly - Laravel Pint enforces this automatically
