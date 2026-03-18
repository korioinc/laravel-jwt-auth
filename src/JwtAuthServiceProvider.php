<?php

namespace Korioinc\JwtAuth;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Korioinc\JwtAuth\Interfaces\RefreshTokenStorageInterface;
use Korioinc\JwtAuth\Middleware\AutoRefreshedTokenMiddleware;
use Korioinc\JwtAuth\Services\AuthService;
use Korioinc\JwtAuth\Utils\RefreshTokenCacheStorage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class JwtAuthServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-jwt-auth')
            ->hasConfigFile('jwt-auth')
            ->hasCommand(Commands\JwtTestCommand::class)
            ->hasCommand(Commands\JwtRefreshTestCommand::class);
    }

    public function register(): void
    {
        parent::register();

        $this->app->bind(RefreshTokenStorageInterface::class, RefreshTokenCacheStorage::class);
        $this->app->bind(AuthService::class);

        $this->app->singleton(Utils\Crypto::class, function ($app) {
            return new Utils\Crypto(config('jwt-auth.secret_key'));
        });

        $this->app->singleton('jwt-auth', function ($app) {
            return new JwtAuth(
                authService: $app->make(AuthService::class)
            );
        });

        Auth::extend('jwt', function (Application $app, string $name, array $config) {
            return new JwtGuard(
                provider: $app['auth']->createUserProvider($config['provider']),
                request: $app['request'],
                authService: $app->make(AuthService::class)
            );
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->app['router']->aliasMiddleware('jwt-auto-refresh', AutoRefreshedTokenMiddleware::class);
    }
}
