<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur de gestion des Dépenses.
 *
 * Ce contrôleur gère le cycle de vie complet d'une dépense :
 * création, consultation, modification et suppression.
 * Il inclut la gestion des justificatifs (upload de fichiers PDF/images).
 *
 * Règles de sécurité principales :
 * - Toutes les actions sont protégées par ExpensePolicy.
 * - Les chefs de projet ne voient et ne modifient que les dépenses
 *   de leurs propres projets (contrôle via project->created_by).
 * - Les montants sont chiffrés en BDD (cast 'encrypted' dans le modèle),
 *   ce qui nécessite une pagination manuelle lors de la recherche numérique.
 */
class ExpenseController extends Controller
{
    use AuthorizesRequests;

    // Constantes pour éviter les "magic strings" (fautes de frappe dans les noms de colonnes)
    private const COL_CREATED_BY  = 'created_by';
    private const COL_PROJECT_ID  = 'project_id';
    private const COL_CATEGORY_ID = 'category_id';
    private const COL_DESCRIPTION = 'description';
    private const COL_NAME        = 'name';

    /**
     * Affiche la liste paginée des dépenses avec filtres avancés.
     *
     * IMPORTANT : La recherche sur le montant (chiffré) ne peut pas se faire en SQL.
     * On effectue alors une pagination manuelle en PHP pour les recherches numériques.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Expense::class);

        // Eager loading des relations pour éviter le problème N+1
        $query = Expense::with(['project', 'category', 'creator']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Restriction chef de projet : uniquement les dépenses de SES projets
        if ($user && $user->hasRole('chef_projet')) {
            $query->whereHas('project', function ($q) {
                $q->where(self::COL_CREATED_BY, Auth::id());
            });
        } elseif ($user && $user->hasRole('client')) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            });
        }

        // ─── Application des filtres ───────────────────────────────
        if ($request->filled('project_id')) {
            $query->where(self::COL_PROJECT_ID, $request->project_id);
        }
        if ($request->filled('category_id')) {
            $query->where(self::COL_CATEGORY_ID, $request->category_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $search = $request->search;
        $isNumericSearch = $request->filled('search') && is_numeric($search);

        if ($isNumericSearch) {
            // PROBLÈME : le champ `amount` est chiffré en BDD, donc impossible de faire
            // un WHERE LIKE en SQL. Solution : charger toutes les dépenses en mémoire,
            // les déchiffrer via Eloquent, filtrer en PHP puis paginer manuellement.
            $allExpenses = $query->latest('date')->get();
            $filteredExpenses = $allExpenses->filter(function ($expense) use ($search) {
                return str_contains(strtolower($expense->description ?? ''), strtolower($search))
                    || str_contains((string) $expense->amount, $search);
            });

            // Pagination manuelle sur la collection filtrée
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $perPage = 15;
            $currentItems = $filteredExpenses->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $expenses = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentItems,
                $filteredExpenses->count(),
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );
            $expenses->appends($request->all()); // Préserve les filtres dans les liens de pagination
        } else {
            // Recherche textuelle classique sur la description via SQL (montant non ciblé)
            if ($request->filled('search')) {
                $query->where(self::COL_DESCRIPTION, 'like', '%' . $search . '%');
            }
            $expenses = $query->latest('date')
                ->paginate(15)
                ->withQueryString();
        }

        // Projets pour le filtre déroulant : limité aux projets du chef de projet si applicable
        $projectsQuery = Project::query();
        if ($user && $user->hasRole('chef_projet')) {
            $projectsQuery->where(self::COL_CREATED_BY, Auth::id());
        } elseif ($user && $user->hasRole('client')) {
            $projectsQuery->where('client_id', $user->client_id);
        }
        $projects = $projectsQuery->orderBy(self::COL_NAME)->get(['id', self::COL_NAME]);

        return Inertia::render('Expenses/Index', [
            'expenses'   => $expenses,
            'projects'   => $projects,
            'categories' => ExpenseCategory::orderBy(self::COL_NAME)->get(['id', self::COL_NAME, 'color']),
            'filters'    => $request->only(['project_id', 'category_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Affiche le formulaire de création d'une dépense.
     * La liste des projets est filtrée selon le rôle de l'utilisateur.
     */
    public function create(): Response
    {
        $this->authorize('create', Expense::class);

        return Inertia::render('Expenses/Create', [
            'projects'   => $this->authorizedProjectsQuery()
                ->orderBy(self::COL_NAME)
                ->get(['id', self::COL_NAME, 'budget']),
            'categories' => ExpenseCategory::orderBy(self::COL_NAME)->get(['id', self::COL_NAME]),
        ]);
    }

