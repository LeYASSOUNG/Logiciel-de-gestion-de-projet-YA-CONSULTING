<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ─── Redirection racine ───────────────────────────────────────────
// L'URL racine "/" redirige automatiquement vers le tableau de bord.
// Si l'utilisateur n'est pas connecté, Inertia le redirige vers /login.
Route::get('/', fn() => redirect()->route('dashboard'));

// Charge les routes d'authentification générées par Laravel Breeze
// (login, register, logout, forgot-password, reset-password...)
require __DIR__.'/auth.php';

// ─── Routes protégées (authentifié + email vérifié) ───────────────
// Toutes les routes ci-dessous nécessitent d'être connecté ET d'avoir
// vérifié son adresse email. Si non, redirection vers /login ou /verify-email.
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard : page d'accueil principale avec KPIs et graphiques
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil utilisateur : modifier ses informations personnelles
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Projets : CRUD complet (index, create, store, show, edit, update, destroy)
    // L'accès à chaque action est contrôlé par ProjectPolicy.
    Route::resource('projects', ProjectController::class);

    // Dépenses : CRUD complet
    // L'accès à chaque action est contrôlé par ExpensePolicy.
    Route::resource('expenses', ExpenseController::class);

    // Route spéciale : créer une dépense depuis la page d'un projet
    // (pré-remplit le formulaire avec le projet sélectionné)
    Route::get('/projects/{project}/expenses/create', [ExpenseController::class, 'createForProject'])
        ->name('expenses.create-for-project');

    // Clients : CRUD complet (accessible par admin et chef de projet)
    Route::resource('clients', ClientController::class);

    // ─── Rapports (admin + chef de projet uniquement) ─────────────
    // Le middleware 'role:admin|chef_projet' bloque les collaborateurs.
    Route::middleware('role:admin|chef_projet')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    });

    // ─── Administration (admin uniquement) ────────────────────────
    // Le middleware 'role:admin' bloque les chefs de projet et collaborateurs.
    Route::middleware('role:admin')->group(function () {
        // Gestion des utilisateurs (liste, édition, suppression - sans create/show séparé)
        Route::resource('users', \App\Http\Controllers\UserController::class)
            ->except(['create', 'show']);

        // Gestion des catégories de dépenses (sans formulaires séparés)
        Route::resource('categories', \App\Http\Controllers\ExpenseCategoryController::class)
            ->except(['create', 'edit', 'show']);

        // Journal d'audit : historique de toutes les actions sensibles
        Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])
            ->name('activity-logs.index');
    });
});

