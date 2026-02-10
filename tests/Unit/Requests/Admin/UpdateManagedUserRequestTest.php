<?php

namespace Tests\Unit\Requests\Admin;

use App\Http\Requests\Admin\UpdateManagedUserRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateManagedUserRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorize_allows_only_admin_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $adminRequest = UpdateManagedUserRequest::create('/admin/usuarios/1', 'PUT');
        $adminRequest->setUserResolver(fn () => $admin);

        $teacherRequest = UpdateManagedUserRequest::create('/admin/usuarios/1', 'PUT');
        $teacherRequest->setUserResolver(fn () => $teacher);

        $this->assertTrue($adminRequest->authorize());
        $this->assertFalse($teacherRequest->authorize());
    }

    public function test_rules_allow_current_email_when_updating_same_user(): void
    {
        $managedUser = User::factory()->create([
            'email' => 'managed@example.test',
        ]);

        $request = new UpdateManagedUserRequest();
        $request->setRouteResolver(fn () => new class($managedUser)
        {
            public function __construct(private User $user) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'user' ? $this->user : $default;
            }
        });

        $validator = Validator::make([
            'name' => 'Managed User',
            'email' => 'managed@example.test',
            'role' => 'estudiante',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_reject_email_used_by_other_user(): void
    {
        $managedUser = User::factory()->create([
            'email' => 'managed@example.test',
        ]);
        User::factory()->create([
            'email' => 'existing@example.test',
        ]);

        $request = new UpdateManagedUserRequest();
        $request->setRouteResolver(fn () => new class($managedUser)
        {
            public function __construct(private User $user) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'user' ? $this->user : $default;
            }
        });

        $validator = Validator::make([
            'name' => 'Managed User',
            'email' => 'existing@example.test',
            'role' => 'profesor',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_rules_validate_role_and_optional_password(): void
    {
        $managedUser = User::factory()->create();

        $request = new UpdateManagedUserRequest();
        $request->setRouteResolver(fn () => new class($managedUser)
        {
            public function __construct(private User $user) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'user' ? $this->user : $default;
            }
        });

        $invalidRole = Validator::make([
            'name' => 'Managed User',
            'email' => 'managed+new@example.test',
            'role' => 'admin',
        ], $request->rules());
        $this->assertTrue($invalidRole->fails());
        $this->assertArrayHasKey('role', $invalidRole->errors()->toArray());

        $invalidPassword = Validator::make([
            'name' => 'Managed User',
            'email' => 'managed+new2@example.test',
            'role' => 'estudiante',
            'password' => 'short',
            'password_confirmation' => 'short',
        ], $request->rules());
        $this->assertTrue($invalidPassword->fails());
        $this->assertArrayHasKey('password', $invalidPassword->errors()->toArray());

        $validWithoutPassword = Validator::make([
            'name' => 'Managed User',
            'email' => 'managed+new3@example.test',
            'role' => 'profesor',
        ], $request->rules());
        $this->assertFalse($validWithoutPassword->fails());
    }
}

