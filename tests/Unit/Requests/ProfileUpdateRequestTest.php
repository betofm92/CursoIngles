<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_rules_allow_same_email_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'self@example.test',
        ]);

        $request = new ProfileUpdateRequest();
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name' => 'Self User',
            'email' => 'self@example.test',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_reject_email_used_by_another_user(): void
    {
        $currentUser = User::factory()->create([
            'email' => 'current@example.test',
        ]);
        User::factory()->create([
            'email' => 'existing@example.test',
        ]);

        $request = new ProfileUpdateRequest();
        $request->setUserResolver(fn () => $currentUser);

        $validator = Validator::make([
            'name' => 'Current User',
            'email' => 'existing@example.test',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_rules_require_lowercase_email_format(): void
    {
        $user = User::factory()->create();

        $request = new ProfileUpdateRequest();
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name' => 'User Uppercase',
            'email' => 'UPPERCASE@EXAMPLE.TEST',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }
}

