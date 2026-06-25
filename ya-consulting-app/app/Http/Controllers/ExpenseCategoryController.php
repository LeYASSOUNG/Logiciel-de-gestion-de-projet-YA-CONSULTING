<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Require admin role
        if (!$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé.');
        }

        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->orderBy('is_custom', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories->map(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'parent_type'   => $c->parent_type,
                'parent_label'  => $c->parent_type_label,
                'is_custom'     => $c->is_custom,
                'color'         => $c->color,
                'expenses_count'=> $c->expenses_count,
            ]),
            'parent_types' => ExpenseCategory::parentTypeLabels(),
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:expense_categories,name',
            'parent_type' => 'required|in:main_oeuvre,materiel,transport,autres',
            'color'       => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $category = ExpenseCategory::create([
            ...$validated,
            'is_custom' => true,
        ]);

        activity()->causedBy(Auth::user())
            ->performedOn($category)
            ->log('Catégorie de dépense créée');

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie de dépense créée avec succès.');
    }

    public function update(Request $request, ExpenseCategory $category)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('admin') || !$category->is_custom) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
            'parent_type' => 'required|in:main_oeuvre,materiel,transport,autres',
            'color'       => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $category->update($validated);

        activity()->causedBy(Auth::user())
            ->performedOn($category)
            ->log('Catégorie de dépense modifiée');

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie de dépense modifiée.');
    }

    public function destroy(ExpenseCategory $category)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasRole('admin') || !$category->is_custom) {
            abort(403, 'Accès non autorisé.');
        }

        if ($category->expenses()->exists()) {
            return back()->withErrors([
                'delete' => 'Impossible de supprimer une catégorie contenant des dépenses.'
            ]);
        }

        activity()->causedBy(Auth::user())
            ->performedOn($category)
            ->log('Catégorie de dépense supprimée');

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie de dépense supprimée.');
    }
}
