<?php

namespace Tests\Unit\Requests\Admin;

use App\Http\Requests\Admin\StoreManagedUserRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreManagedUserRequestTest extends TestCase
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

        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $adminRequest = StoreManagedUserRequest::create('/admin/usuarios', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);

        $studentRequest = StoreManagedUserRequest::create('/admin/usuarios', 'POST');
        $studentRequest->setUserResolver(fn () => $student);

        $this->assertTrue($adminRequest->authorize());
        $this->assertFalse($studentRequest->authorize());
    }

    public function test_rules_validate_required_fields_role_and_password_confirmation(): void
    {
        $request = new StoreManagedUserRequest();

        $valid = Validator::make([
            'name' => 'Usuario Gestionado',
            'email' => 'gestionado@example.test',
            'role' => 'profesor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $request->rules());
        $this->assertFalse($valid->fails());

        $invalidRole = Validator::make([
            'name' => 'Usuario Gestionado',
            'email' => 'otro@example.test',
            'role' => 'admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $request->rules());
        $this->assertTrue($invalidRole->fails());
        $this->assertArrayHasKey('role', $invalidRole->errors()->toArray());

        $invalidPasswordConfirmation = Validator::make([
            'name' => 'Usuario Gestionado',
            'email' => 'otro2@example.test',
            'role' => 'estudiante',
            'password' => 'password123',
            'password_confirmation' => 'diferente123',
        ], $request->rules());
        $this->assertTrue($invalidPasswordConfirmation->fails());
        $this->assertArrayHasKey('password', $invalidPasswordConfirmation->errors()->toArray());
    }

    public function test_rules_validate_unique_email(): void
    {
        User::factory()->create(['email' => 'duplicado@example.test']);
        $request = new StoreManagedUserRequest();

        $validator = Validator::make([
            'name' => 'Nuevo Usuario',
            'email' => 'duplicado@example.test',
            'role' => 'estudiante',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }
}
