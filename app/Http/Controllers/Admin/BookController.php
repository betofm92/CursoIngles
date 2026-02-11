<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        return view('admin.books.index', [
            'books' => Book::query()
                ->with(['students:id,name,email'])
                ->withCount('students')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'created_at']),
            'students' => User::role('estudiante')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $studentIds = $this->validatedStudentIds($validated['student_ids'] ?? []);

        DB::transaction(function () use ($validated, $studentIds): void {
            $book = Book::create([
                'code' => Str::upper($validated['code']),
                'name' => $validated['name'],
            ]);

            $book->students()->sync($studentIds);
        });

        return back()->with('success', 'Libro creado correctamente.');
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();
        $studentIds = $this->validatedStudentIds($validated['student_ids'] ?? []);

        DB::transaction(function () use ($book, $validated, $studentIds): void {
            $book->update([
                'code' => Str::upper($validated['code']),
                'name' => $validated['name'],
            ]);

            $book->students()->sync($studentIds);
        });

        return back()->with('success', 'Libro actualizado correctamente.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return back()->with('success', 'Libro eliminado correctamente.');
    }

    /**
     * @param array<int, int|string> $studentIds
     * @return array<int, int>
     */
    private function validatedStudentIds(array $studentIds): array
    {
        $uniqueIds = collect($studentIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        if ($uniqueIds->isEmpty()) {
            return [];
        }

        $validCount = User::role('estudiante')
            ->whereIn('id', $uniqueIds)
            ->count();

        if ($validCount !== $uniqueIds->count()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Uno o mas usuarios seleccionados no tienen rol de estudiante.',
            ]);
        }

        return $uniqueIds->all();
    }
}
