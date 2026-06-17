<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('manage', User::class);

        $query = User::with('roles');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->paginate(15)->withQueryString()
            ->through(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->roles->first()?->name,
                'created_at' => $u->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'roles'   => Role::orderBy('name')->pluck('name'),
            'filters' => $request->only(['search']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('manage', User::class);

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role'  => 'required|exists:roles,name',
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles($validated['role']);

        activity()->causedBy(Auth::user())
            ->performedOn($user)
            ->log('Utilisateur mis à jour');

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        $this->authorize('manage', User::class);

        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'delete' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ]);
        }

        activity()->causedBy(Auth::user())
            ->performedOn($user)
            ->log('Utilisateur supprimé');

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }
}
