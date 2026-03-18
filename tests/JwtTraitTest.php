<?php

use Korioinc\JwtAuth\Data\Jwt;
use Korioinc\JwtAuth\Tests\Fixtures\TestJwtUser;

it('generates a jwt with the user identifier and custom props', function () {
    config()->set('jwt-auth.secret_key', 'test-secret-key');
    config()->set('jwt-auth.access_token.lifetime', 120);
    config()->set('jwt-auth.refresh_token.lifetime', 300);

    $user = new TestJwtUser(123, [
        'role' => 'admin',
        'team' => 'backend',
    ]);

    $tokenData = $user->generateJwt();
    $jwt = Jwt::decode($tokenData->accessToken);

    expect($tokenData->refreshToken)->toBeString()->not->toBeEmpty()
        ->and($tokenData->expiresAt)->toBeInt()->toBeGreaterThan(time())
        ->and($jwt->payload->sub)->toBe('123')
        ->and($jwt->payload->props)->toBe([
            'role' => 'admin',
            'team' => 'backend',
        ])
        ->and($jwt->payload->exp - $jwt->payload->iat)->toBe(120);
});
