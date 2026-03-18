<?php

namespace Korioinc\JwtAuth\Commands;

use Illuminate\Console\Command;
use Korioinc\JwtAuth\Data\Jwt;
use Korioinc\JwtAuth\Data\JwtHeader;
use Korioinc\JwtAuth\Data\JwtPayload;
use Korioinc\JwtAuth\Enums\AlgorithmEnum;
use Korioinc\JwtAuth\Services\AuthService;
use Korioinc\JwtAuth\Utils\Crypto;

class JwtTestCommand extends Command
{
    protected $signature = 'jwt:test {--token= : JWT token to decode and verify} {--jwt-io : Generate a jwt.io compatible test token}';

    protected $description = 'Test JWT token generation and verification';

    public function handle(): int
    {
        if ($this->option('jwt-io')) {
            $this->generateJwtIoTestToken();

            return Command::SUCCESS;
        }

        $token = $this->option('token');

        if ($token) {
            $this->testDecodeToken($token);
        } else {
            $this->testGenerateToken();
        }

        return Command::SUCCESS;
    }

    private function generateJwtIoTestToken(): void
    {
        $this->info('Generating jwt.io compatible test token...');

        $secretKey = config('jwt-auth.secret_key');
        if (! $secretKey) {
            $this->error('JWT_SECRET_KEY is not set in your .env file!');

            return;
        }

        // Create a simple test token
        $header = new JwtHeader(AlgorithmEnum::HS256, null, 'JWT');
        $payload = new JwtPayload(
            jti: 'test-123',
            iat: time(),
            exp: time() + 3600,
            sub: '1',
            iss: 'laravel-jwt-auth',
            aud: 'jwt.io'
        );

        $jwt = new Jwt($header, $payload);
        $token = $jwt->encode();

        $this->info("\nGenerated Token:");
        $this->line($token);

        $this->info("\nTo verify on jwt.io:");
        $this->line('1. Go to https://jwt.io');
        $this->line('2. Paste the token above');
        $this->line("3. In the 'Verify Signature' section, paste your secret key:");
        $this->line('   '.$secretKey);
        $this->line("4. Make sure 'secret base64 encoded' is UNCHECKED");

        // Decode to show parts
        $parts = explode('.', $token);
        $this->info("\nToken Parts:");
        $this->line('Header:  '.$parts[0]);
        $this->line('Payload: '.$parts[1]);
        $this->line('Signature: '.$parts[2]);
    }

    private function testDecodeToken(string $token): void
    {
        $this->info('Decoding JWT token...');

        try {
            // Decode token
            $jwt = Jwt::decode($token);

            $this->info('Token Structure:');
            $this->line('Header Algorithm: '.$jwt->header->algorithm->value);
            $this->line('Header Type: '.($jwt->header->typ ?? 'JWT'));

            $this->info("\nPayload:");
            $this->line('Subject (user): '.$jwt->payload->sub);
            $this->line('JWT ID: '.$jwt->payload->jti);
            $this->line('Issued at: '.date('Y-m-d H:i:s', $jwt->payload->iat));
            $this->line('Expires at: '.date('Y-m-d H:i:s', $jwt->payload->exp));

            // Validate token
            $this->info("\nValidating signature...");
            app(AuthService::class)->validateAccessToken($jwt);

            $this->info('✓ Token is valid!');

            // Check expiration
            $remaining = $jwt->payload->exp - time();
            if ($remaining > 0) {
                $this->info('Token expires in '.round($remaining / 60, 1).' minutes');
            } else {
                $this->warn('Token has expired '.round(abs($remaining) / 60, 1).' minutes ago');
            }

        } catch (\Exception $e) {
            $this->error('Failed to decode/verify token: '.$e->getMessage());
        }
    }

    private function testGenerateToken(): void
    {
        $this->info('Testing JWT token generation with current config...');

        $secretKey = config('jwt-auth.secret_key');
        $algorithm = config('jwt-auth.algorithm', 'HS256');

        if (! $secretKey) {
            $this->error('JWT_SECRET_KEY is not set in your .env file!');

            return;
        }

        $this->line('Secret Key: '.substr($secretKey, 0, 10).'...'.substr($secretKey, -10));
        $this->line('Algorithm: '.$algorithm);
        $this->line('Token Lifetime: '.config('jwt-auth.access_token.lifetime').' minutes');

        // Test signature generation
        $this->info("\nTesting signature generation...");
        $crypto = app(Crypto::class);
        $testPayload = 'test.payload';
        $signature = $crypto->encode(AlgorithmEnum::from($algorithm), $testPayload);

        $this->line('Test payload: '.$testPayload);
        $this->line('Generated signature: '.$signature);

        // Verify the signature
        try {
            $crypto->verify(AlgorithmEnum::from($algorithm), $testPayload, $signature);
            $this->info('✓ Signature verification successful!');
        } catch (\Exception) {
            $this->error('✗ Signature verification failed!');
        }

        $this->info("\nTo generate a token for a user, use: \$user->generateJwt()");
    }
}
