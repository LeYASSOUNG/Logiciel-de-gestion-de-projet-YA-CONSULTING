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
 * Contrôleur gérant l'ensemble de la logique métier liée aux Projets.
 * Permet de lister, créer, afficher, modifier et supprimer des projets.
 */
class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste des projets avec pagination, filtres et recherche.
     * Accessible par tous les rôles, mais les chefs de projet et les clients ne voient que leurs propres projets.
     *
     * @param Request $request La requête HTTP contenant d'éventuels filtres (status, client_id, year, search)
     * @return Response Vue Inertia (Projects/Index)
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Initialise la requête de base en chargeant les relations nécessaires pour éviter le problème N+1
        $query = Project::with(['client', 'expenses', 'payments'])
            ->when($user && $user->hasRole('chef_projet'), fn ($q) =>
                // Si l'utilisateur est un chef de projet, on filtre pour ne récupérer que les projets qu'il a créés
                $q->where('created_by', Auth::id())
            )
            ->when($user && $user->hasRole('client'), fn ($q) =>
                // Si l'utilisateur est un client, on ne montre que SES projets
                $q->where('client_id', Auth::user()->client_id)
            );

        // Application dynamique des filtres de recherche si présents dans la requête
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

        // Récupère les résultats paginés et transforme la collection
        // pour ne renvoyer que les données nécessaires au frontend
        $projects = $query->latest()->paginate(15)->withQueryString()
            ->through(fn (Project $p) => [
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
            'clients'  => ($user && $user->hasRole('client')) 
                ? Client::where('id', $user->client_id)->get(['id', 'name']) 
                : Client::orderBy('name')->get(['id', 'name']),
            'filters'  => $request->only(['status', 'client_id', 'year', 'search']),
            'years'    => Project::whereNotNull('start_date')->get()
                ->map(fn(Project $p) => $p->start_date->year)->unique()->sortDesc()->values(),
            'can'      => [
                'create' => $user && $user->hasAnyRole(['admin', 'chef_projet']),
                'edit'   => $user && $user->hasAnyRole(['admin', 'chef_projet']),
            ],
        ]);
    }

    /**
     * Affiche le formulaire de création d'un nouveau projet.
     *
     * @return Response Vue Inertia (Projects/Create)
     */
    public function create(): Response
    {
        // Vérifie via les Policies si l'utilisateur a le droit de créer un projet (Admin ou Chef de Projet)
        $this->authorize('create', Project::class);

        return Inertia::render('Projects/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Enregistre un nouveau projet dans la base de données.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse Redirige vers la vue détaillée du projet
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validated = $this->validateProjectRequest($request);

        // Calcul automatique du budget global à partir des budgets détaillés
        $validated['budget'] = (float)$validated['budget_labor']
            + (float)$validated['budget_material']
            + (float)$validated['budget_transport']
            + (float)$validated['budget_other'];
        $validated['initial_budget'] = $validated['budget'];

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
     * Affiche les détails complets d'un projet spécifique (Vue "Show").
     * Calcule la rentabilité, affiche les dépenses groupées par catégorie.
     *
     * @param Project $project Le projet injecté automatiquement par Laravel via le Route Model Binding
     * @return Response
     */
    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        // Chargement des relations (Eager Loading) pour éviter les requêtes N+1 dans la vue
        $project->load([
            'client',
            'expenses.category',
            'expenses.creator',
            'payments.creator',
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

        // Renvoie toutes les métriques de rentabilité et la répartition des dépenses
        return Inertia::render('Projects/Show', [
            'project'             => array_merge($project->toArray(), [
                'total_paid'       => $project->total_paid,
                'balance_due'      => $project->balance_due,
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

    /**
     * Affiche le formulaire de modification d'un projet existant.
     *
     * @param Project $project
     * @return Response
     */
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

    /**
     * Met à jour les informations du projet dans la base de données.
     * Empêche la modification de certaines données financières sensibles si des dépenses ont déjà été engagées.
     *
     * @param Request $request
     * @param Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $this->validateProjectRequest($request);

        // Contrainte de modification si dépenses existantes
        if ($project->hasPendingExpenses()) {
            if (
                $request->client_id != $project->client_id ||
                \Illuminate\Support\Carbon::parse($request->start_date)->toDateString()
                    != \Illuminate\Support\Carbon::parse($project->start_date)->toDateString() ||
                (float)$request->budget_labor != (float)$project->budget_labor ||
                (float)$request->budget_material != (float)$project->budget_material ||
                (float)$request->budget_transport != (float)$project->budget_transport ||
                (float)$request->budget_other != (float)$project->budget_other
            ) {
                return back()->withErrors([
                    'budget' => 'Impossible de modifier le budget, le client ou la date de début'
                        . ' pour un projet ayant des dépenses enregistrées.'
                ]);
            }
        }

        $validated['budget'] = (float)$validated['budget_labor']
            + (float)$validated['budget_material']
            + (float)$validated['budget_transport']
            + (float)$validated['budget_other'];

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
     * Supprime définitivement un projet.
     * La suppression est interdite (bloquée) si le projet contient des dépenses pour garantir l'intégrité financière.
     *
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

    private function validateProjectRequest(Request $request): array
    {
        return $request->validate([
            'name'              => 'required|string|max:255',
            'client_id'         => 'required|exists:clients,id',
            'description'       => 'nullable|string|max:2000',
            'start_date'        => 'required|date',
            'planned_end_date'  => 'required|date|after_or_equal:start_date',
            'actual_end_date'   => 'required_if:status,termine|nullable|date|after_or_equal:start_date',
            'budget_labor'      => 'required|numeric|min:0',
            'budget_material'   => 'required|numeric|min:0',
            'budget_transport'  => 'required|numeric|min:0',
            'budget_other'      => 'required|numeric|min:0',
            'status'            => 'required|in:en_cours,termine,en_pause',
            'supplier_contact'  => 'nullable|string|max:255',
        ]);
    }
}
