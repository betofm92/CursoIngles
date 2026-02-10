<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_teacher_and_student(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Profesora Nueva',
                'email' => 'profe.nueva@example.test',
                'role' => 'profesor',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        $teacher = User::where('email', 'profe.nueva@example.test')->first();
        $this->assertNotNull($teacher);
        $this->assertTrue($teacher->hasRole('profesor'));

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Estudiante Nueva',
                'email' => 'estudiante.nueva@example.test',
                'role' => 'estudiante',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        $student = User::where('email', 'estudiante.nueva@example.test')->first();
        $this->assertNotNull($student);
        $this->assertTrue($student->hasRole('estudiante'));
    }

    public function test_admin_can_update_managed_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $managedUser = User::factory()->create([
            'name' => 'Usuario Original',
            'email' => 'original@example.test',
        ]);
        $managedUser->assignRole('estudiante');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $managedUser), [
                'name' => 'Usuario Editado',
                'email' => 'editado@example.test',
                'role' => 'profesor',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect();

        $managedUser->refresh();
        $this->assertSame('Usuario Editado', $managedUser->name);
        $this->assertSame('editado@example.test', $managedUser->email);
        $this->assertTrue($managedUser->hasRole('profesor'));
        $this->assertFalse($managedUser->hasRole('estudiante'));
    }

    public function test_admin_can_delete_managed_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $managedUser = User::factory()->create();
        $managedUser->assignRole('profesor');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $managedUser))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $managedUser->id]);
    }

    public function test_admin_cannot_edit_or_delete_admin_accounts(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $otherAdmin), [
                'name' => 'Otro Admin',
                'email' => $otherAdmin->email,
                'role' => 'profesor',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $otherAdmin))
            ->assertForbidden();
    }
}
