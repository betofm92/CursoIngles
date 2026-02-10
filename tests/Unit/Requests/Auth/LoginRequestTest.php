<?php

namespace Tests\Unit\Requests\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new LoginRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_rules_require_email_and_password(): void
    {
        $request = new LoginRequest();

        $valid = Validator::make([
            'email' => 'user@example.test',
            'password' => 'secret-password',
        ], $request->rules());
        $this->assertFalse($valid->fails());

        $invalid = Validator::make([
            'email' => 'invalid-email',
            'password' => '',
        ], $request->rules());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('email', $invalid->errors()->toArray());
        $this->assertArrayHasKey('password', $invalid->errors()->toArray());
    }

    public function test_throttle_key_is_lowercased_and_transliterated(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'Usér@Example.TEST',
        ]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertSame('user@example.test|127.0.0.1', $request->throttleKey());
    }

    public function test_ensure_is_not_rate_limited_throws_after_five_attempts(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'locked@example.test',
        ]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $key = $request->throttleKey();

        RateLimiter::clear($key);
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key);
        }

        $this->expectException(ValidationException::class);
        $request->ensureIsNotRateLimited();
    }
}

