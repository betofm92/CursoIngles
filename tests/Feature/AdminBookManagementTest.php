<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_create_book_with_students(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $studentA = User::factory()->create();
        $studentA->assignRole('estudiante');

        $studentB = User::factory()->create();
        $studentB->assignRole('estudiante');

        $this->actingAs($admin)
            ->get(route('admin.books.index'))
            ->assertOk()
            ->assertSee('Libros');

        $this->actingAs($admin)
            ->post(route('admin.books.store'), [
                'code' => 'book-feature-001',
                'name' => 'Libro Feature 1',
                'student_ids' => [$studentA->id, $studentB->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $book = Book::where('code', 'BOOK-FEATURE-001')->first();
        $this->assertNotNull($book);
        $this->assertDatabaseHas('book_student', [
            'book_id' => $book->id,
            'student_id' => $studentA->id,
        ]);
        $this->assertDatabaseHas('book_student', [
            'book_id' => $book->id,
            'student_id' => $studentB->id,
        ]);
    }

    public function test_admin_can_update_book_and_sync_students(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $studentA = User::factory()->create();
        $studentA->assignRole('estudiante');

        $studentB = User::factory()->create();
        $studentB->assignRole('estudiante');

        $book = Book::create([
            'code' => 'BOOK-FEATURE-UPDATE',
            'name' => 'Libro Original',
        ]);
        $book->students()->sync([$studentA->id]);

        $this->actingAs($admin)
            ->put(route('admin.books.update', $book), [
                'code' => 'book-feature-updated',
                'name' => 'Libro Actualizado',
                'student_ids' => [$studentB->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $book->refresh();
        $this->assertSame('BOOK-FEATURE-UPDATED', $book->code);
        $this->assertSame('Libro Actualizado', $book->name);
        $this->assertEqualsCanonicalizing([$studentB->id], $book->students()->pluck('users.id')->all());
    }

    public function test_admin_can_delete_book(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->create();
        $student->assignRole('estudiante');

        $book = Book::create([
            'code' => 'BOOK-FEATURE-DELETE',
            'name' => 'Libro Eliminar',
        ]);
        $book->students()->sync([$student->id]);

        $this->actingAs($admin)
            ->delete(route('admin.books.destroy', $book))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_student', [
            'book_id' => $book->id,
            'student_id' => $student->id,
        ]);
    }
}
