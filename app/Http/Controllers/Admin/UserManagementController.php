<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManagedUserRequest;
use App\Http\Requests\Admin\UpdateManagedUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const MANAGEABLE_ROLES = ['profesor', 'estudiante'];

    public function index(): View
    {
        return view('admin.users.index', [
            'managedUsers' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', self::MANAGEABLE_ROLES))
                ->with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'created_at']),
            'roleOptions' => [
                'profesor' => 'Profesor',
                'estudiante' => 'Estudiante',
            ],
        ]);
    }

    public function store(StoreManagedUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['role']);
        });

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(UpdateManagedUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureManageable($user);

        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (! empty($validated['password'])) {
                $payload['password'] = Hash::make($validated['password']);
            }

            $user->update($payload);
            $user->syncRoles([$validated['role']]);
        });

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureManageable($user);

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    private function ensureManageable(User $user): void
    {
        if (! $user->hasAnyRole(self::MANAGEABLE_ROLES)) {
            abort(403);
        }
    }
}
