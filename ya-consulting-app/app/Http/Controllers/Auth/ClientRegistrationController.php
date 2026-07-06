<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ClientRegistrationController extends Controller
{
    /**
     * Affiche le formulaire d'inscription pour un client spécifique.
     * Cette route est protégée par un lien signé (middleware 'signed').
     */
    public function create(Request $request, Client $client): Response
    {
        // On passe les infos du client à la vue Vue.js
        return Inertia::render('Auth/ClientRegister', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
            ],
            // On peut pré-remplir l'email si le client a un email de contact
            'defaultEmail' => $client->contact_email,
        ]);
    }

    /**
     * Traite la soumission du formulaire d'inscription.
     */
    public function store(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // Création de l'utilisateur avec le rôle client
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'client_id' => $client->id,
        ]);

        // Assigner le rôle "client" (Spatie Permissions)
        $user->assignRole('client');

        // Connexion automatique après inscription
        Auth::login($user);

        // Redirection vers le tableau de bord
        return redirect()->route('dashboard')->with('success', 'Bienvenue sur votre espace client !');
    }
}
