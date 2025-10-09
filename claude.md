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

5. **Field Masking** (`src/FieldMasker.php`):
   - Masks sensitive fields in request/response data
   - Default masked fields: password, secret, card_number, ssn, etc.
   - Custom masked fields support

### Key Features

- **Automatic data collection**: Captures server, request, response, language, and error data
- **Field masking**: Sensitive data removed before sending to Treblle
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
   - Send to Treblle API (optionally in forked process)
```

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
├── DataTransferObject/    # DTOs for structured data
├── Factory/               # Factory classes
└── *.php                  # Provider implementations
```

### Running Code Quality Tools

```bash
# Format code with Laravel Pint
composer pint
```

## Important Implementation Details

### Field Masking

Default masked fields (case-insensitive):
- `password`, `pwd`, `secret`, `password_confirmation`
- `cc`, `card_number`, `ccv`
- `ssn`, `credit_score`

Also automatically masks:
- Authorization headers (Bearer, Basic, Digest)
- API key headers (`x-api-key`)
- Base64 encoded images

### Output Buffering

The SDK uses `ob_start()` to capture response output. The response provider reads buffered content on shutdown.

### Background Processing

When `fork_process: true` and `pcntl_fork` is available:
- Forks child process to send data
- Main process continues/exits immediately
- Child process sends data then kills itself

### Error Handling

- **Debug mode OFF** (default): Silently catches and ignores SDK errors
- **Debug mode ON**: Throws exceptions for easier debugging

### Multiple Treblle Endpoints

The SDK randomly selects from 3 Treblle endpoints for load balancing:
- `https://rocknrolla.treblle.com`
- `https://punisher.treblle.com`
- `https://sicario.treblle.com`

Override with `config['url']` if needed.

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

## Common Tasks

### Adding a New Data Provider

1. Create interface in `src/Contract/`
2. Implement provider class in `src/`
3. Update `TreblleFactory` with default implementation
4. Allow override via `config` array

### Modifying Masked Fields

Add to default list in `TreblleFactory::create()` or pass via `maskedFields` parameter.

### Debugging SDK Issues

1. Enable debug mode: `debug: true`
2. Check error logs for exceptions
3. Verify API key and SDK token
4. Test with custom endpoint: `config['url']`

## Dependencies

- **guzzlehttp/guzzle**: `^7.4.5|^8.0` - HTTP client for sending data
- **ext-mbstring**: Required for string operations
- **ext-pcntl**: Optional, for background processing

## Development Dependencies

- **laravel/pint**: `^1.15` - Code formatter (PSR-12)

## Links

- **Platform**: https://platform.treblle.com
- **Documentation**: https://docs.treblle.com
- **Integration Docs**: https://docs.treblle.com/en/integrations/php
- **Support**: https://treblle.com/chat (Discord)

## Notes for Claude

- When making changes, always run `composer pint` before committing
- Maintain strict type declarations and final classes
- Follow the provider pattern for extensibility
- Keep field masking comprehensive for security
- Test with both debug mode ON and OFF
- Consider backward compatibility when modifying factory parameters
- Use named arguments for better readability in factory methods
