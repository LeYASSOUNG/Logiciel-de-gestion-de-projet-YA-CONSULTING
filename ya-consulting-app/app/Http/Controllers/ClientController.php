<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('company', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_email', 'like', '%' . $request->search . '%');
            });
        }

        $clients = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

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

        activity()->causedBy(Auth::user())
            ->performedOn($client)
            ->log('Client créé');

        return redirect()->route('clients.index')
            ->with('success', 'Client créé avec succès.');
    }

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

        activity()->causedBy(Auth::user())
            ->performedOn($client)
            ->log('Client mis à jour');

        return redirect()->route('clients.index')
            ->with('success', 'Client mis à jour avec succès.');
    }

    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        if ($client->projects()->exists()) {
            return back()->withErrors([
                'delete' => 'Impossible de supprimer un client ayant des projets en cours.'
            ]);
        }

        activity()->causedBy(Auth::user())
            ->performedOn($client)
            ->log('Client supprimé');

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client supprimé avec succès.');
    }
}