    /**
     * Affiche le formulaire de création d'une dépense pré-rempli avec un projet.
     * Appelé depuis la page de détail d'un projet (bouton "Ajouter une dépense").
     *
     * Double vérification : l'utilisateur doit pouvoir créer une dépense ET voir le projet.
     */
    public function createForProject(Project $project): Response
    {
        $this->authorize('create', Expense::class);
        $this->authorize('view', $project); // Double protection : vérifier l'accès au projet

        return Inertia::render('Expenses/Create', [
            'projects'         => [$project->only(['id', self::COL_NAME, 'budget'])],
            'selected_project' => $project->only(['id', self::COL_NAME]),
            'categories'       => ExpenseCategory::orderBy(self::COL_NAME)->get(['id', self::COL_NAME]),
        ]);
    }

    /**
     * Enregistre une nouvelle dépense en base de données.
     *
     * Validations métier supplémentaires :
     * - La date de dépense doit être dans la période du projet (entre start_date et actual_end_date).
     * - Si un justificatif est joint, il est stocké dans le dossier `receipts/` du stockage public.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Expense::class);

        $validated = $this->validateExpenseRequest($request);

        $project = Project::findOrFail($validated['project_id']);

        // Vérification de l'ownership : l'utilisateur peut-il modifier CE projet ?
        $this->authorize('update', $project);

        if ($error = $this->validateExpenseDate($validated['date'], $project)) {
            return back()->withErrors($error);
        }

        // ─── Gestion du justificatif ──────────────────────────────
        $receiptPath = null;
        $receiptOriginalName = null;

        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            // Stockage sur le disque 'local' (privé) → NON accessible publiquement.
            // Les justificatifs sont servis via la route protégée 'expenses.receipt'.
            $receiptPath = $file->store('receipts', 'local');
            $receiptOriginalName = $file->getClientOriginalName();
        }

        // ─── Création de la dépense ───────────────────────────────
        $expense = Expense::create([
            'project_id'            => $validated['project_id'],
            'category_id'           => $validated['category_id'],
            'date'                  => $validated['date'],
            'amount'                => $validated['amount'], // Sera chiffré automatiquement par le cast
            'description'           => $validated['description'],
            'receipt_path'          => $receiptPath,
            'receipt_original_name' => $receiptOriginalName,
            'created_by'            => Auth::id(),
        ]);

        // Journalisation avec la propriété 'project' pour contexte dans le log d'audit
        activity()->causedBy(Auth::user())
            ->performedOn($expense)
            ->withProperty('project', $project->name)
            ->log('Dépense enregistrée');

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Dépense enregistrée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition d'une dépense existante.
     */
    public function edit(Expense $expense): Response
    {
        $this->authorize('update', $expense);

        return Inertia::render('Expenses/Edit', [
            'expense'    => $expense,
            'projects'   => $this->authorizedProjectsQuery()
                ->orderBy(self::COL_NAME)
                ->get(['id', self::COL_NAME, 'budget']),
            'categories' => ExpenseCategory::orderBy(self::COL_NAME)->get(['id', self::COL_NAME]),
        ]);
    }

    /**
     * Met à jour une dépense existante.
     *
     * Si un nouveau justificatif est fourni, l'ancien fichier est supprimé du stockage
     * avant de stocker le nouveau (évite les fichiers orphelins sur le disque).
     */
    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $validated = $this->validateExpenseRequest($request);

        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('update', $project);

        if ($error = $this->validateExpenseDate($validated['date'], $project)) {
            return back()->withErrors($error);
        }

