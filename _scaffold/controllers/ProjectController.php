<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur de brouillon (Scaffold) pour la gestion des Projets.
 * Permet de créer, lire, mettre à jour et supprimer des projets.
 */
class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste des projets avec filtres et pagination.
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $query = Project::with('client', 'expenses')
            ->when($user->hasRole('chef_projet'), fn ($q) =>
                $q->where('created_by', $user->id)
            );

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $projects = $query->latest()->paginate(15)->withQueryString()
            ->through(fn ($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'client'          => $p->client?->name,
                'status'          => $p->status,
                'budget'          => $p->budget,
                'total_expenses'  => $p->total_expenses,
                'gross_gain'      => $p->gross_gain,
                'profitability'   => $p->profitability_rate,
                'is_profitable'   => $p->is_profitable,
                'start_date'      => $p->start_date->format('d/m/Y'),
                'planned_end_date'=> $p->planned_end_date->format('d/m/Y'),
            ]);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'clients'  => Client::orderBy('name')->get(['id', 'name']),
            'filters'  => $request->only(['status', 'client_id', 'year', 'search']),
            'years'    => Project::selectRaw('YEAR(start_date) as year')
                ->distinct()->orderBy('year', 'desc')->pluck('year'),
        ]);
    }

    /**
     * Affiche le formulaire de création d'un projet.
     * @return Response
     */
    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('Projects/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Enregistre un nouveau projet en base de données.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'client_id'         => 'required|exists:clients,id',
            'description'       => 'nullable|string',
            'start_date'        => 'required|date',
            'planned_end_date'  => 'required|date|after_or_equal:start_date',
            'budget'            => 'required|numeric|min:0',
            'status'            => 'required|in:en_cours,termine,en_pause',
            'supplier_contact'  => 'nullable|string|max:255',
        ]);

        $project = Project::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        activity()->causedBy(Auth::user())
            ->performedOn($project)
            ->log('Projet créé');

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projet créé avec succès.');
    }

    /**
     * Affiche les détails d'un projet spécifique.
     * @param Project $project
     * @return Response
     */
    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load([
            'client',
            'expenses.category',
            'expenses.creator',
            'creator',
        ]);

        // Dépenses groupées par catégorie
        $expensesByCategory = $project->expenses
            ->groupBy('category.name')
            ->map(fn ($expenses, $name) => [
                'name'  => $name,
                'color' => $expenses->first()->category->color,
                'total' => $expenses->sum('amount'),
                'count' => $expenses->count(),
            ])->values();

        return Inertia::render('Projects/Show', [
            'project'             => array_merge($project->toArray(), [
                'total_expenses'   => $project->total_expenses,
                'gross_gain'       => $project->gross_gain,
                'profitability'    => $project->profitability_rate,
                'is_profitable'    => $project->is_profitable,
            ]),
            'expenses_by_category' => $expensesByCategory,
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un projet.
     * @param Project $project
     * @return Response
     */
    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Projects/Edit', [
            'project' => $project->load('client'),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Met à jour les informations d'un projet.
     * @param Request $request
     * @param Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'client_id'         => 'required|exists:clients,id',
            'description'       => 'nullable|string',
            'start_date'        => 'required|date',
            'planned_end_date'  => 'required|date|after_or_equal:start_date',
            'actual_end_date'   => 'nullable|date|after_or_equal:start_date',
            'budget'            => 'required|numeric|min:0',
            'status'            => 'required|in:en_cours,termine,en_pause',
            'supplier_contact'  => 'nullable|string|max:255',
        ]);

        $project->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        activity()->causedBy(Auth::user())
            ->performedOn($project)
            ->log('Projet modifié');

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projet mis à jour.');
    }

    /**
     * Supprime un projet s'il ne possède pas de dépenses.
     * @param Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        if ($project->hasPendingExpenses()) {
            return back()->withErrors([
                'delete' => 'Impossible de supprimer un projet ayant des dépenses enregistrées.'
            ]);
        }

        activity()->causedBy(Auth::user())
            ->performedOn($project)
            ->log('Projet supprimé');

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Projet supprimé.');
    }
}
