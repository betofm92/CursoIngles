<?php

namespace Tests\Unit\Requests\Admin;

use App\Http\Requests\Admin\StoreBookRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreBookRequestTest extends TestCase
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

        $adminRequest = StoreBookRequest::create('/admin/libros', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);

        $teacherRequest = StoreBookRequest::create('/admin/libros', 'POST');
        $teacherRequest->setUserResolver(fn () => $teacher);

        $this->assertTrue($adminRequest->authorize());
        $this->assertFalse($teacherRequest->authorize());
    }

    public function test_rules_validate_required_fields_and_student_array(): void
    {
        $student = User::factory()->create();
        $request = new StoreBookRequest();

        $valid = Validator::make([
            'code' => 'BOOK_REQ_01',
            'name' => 'Libro Request',
            'student_ids' => [$student->id],
        ], $request->rules());
        $this->assertFalse($valid->fails());

        $invalid = Validator::make([
            'code' => '',
            'name' => '',
            'student_ids' => [999999, 999999],
        ], $request->rules());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('code', $invalid->errors()->toArray());
        $this->assertArrayHasKey('name', $invalid->errors()->toArray());
        $this->assertArrayHasKey('student_ids.0', $invalid->errors()->toArray());
        $this->assertArrayHasKey('student_ids.1', $invalid->errors()->toArray());
    }
}
