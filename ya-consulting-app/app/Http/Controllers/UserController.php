<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur de gestion des Utilisateurs.
 *
 * Ce contrôleur est EXCLUSIVEMENT réservé aux Administrateurs.
 * Il permet de créer, modifier et supprimer des comptes utilisateurs,
 * ainsi que d'affecter des rôles (admin, chef_projet, collaborateur).
 *
 * La protection d'accès est assurée par le middleware 'role:admin'
 * défini dans web.php ET par $this->authorize() dans chaque méthode.
 */
class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste paginée des utilisateurs avec leurs rôles.
     * Inclut une recherche par nom ou email.
     */
    public function index(Request $request): Response
    {
        $this->authorize('manage', User::class);

        // Chargement eager des rôles pour éviter les requêtes N+1
        $query = User::with('roles');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Transformation des données pour le frontend : on n'expose que le nécessaire
        $users = $query->paginate(15)->withQueryString()
            ->through(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->roles->first()?->name, // Un seul rôle par utilisateur
                'created_at' => $u->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'roles'   => Role::orderBy('name')->pluck('name'), // Liste des rôles disponibles
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Crée un nouveau compte utilisateur et lui assigne un rôle.
     *
     * Le mot de passe est hashé avant stockage (règle Laravel).
     * Le rôle est assigné via Spatie Laravel Permission.
     */
    public function store(Request $request)
    {
        $this->authorize('manage', User::class);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email', // L'email doit être unique
            // Règles standard : minimum 8 caractères
            'password' => ['required', 'confirmed', 'min:8'],
            'role'     => 'required|exists:roles,name',      // Le rôle doit exister en BDD
        ]);

        // Création du compte avec le mot de passe hashé (jamais en clair en BDD)
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assignation du rôle via Spatie Permission
        $user->assignRole($validated['role']);

        // Journalisation de la création
        activity()->causedBy(Auth::user())
            ->performedOn($user)
            ->log('Utilisateur créé');

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Met à jour les informations et le rôle d'un utilisateur.
     *
     * Note : le mot de passe n'est pas modifiable ici (géré dans ProfileController).
     * syncRoles() remplace l'ancien rôle par le nouveau.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('manage', User::class);

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            // L'email doit être unique SAUF pour l'utilisateur en cours de modification
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role'  => 'required|exists:roles,name',
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        // syncRoles() retire tous les anciens rôles et assigne le nouveau
        $user->syncRoles($validated['role']);

        // Journalisation de la modification
        activity()->causedBy(Auth::user())
            ->performedOn($user)
            ->log('Utilisateur mis à jour');

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Supprime définitivement un compte utilisateur.
     *
     * PROTECTION : Un administrateur ne peut pas supprimer SON PROPRE compte.
     * Cette sécurité empêche de se retrouver sans administrateur dans le système.
     */
    public function destroy(User $user)
    {
        $this->authorize('manage', User::class);

        // Règle de sécurité : interdire l'auto-suppression
        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'delete' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ]);
        }

        // Journalisation avant la suppression
        activity()->causedBy(Auth::user())
            ->performedOn($user)
            ->log('Utilisateur supprimé');

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }
}
