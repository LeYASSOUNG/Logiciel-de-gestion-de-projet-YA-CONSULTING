<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $query = Project::with('client', 'expenses')
            ->when(Auth::user()->hasRole('chef_projet'), fn ($q) =>
                $q->where('created_by', Auth::id())
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
            'years'    => Project::whereNotNull('start_date')->get()
                ->map(fn($p) => $p->start_date->year)->unique()->sortDesc()->values(),
            'can'      => [
                'create' => Auth::user()->hasAnyRole(['admin', 'chef_projet']),
                'edit'   => Auth::user()->hasAnyRole(['admin', 'chef_projet']),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('Projects/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'client_id'         => 'required|exists:clients,id',
            'description'       => 'nullable|string',
            'start_date'        => 'required|date',
            'planned_end_date'  => 'required|date|after_or_equal:start_date',
            'budget_labor'      => 'required|numeric|min:0',
            'budget_material'   => 'required|numeric|min:0',
            'budget_transport'  => 'required|numeric|min:0',
            'budget_other'      => 'required|numeric|min:0',
            'status'            => 'required|in:en_cours,termine,en_pause',
            'supplier_contact'  => 'nullable|string|max:255',
        ]);

        $validated['budget'] = (float)$validated['budget_labor'] + (float)$validated['budget_material'] + (float)$validated['budget_transport'] + (float)$validated['budget_other'];

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
                'expenses_labor'     => $project->expenses_labor,
                'expenses_material'  => $project->expenses_material,
                'expenses_transport' => $project->expenses_transport,
                'expenses_other'     => $project->expenses_other,
                'has_expenses'       => $project->hasPendingExpenses(),
            ]),
            'expenses_by_category' => $expensesByCategory,
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Projects/Edit', [
            'project' => array_merge($project->load('client')->toArray(), [
                'has_expenses' => $project->hasPendingExpenses(),
            ]),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

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
            'budget_labor'      => 'required|numeric|min:0',
            'budget_material'   => 'required|numeric|min:0',
            'budget_transport'  => 'required|numeric|min:0',
            'budget_other'      => 'required|numeric|min:0',
            'status'            => 'required|in:en_cours,termine,en_pause',
            'supplier_contact'  => 'nullable|string|max:255',
        ]);

        // Contrainte de modification si dépenses existantes
        if ($project->hasPendingExpenses()) {
            if (
                $request->client_id != $project->client_id ||
                \Illuminate\Support\Carbon::parse($request->start_date)->toDateString() != $project->start_date->toDateString() ||
                (float)$request->budget_labor != (float)$project->budget_labor ||
                (float)$request->budget_material != (float)$project->budget_material ||
                (float)$request->budget_transport != (float)$project->budget_transport ||
                (float)$request->budget_other != (float)$project->budget_other
            ) {
                return back()->withErrors([
                    'budget' => 'Impossible de modifier le budget, le client ou la date de début pour un projet ayant des dépenses enregistrées.'
                ]);
            }
        }

        $validated['budget'] = (float)$validated['budget_labor'] + (float)$validated['budget_material'] + (float)$validated['budget_transport'] + (float)$validated['budget_other'];

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
