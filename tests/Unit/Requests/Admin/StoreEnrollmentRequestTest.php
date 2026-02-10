<?php

namespace Tests\Unit\Requests\Admin;

use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreEnrollmentRequestTest extends TestCase
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

        $adminRequest = StoreEnrollmentRequest::create('/admin/horarios/1/enrollments', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);

        $teacherRequest = StoreEnrollmentRequest::create('/admin/horarios/1/enrollments', 'POST');
        $teacherRequest->setUserResolver(fn () => $teacher);

        $this->assertTrue($adminRequest->authorize());
        $this->assertFalse($teacherRequest->authorize());
    }

    public function test_rules_require_valid_student_id(): void
    {
        $student = User::factory()->create();
        $request = new StoreEnrollmentRequest();

        $valid = Validator::make(
            ['student_id' => $student->id],
            $request->rules(),
        );
        $this->assertFalse($valid->fails());

        $invalid = Validator::make(
            ['student_id' => 999999],
            $request->rules(),
        );
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('student_id', $invalid->errors()->toArray());
    }
}
