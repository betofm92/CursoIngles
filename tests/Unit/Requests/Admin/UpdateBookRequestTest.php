<?php

namespace Tests\Unit\Requests\Admin;

use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateBookRequestTest extends TestCase
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

        $adminRequest = UpdateBookRequest::create('/admin/libros/1', 'PUT');
        $adminRequest->setUserResolver(fn () => $admin);

        $studentRequest = UpdateBookRequest::create('/admin/libros/1', 'PUT');
        $studentRequest->setUserResolver(fn () => $student);

        $this->assertTrue($adminRequest->authorize());
        $this->assertFalse($studentRequest->authorize());
    }

    public function test_rules_allow_current_code_and_reject_existing_code_from_other_book(): void
    {
        $book = Book::create([
            'code' => 'BOOK_UPDATE_01',
            'name' => 'Book Update 1',
        ]);

        $otherBook = Book::create([
            'code' => 'BOOK_UPDATE_02',
            'name' => 'Book Update 2',
        ]);

        $student = User::factory()->create();
        $request = new UpdateBookRequest();
        $request->setRouteResolver(fn () => new class($book)
        {
            public function __construct(private Book $book) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'book' ? $this->book : $default;
            }
        });

        $valid = Validator::make([
            'code' => 'BOOK_UPDATE_01',
            'name' => 'Book Updated',
            'student_ids' => [$student->id],
        ], $request->rules());
        $this->assertFalse($valid->fails());

        $invalid = Validator::make([
            'code' => $otherBook->code,
            'name' => 'Book Updated',
            'student_ids' => [$student->id],
        ], $request->rules());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('code', $invalid->errors()->toArray());
    }
}
