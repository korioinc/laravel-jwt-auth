# Laravel JWT Auth

A Laravel package for easy implementation of JWT (JSON Web Token) authentication. Fully compliant with JWT standard (RFC 7519).

## Key Features

- 🔐 JWT Standard (RFC 7519) Compliant
- 🔄 Automatic Token Refresh with Grace Period
- 🎯 Custom User Properties Support (props)
- 🚀 Seamless Integration with Laravel Auth System

## Installation

Install the package via composer:

```bash
composer require korioinc/laravel-jwt-auth
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Korioinc\JwtAuth\JwtAuthServiceProvider"
```

## Configuration

### 1. Environment Setup

Add the JWT secret key to your `.env` file:

```env
JWT_SECRET_KEY=your-secret-key-here
```

### 2. User Model Setup

Implement `JwtUserInterface` and use `JwtTrait` in your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Korioinc\JwtAuth\Interfaces\JwtUserInterface;
use Korioinc\JwtAuth\Traits\JwtTrait;

class User extends Authenticatable implements JwtUserInterface
{
    use JwtTrait;
    
    // Optional: Define custom properties to include in JWT payload
    public function getJwtProps(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            // Add any other user properties you need in the token
        ];
    }
}
```

### 3. Auth Guard Configuration

Add the JWT guard to your `config/auth.php` file:

```php
'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

## Usage

### Basic Authentication - Login and Token Generation

This example shows how to implement a standard email/password login endpoint:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();
        $tokenData = $user->generateJwt();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $tokenData->accessToken,
                'expires_at' => $tokenData->expiresAt,
                'refresh_token' => $tokenData->refreshToken,
            ],
        ]);
    }
}
```

### OAuth Login Example (Laravel Socialite)

Integrate social login providers (Google, Facebook, GitHub, etc.) with JWT authentication:

#### Important: Socialite `stateless()` Usage

When using Laravel Socialite with API routes (JWT authentication), you **MUST** include the `stateless()` method:

```php
// ✅ Correct for API routes
Socialite::driver($provider)->stateless()->redirect();
Socialite::driver($provider)->stateless()->user();

// ❌ Wrong for API routes - will cause session errors
Socialite::driver($provider)->redirect();
Socialite::driver($provider)->user();
```

The `stateless()` method disables session state verification, which is required for API routes since they don't use sessions.

**Example for API routes (routes/api.php):**

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider): JsonResponse
    {
        $providerUser = Socialite::driver($provider)->stateless()->user();

        // Find or create user based on OAuth provider data
        $user = User::firstOrCreate([
            'provider_id' => $providerUser->getId(),
            'provider' => $provider,
        ], [
            'email' => $providerUser->getEmail(),
            'name' => $providerUser->getName() ?? $providerUser->getNickname(),
            'nickname' => Str::random(16),
        ]);

        // Generate JWT token for the authenticated user
        $tokenData = $user->generateJwt();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $tokenData->accessToken,
                'expires_at' => $tokenData->expiresAt,
                'refresh_token' => $tokenData->refreshToken,
            ],
        ]);
    }
}
```

### Protecting API Routes

Use the `auth:api` middleware to protect your API endpoints. This middleware will validate the JWT token and authenticate the user:
```php
use Illuminate\Support\Facades\Route;

// Protected routes - requires valid JWT token
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Public routes - no authentication required
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/refresh', [AuthController::class, 'refresh']);

// OAuth routes
Route::get('/auth/{provider}', [OAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
```

### Token Refresh

Implement token refresh functionality to get a new access token using a refresh token:

```php
use Korioinc\JwtAuth\Services\AuthService;
use Korioinc\JwtAuth\Exceptions\InvalidRefreshToken;

public function refresh(Request $request): JsonResponse
{
    $refreshToken = $request->input('refresh_token');
    
    if (!$refreshToken) {
        return response()->json(['message' => 'Refresh token required'], 400);
    }
    
    try {
        $tokenData = app(AuthService::class)->refreshAccessToken($refreshToken);
        
        return response()->json([
            'token' => $tokenData->accessToken,
            'expires_at' => $tokenData->expiresAt,
            'refresh_token' => $tokenData->refreshToken,
        ]);
    } catch (InvalidRefreshToken $e) {
        return response()->json(['message' => 'Invalid refresh token'], 401);
    }
}
```

