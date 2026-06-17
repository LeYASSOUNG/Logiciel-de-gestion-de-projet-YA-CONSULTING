<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ─── Auth ────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('dashboard'));

require __DIR__.'/auth.php';

// ─── Application (authentifié) ───────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Projets
    Route::resource('projects', ProjectController::class);

    // Dépenses
    Route::resource('expenses', ExpenseController::class);
    Route::get('/projects/{project}/expenses/create', [ExpenseController::class, 'createForProject'])
        ->name('expenses.create-for-project');

    // Clients
    Route::resource('clients', ClientController::class);

    // Rapports (admin + chef de projet)
    Route::middleware('role:admin|chef_projet')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    });

    // Gestion utilisateurs (admin uniquement)
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)
            ->except(['create', 'store', 'show']);
    });
});
