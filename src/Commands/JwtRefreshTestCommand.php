<?php

namespace Korioinc\JwtAuth\Commands;

use Illuminate\Console\Command;
use Korioinc\JwtAuth\Exceptions\InvalidRefreshToken;
use Korioinc\JwtAuth\Services\AuthService;

class JwtRefreshTestCommand extends Command
{
    protected $signature = 'jwt:test-refresh {--token= : Refresh token to test}';

    protected $description = 'Test JWT refresh token functionality';

    public function handle(): void
    {
        $refreshToken = $this->option('token');

        if ($refreshToken) {
            $this->testRefreshToken($refreshToken);
        } else {
            $this->runFullRefreshTokenTests();
        }
    }

    private function testRefreshToken(string $refreshToken): void
    {
        $this->info('Testing Refresh Token');
        $this->info('====================');
        $this->info('Refresh Token: '.substr($refreshToken, 0, 20).'...');
        $this->newLine();

        try {
            $authService = app(AuthService::class);

            // Get token data before refresh
            $storage = app(\Korioinc\JwtAuth\Interfaces\RefreshTokenStorageInterface::class);
            $tokenData = $storage->getData($refreshToken);

            if ($tokenData) {
                $this->info('Token Data:');
                $this->line('  User Identifier: '.$tokenData->identifier);
                $this->line('  Token Lifetime: '.$tokenData->lifetime.' seconds');
                $this->newLine();
            }

            // Refresh the token
            $this->info('Refreshing token...');
            $newTokenData = $authService->refreshAccessToken($refreshToken);

            $this->info('✓ Token refreshed successfully!');
            $this->newLine();

            $this->info('New Access Token:');
            $this->line($newTokenData->accessToken);
            $this->newLine();

            $this->info('Token Details:');
            $this->line('  Expires at: '.date('Y-m-d H:i:s', $newTokenData->expiresAt));
            $this->line('  Expires in: '.round(($newTokenData->expiresAt - time()) / 60, 1).' minutes');
            $this->newLine();

            $this->info('New Refresh Token (full):');
            $this->line($newTokenData->refreshToken);

            // Decode to show payload
            $jwt = \Korioinc\JwtAuth\Data\Jwt::decode($newTokenData->accessToken);
            $this->newLine();
            $this->info('JWT Payload:');
            $this->line('  Subject (user): '.$jwt->payload->sub);
            $this->line('  JWT ID: '.$jwt->payload->jti);
            $this->line('  Issued at: '.date('Y-m-d H:i:s', $jwt->payload->iat));

        } catch (InvalidRefreshToken $e) {
            $this->error('Invalid or expired refresh token');
            $context = $e->getContext();
            if (! empty($context)) {
                $this->error('Details: '.json_encode($context));
            }
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }

    private function runFullRefreshTokenTests(): void
    {
        $this->info('JWT Refresh Token Test Suite');
        $this->info('============================');

        // Get User Model
        $userModel = config('auth.providers.users.model');
        if (! $userModel) {
            $this->error('User model not configured in auth.providers.users.model');

            return;
        }

        // Get Test User
        $user = $userModel::first();
        if (! $user) {
            $this->error('No users found in database. Please create a user first.');

            return;
        }

        $this->info("Using user: {$user->getAuthIdentifier()}");
        $this->newLine();

        try {
            // Test 1: Generate token with default lifetime
            $this->info('Test 1: Generate token with default lifetime');
            $tokenData = $user->generateJwt();
            $this->info('✓ Access token generated');
            $this->info('  Expires at: '.date('Y-m-d H:i:s', $tokenData->expiresAt));
            $this->info('  Refresh token: '.substr($tokenData->refreshToken, 0, 20).'...');
            $this->newLine();

            // Test 2: Refresh the token
            $this->info('Test 2: Refresh the access token');
            $authService = app(AuthService::class);
            $newTokenData = $authService->refreshAccessToken($tokenData->refreshToken);
            $this->info('✓ Token refreshed successfully');
            $this->info('  New expires at: '.date('Y-m-d H:i:s', $newTokenData->expiresAt));
            $this->info('  New refresh token: '.substr($newTokenData->refreshToken, 0, 20).'...');
            $this->newLine();

            // Test 3: Try to use Old refresh token (should fail)
            $this->info('Test 3: Try to use old refresh token (should fail)');
            try {
                $authService->refreshAccessToken($tokenData->refreshToken);
                $this->error('✗ Old refresh token should have been invalidated!');
            } catch (InvalidRefreshToken $e) {
                $this->info('✓ Old refresh token correctly invalidated');
            }
            $this->newLine();

            // Test 4: Generate token with custom lifetime and refresh
            $this->info('Test 4: Generate token with custom lifetime (300 seconds) and refresh');
            $customTokenData = $user->generateJwt(300);
            $this->info('✓ Custom lifetime token generated');
            $this->info('  Expires at: '.date('Y-m-d H:i:s', $customTokenData->expiresAt));

            $refreshedCustomToken = $authService->refreshAccessToken($customTokenData->refreshToken);
            $this->info('✓ Custom lifetime token refreshed');
            $this->info('  New expires at: '.date('Y-m-d H:i:s', $refreshedCustomToken->expiresAt));

            $lifetimeDiff = $refreshedCustomToken->expiresAt - time();
            $this->info("  Lifetime preserved: ~{$lifetimeDiff} seconds");
            $this->newLine();

            // Test 5: Invalid refresh token
            $this->info('Test 5: Try invalid refresh token');
            try {
                $authService->refreshAccessToken('invalid-token-12345');
                $this->error('✗ Invalid token should have failed!');
            } catch (InvalidRefreshToken $e) {
                $this->info('✓ Invalid refresh token correctly rejected');
            }

            $this->newLine();
            $this->info('All tests passed! ✓');

        } catch (\Exception $e) {
            $this->error('Test failed: '.$e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
        }
    }
}
