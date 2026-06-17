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

class ExpenseController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with(['project', 'category', 'creator']);

        // Restrictions par rôle
        if (Auth::user()->hasRole('chef_projet')) {
            $query->whereHas('project', function ($q) {
                $q->where('created_by', Auth::id());
            });
        }

        // Filtres
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('amount', 'like', '%' . $request->search . '%');
            });
        }

        $expenses = $query->latest('date')
            ->paginate(15)
            ->withQueryString();

        // Récupérer les projets pour les filtres
        $projectsQuery = Project::query();
        if (Auth::user()->hasRole('chef_projet')) {
            $projectsQuery->where('created_by', Auth::id());
        }
        $projects = $projectsQuery->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Expenses/Index', [
            'expenses'   => $expenses,
            'projects'   => $projects,
            'categories' => ExpenseCategory::orderBy('name')->get(['id', 'name', 'color']),
            'filters'    => $request->only(['project_id', 'category_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Expense::class);

        $projectsQuery = Project::query();
        if (Auth::user()->hasRole('chef_projet')) {
            $projectsQuery->where('created_by', Auth::id());
        }

        return Inertia::render('Expenses/Create', [
            'projects'   => $projectsQuery->orderBy('name')->get(['id', 'name', 'budget']),
            'categories' => ExpenseCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function createForProject(Project $project): Response
    {
        $this->authorize('create', Expense::class);
        $this->authorize('view', $project);

        return Inertia::render('Expenses/Create', [
            'projects'         => [$project->only(['id', 'name', 'budget'])],
            'selected_project' => $project->only(['id', 'name']),
            'categories'       => ExpenseCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Expense::class);

        $validated = $request->validate([
            'project_id'   => 'required|exists:projects,id',
            'category_id'  => 'required|exists:expense_categories,id',
            'date'         => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'description'  => 'nullable|string',
            'receipt'      => 'nullable|file|max:5120|mimes:pdf,jpeg,png,jpg,webp',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('update', $project); // S'assurer que l'utilisateur peut modifier ce projet

        $receiptPath = null;
        $receiptOriginalName = null;

        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $receiptPath = $file->store('receipts', 'public');
            $receiptOriginalName = $file->getClientOriginalName();
        }

        $expense = Expense::create([
            'project_id'            => $validated['project_id'],
            'category_id'           => $validated['category_id'],
            'date'                  => $validated['date'],
            'amount'                => $validated['amount'],
            'description'           => $validated['description'],
            'receipt_path'          => $receiptPath,
            'receipt_original_name' => $receiptOriginalName,
            'created_by'            => Auth::id(),
        ]);

        activity()->causedBy(Auth::user())
            ->performedOn($expense)
            ->withProperty('project', $project->name)
            ->log('Dépense enregistrée');

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Dépense enregistrée avec succès.');
    }

    public function edit(Expense $expense): Response
    {
        $this->authorize('update', $expense);

        $projectsQuery = Project::query();
        if (Auth::user()->hasRole('chef_projet')) {
            $projectsQuery->where('created_by', Auth::id());
        }

        return Inertia::render('Expenses/Edit', [
            'expense'    => $expense,
            'projects'   => $projectsQuery->orderBy('name')->get(['id', 'name', 'budget']),
            'categories' => ExpenseCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $validated = $request->validate([
            'project_id'   => 'required|exists:projects,id',
            'category_id'  => 'required|exists:expense_categories,id',
            'date'         => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'description'  => 'nullable|string',
            'receipt'      => 'nullable|file|max:5120|mimes:pdf,jpeg,png,jpg,webp',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('update', $project);

        if ($request->hasFile('receipt')) {
            // Supprimer l'ancien fichier s'il existe
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $file = $request->file('receipt');
            $expense->receipt_path = $file->store('receipts', 'public');
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

        activity()->causedBy(Auth::user())
            ->performedOn($expense)
            ->log('Dépense mise à jour');

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Dépense mise à jour avec succès.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        $projectId = $expense->project_id;

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        activity()->causedBy(Auth::user())
            ->performedOn($expense)
            ->log('Dépense supprimée');

        $expense->delete();

        return redirect()->route('projects.show', $projectId)
            ->with('success', 'Dépense supprimée.');
    }
}
