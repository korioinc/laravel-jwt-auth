# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Laravel JWT Auth Package

Package: korioinc/laravel-jwt-auth

A Laravel package for easy implementation of JWT (JSON Web Token) authentication. Fully compliant with JWT standard (RFC 7519).

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyse

# Format code
composer format

# Test JWT functionality
php artisan jwt:test
php artisan jwt:test "your.jwt.token"

# Test refresh token functionality
php artisan jwt:test-refresh
php artisan jwt:test-refresh "your-refresh-token"
```

## Architecture Overview

### Core Components

1. **Service Provider** (`JwtAuthServiceProvider`): Registers all bindings and extends Laravel's auth system
2. **Guard** (`JwtGuard`): Custom authentication guard for JWT tokens
3. **AuthService**: Core authentication logic including token validation and refresh
4. **JwtAuthManager**: High-level JWT operations exposed via facade
5. **Data Objects**: Immutable data structures for JWT components (Jwt, JwtHeader, JwtPayload)

### Key Interfaces

- `JwtUserInterface`: Contract for JWT-enabled user models
- `RefreshTokenStorageInterface`: Contract for refresh token storage implementations

### Token Flow

1. User authenticates → `generateJwt()` creates JWT with custom props via `getJwtProps()`
2. JWT structure: Standard claims + nested `props` object for custom data
3. Refresh tokens stored in cache via `RefreshTokenCacheStorage`
4. Auto-refresh handled by `AutoRefreshedTokenMiddleware` with configurable preemptive refresh

### Custom User Properties

User models implement `getJwtProps()` to add custom data to JWT:
```php
public function getJwtProps(): array
{
    return [
        'name' => $this->name,
        'email' => $this->email,
        'role' => $this->role,
    ];
}
```

These properties are nested under `props` in the JWT payload for clean separation from standard claims.

### Facade Operations

- `JwtAuth::refreshToken($force = true)`: Manually refresh token with updated user props
  - Default `$force = true` bypasses cache for immediate prop updates
  - Use `$force = false` to utilize cached tokens

## Configuration

Main config file: `config/jwt-auth.php`
- `secret_key`: JWT signing key (never commit!)
- `algorithm`: HS256, HS384, or HS512
- `access_token.lifetime`: Token lifetime in seconds
- `refresh_token.lifetime`: Refresh token lifetime
- Auto-refresh settings with grace period and preemptive refresh

## Testing Approach

- Framework: Pest PHP v4
- Test base: Orchestra Testbench for package testing
- Architecture tests included
- PHPStan level 5 for static analysis

## important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.
