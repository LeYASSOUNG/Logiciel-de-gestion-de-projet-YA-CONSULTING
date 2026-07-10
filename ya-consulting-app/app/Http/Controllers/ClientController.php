<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientInvitationMail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur de gestion des Clients.
 *
 * Gère les opérations CRUD sur les clients de YA CONSULTING.
 * Un client est une entité associée à un ou plusieurs projets.
 * Les actions sont protégées par ClientPolicy.
 */
class ClientController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste paginée des clients avec recherche.
     * Accessible à tous les rôles (admin, chef_projet, collaborateur).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query();

        // Recherche textuelle sur le nom, la société ou l'email de contact
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('company', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_email', 'like', '%' . $request->search . '%');
            });
        }

        $clients = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString() // Préserve les filtres dans les liens de pagination
            ->through(fn ($client) => [
                'id'              => $client->id,
                'name'            => $client->name,
                'contact_email'   => $client->contact_email,
                'contact_phone'   => $client->contact_phone,
                'company'         => $client->company,
                'address'         => $client->address,
                'notes'           => $client->notes,
                'invitation_link' => URL::signedRoute('client.register', ['client' => $client->id]),
            ]);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Crée un nouveau client après validation.
     * Accessible aux admin et chefs de projet.
     * L'action est journalisée dans le log d'activité.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Client::class);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'company'       => 'nullable|string|max:255',
            'address'       => 'nullable|string',
            'notes'         => 'nullable|string',
        ]);

        $client = Client::create($validated);

        // Journalisation : enregistre qui a créé ce client et quand
        activity()->causedBy(Auth::user())
            ->performedOn($client)
            ->log('Client créé');

        return redirect()->route('clients.index')
            ->with('success', 'Client créé avec succès.');
    }

    /**
     * Met à jour les informations d'un client existant.
     * Accessible aux admin et chefs de projet.
     * L'action est journalisée dans le log d'activité.
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'company'       => 'nullable|string|max:255',
            'address'       => 'nullable|string',
            'notes'         => 'nullable|string',
        ]);

        $client->update($validated);

        // Journalisation de la modification
        activity()->causedBy(Auth::user())
            ->performedOn($client)
            ->log('Client mis à jour');

        return redirect()->route('clients.index')
            ->with('success', 'Client mis à jour avec succès.');
    }

    /**
     * Supprime un client.
     * PROTÉGÉ : Impossible de supprimer un client s'il possède des projets actifs.
     * Cette règle garantit l'intégrité des données financières liées.
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        // Vérification de l'intégrité référentielle : bloquer si des projets existent
        if ($client->projects()->exists()) {
            return back()->withErrors([
                'delete' => 'Impossible de supprimer un client ayant des projets en cours.'
            ]);
        }

        // Journalisation avant la suppression (l'enregistrement sera soft-deleted)
        activity()->causedBy(Auth::user())
            ->performedOn($client)
            ->log('Client supprimé');

        $client->delete(); // Suppression logique (SoftDeletes)

        return redirect()->route('clients.index')
            ->with('success', 'Client supprimé avec succès.');
    }

    /**
     * Envoie le lien magique d'invitation par e-mail au client.
     */
    public function sendInvitation(Client $client)
    {
        $this->authorize('update', $client); // Vérifier si l'utilisateur a le droit de gérer ce client

        if (!$client->contact_email) {
            return back()->withErrors(['email' => "Ce client n'a pas d'adresse e-mail renseignée."]);
        }

        $invitationLink = URL::signedRoute('client.register', ['client' => $client->id]);

        try {
            Mail::to($client->contact_email)->send(new ClientInvitationMail($client, $invitationLink));

            // On peut aussi logger cette action
            activity()->causedBy(Auth::user())
                ->performedOn($client)
                ->log('Invitation envoyée par e-mail');

            return back()->with('success', "L'invitation a été envoyée avec succès à {$client->contact_email}.");
        } catch (\Exception $e) {
            // Retourner l'erreur exacte pour aider au débogage en production
            return back()->withErrors(['email' => "Erreur d'envoi d'e-mail : " . $e->getMessage()]);
        }
    }
}
