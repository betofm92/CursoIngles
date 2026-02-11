<?php

namespace Tests\Unit\Controllers\Admin;

use App\Http\Controllers\Admin\BookController;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_index_returns_books_with_students_and_student_catalog(): void
    {
        $studentA = User::factory()->create(['name' => 'Ana Book']);
        $studentA->assignRole('estudiante');

        $studentB = User::factory()->create(['name' => 'Bruno Book']);
        $studentB->assignRole('estudiante');

        $teacher = User::factory()->create(['name' => 'Teacher Book']);
        $teacher->assignRole('profesor');

        $book = Book::create([
            'code' => 'BOOK-CONT-01',
            'name' => 'Book Controller',
        ]);
        $book->students()->sync([$studentA->id]);

        $view = (new BookController())->index();
        $data = $view->getData();

        $this->assertSame('admin.books.index', $view->name());
        $this->assertEqualsCanonicalizing([$book->id], $data['books']->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$studentA->id, $studentB->id],
            $data['students']->pluck('id')->all()
        );
        $this->assertNotContains($teacher->id, $data['students']->pluck('id')->all());
    }
}