        // ─── Remplacement du justificatif ─────────────────────────
        if ($request->hasFile('receipt')) {
            // Supprimer l'ancien fichier du disque privé pour éviter les fichiers orphelins
            if ($expense->receipt_path) {
                Storage::disk('local')->delete($expense->receipt_path);
            }
            $file = $request->file('receipt');
            $expense->receipt_path = $file->store('receipts', 'local');
            $expense->receipt_original_name = $file->getClientOriginalName();
        }

        $expense->update([
            'project_id'  => $validated['project_id'],
            'category_id' => $validated['category_id'],
            'date'        => $validated['date'],
            'amount'      => $validated['amount'],
            'description' => $validated['description'],
            'updated_by'  => Auth::id(),
        ]);

        // Journalisation de la modification
        activity()->causedBy(Auth::user())
            ->performedOn($expense)
            ->log('Dépense mise à jour');

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Dépense mise à jour avec succès.');
    }

    /**
     * Supprime une dépense et son justificatif associé.
     *
     * Le fichier physique du justificatif est supprimé du disque avant
     * la suppression logique de l'enregistrement en base de données.
     */
    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        // On mémorise l'ID du projet avant suppression pour la redirection
        $projectId = $expense->project_id;

        // Suppression du fichier justificatif du disque privé si présent
        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }

        // Journalisation avant la suppression
        activity()->causedBy(Auth::user())
            ->performedOn($expense)
            ->log('Dépense supprimée');

        $expense->delete(); // Suppression logique (SoftDeletes)

        return redirect()->route('projects.show', $projectId)
            ->with('success', 'Dépense supprimée.');
    }

    /**
     * Télécharge le justificatif d'une dépense via une route protégée.
     *
     * Le fichier est stocké sur le disque 'local' (privé), donc non accessible
     * directement depuis le web. Cette méthode vérifie l'autorisation avant
     * de servir le fichier, garantissant que seuls les utilisateurs ayant
     * le droit de voir la dépense peuvent accéder à son justificatif.
     */
    public function downloadReceipt(Expense $expense)
    {
        // Vérifier que l'utilisateur a le droit de voir cette dépense
        $this->authorize('view', $expense);

        // Vérifier que la dépense a bien un justificatif
        if (!$expense->receipt_path || !Storage::disk('local')->exists($expense->receipt_path)) {
            abort(404, 'Justificatif introuvable.');
        }

        $absolutePath = Storage::disk('local')->path($expense->receipt_path);
        $downloadName = $expense->receipt_original_name ?? basename($expense->receipt_path);

        return response()->download($absolutePath, $downloadName);
    }

    private function validateExpenseRequest(Request $request): array
    {
        return $request->validate([
            'project_id'   => 'required|exists:projects,id',
            'category_id'  => 'required|exists:expense_categories,id',
            'date'         => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'description'  => 'nullable|string|max:1000',
            'receipt'      => 'nullable|file|max:5120|mimes:pdf,jpeg,png,jpg,webp',
        ]);
    }

    private function validateExpenseDate(string $date, Project $project): ?array
    {
        $expenseDate = \Carbon\Carbon::parse($date);
        
        if ($expenseDate->lt($project->start_date)) {
            return [
                'date' => 'La date de la dépense ne peut pas être antérieure à la date de début du projet ('
                    . \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') . ').'
            ];
        }

        if ($project->actual_end_date && $expenseDate->gt($project->actual_end_date)) {
            return [
                'date' => 'La date de la dépense ne peut pas être postérieure à la date de fin réelle du projet ('
                    . \Carbon\Carbon::parse($project->actual_end_date)->format('d/m/Y') . ').'
            ];
        }
        
        return null;
    }

    /**
     * Retourne un QueryBuilder de projets filtré selon le rôle de l'utilisateur connecté.
     * Les chefs de projet ne voient que leurs propres projets.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function authorizedProjectsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Project::query();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user && $user->hasRole('chef_projet')) {
            $query->where(self::COL_CREATED_BY, Auth::id());
        }
        return $query;
    }
}
