<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_book_has_many_students_relationship(): void
    {
        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $book = Book::create([
            'code' => 'BOOK-U1',
            'name' => 'Libro Unitario',
        ]);

        $book->students()->attach($student->id);
        $book->refresh()->load('students');

        $this->assertCount(1, $book->students);
        $this->assertSame($student->id, $book->students->first()?->id);
    }
}
