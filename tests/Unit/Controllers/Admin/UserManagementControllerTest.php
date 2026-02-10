<?php

namespace Tests\Unit\Controllers\Admin;

use App\Http\Controllers\Admin\UserManagementController;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_index_returns_only_teacher_and_student_users(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('profesor');

        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $view = (new UserManagementController())->index();
        $data = $view->getData();

        $this->assertSame('admin.users.index', $view->name());
        $this->assertSame(
            ['profesor' => 'Profesor', 'estudiante' => 'Estudiante'],
            $data['roleOptions']
        );
        $this->assertEqualsCanonicalizing(
            [$teacher->id, $student->id],
            $data['managedUsers']->pluck('id')->all()
        );
        $this->assertNotContains($admin->id, $data['managedUsers']->pluck('id')->all());
    }
}