### Manual Token Refresh (After User Profile Update)

When user information changes, you need to refresh the JWT token to include updated properties:

```php
use Korioinc\JwtAuth\Facades\JwtAuth;

// Update user profile
$user->update(['name' => 'New Name']);

// Generate new JWT with updated props
$newToken = JwtAuth::refreshToken();

return response()->json([
    'message' => 'Profile updated successfully',
    'data' => [
        'token' => $newToken,
    ],
]
]);
```

## Automatic Token Refresh

This package provides automatic token refresh functionality to seamlessly renew tokens before or after expiration.

### Setup Auto-Refresh Middleware

Add the middleware to your API routes in `bootstrap/app.php`:

```php
use Korioinc\JwtAuth\Middleware\AutoRefreshedTokenMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->api(append: [
        AutoRefreshedTokenMiddleware::class,
    ]);
})
```

### Auto-Refresh Configuration

Configure auto-refresh behavior in `config/jwt-auth.php`:

```php
'auto_refresh' => [
    'enabled' => true,
    'grace_period' => 3600,     // Allow refresh up to 1 hour after expiration
    'cache_ttl' => 10,          // Cache refreshed tokens for 10 seconds
    'preemptive_refresh' => [
        'enabled' => true,
        'threshold' => 600,     // Start refreshing 10 minutes before expiration
    ],
],
```

### How It Works

1. **Preemptive Refresh**: When a token is close to expiration (within threshold), a new token is automatically generated
2. **Grace Period**: Even after expiration, tokens can be refreshed within the grace period
3. **Response Header**: New tokens are returned in the `X-Refreshed-Token` response header
4. **Caching**: Refreshed tokens are cached to prevent multiple refreshes for the same request

### ⚠️ Important: Client-Side Token Update

**When auto-refresh is enabled, clients MUST check for the `X-Refreshed-Token` header in every response. If present, the client must immediately replace their current Bearer token with the new token provided in this header.**

```javascript
// Example: Axios interceptor for automatic token update
axios.interceptors.response.use(
  (response) => {
    const refreshedToken = response.headers['x-refreshed-token'];
    if (refreshedToken) {
      // Update stored token
      localStorage.setItem('auth_token', refreshedToken);
      // Update axios default header
      axios.defaults.headers.common['Authorization'] = `Bearer ${refreshedToken}`;
    }
    return response;
  }
);
```

## JWT Token Structure

Example of JWT payload generated by this package:

```json
{
  "jti": "1:moySfYsHMzrDSJC7Qxfy4sWpaSdYUSOi",
  "iat": 1749997727,
  "exp": 1749997757,
  "sub": "1",
  "props": {
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin"
  }
}
```

## Testing Commands

This package includes artisan commands for testing JWT functionality:

```bash
# Test JWT generation with current configuration
php artisan jwt:test

# Decode and verify an existing JWT token
php artisan jwt:test "your.jwt.token"

# Test refresh token functionality
php artisan jwt:test-refresh "your-refresh-token"
```

These commands are useful for:
- Verifying your JWT configuration
- Debugging token issues
- Testing token generation and validation

## Advanced Configuration

### Custom Token Lifetime

```php
// Generate token with custom lifetime (in seconds)
$tokenData = $user->generateJwt(lifetime: 7200); // 2 hours
```

### Algorithm Options

Supported algorithms in `config/jwt-auth.php`:
```php
'algorithm' => 'HS256', // Options: HS256, HS384, HS512
```

### Cache Driver

Refresh tokens are stored using Laravel's cache system. Configure your preferred cache driver in `.env`:
```env
CACHE_DRIVER=redis  # Recommended for production
```

## Requirements

- PHP 8.4+
- Laravel 10.x, 11.x, 12.x, or 13.x

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for more information.
