<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Expense;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur du Tableau de Bord (Dashboard).
 *
 * Responsable de l'agrégation et du calcul de tous les indicateurs
 * de performance (KPI) affichés sur la page d'accueil de l'application.
 *
 * Les données sont adaptées selon le rôle de l'utilisateur :
 * - Admin/Collaborateur : vision globale de tous les projets.
 * - Chef de Projet      : vision filtrée sur ses propres projets uniquement.
 */
class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord avec tous les KPIs et graphiques.
     *
     * Données transmises à Dashboard.vue (via Inertia) :
     * - stats               : KPIs globaux (budget, dépenses, gain, nombre de projets)
     * - top_profitable      : Top 5 des projets les plus rentables
     * - top_least_profitable: Top 5 des projets les moins rentables
     * - expenses_by_category: Répartition des dépenses par catégorie (pour le graphique donut)
     * - monthly_trend       : Évolution mensuelle sur 12 mois par type de coût (pour le graphique area)
     * - recent_projects     : Les 5 derniers projets créés
     */
    public function index(): Response
    {
        $user = auth()->user();

        // ─── Construction de la requête de base des projets ───────
        // On clone cette requête pour l'utiliser plusieurs fois avec des filtres différents
        // sans avoir à la reconstruire (amélioration des performances).
        $projectsQuery = Project::query();

        // Restriction pour les chefs de projet : ils ne voient que leurs propres projets
        if ($user->hasRole('chef_projet')) {
            $projectsQuery->where('created_by', $user->id);
        }

        // Restriction pour les clients : ils ne voient que leurs projets
        if ($user->hasRole('client')) {
            $projectsQuery->where('client_id', $user->client_id);
        }

        // ─── KPIs de comptage ─────────────────────────────────────
        $totalProjects     = $projectsQuery->count();
        $activeProjects    = (clone $projectsQuery)->enCours()->count();
        $pausedProjects    = (clone $projectsQuery)->enPause()->count();
        $completedProjects = (clone $projectsQuery)->termine()->count();

        // ─── Calcul de la rentabilité globale ─────────────────────
        // On charge les dépenses en eager loading pour éviter le problème N+1
        $projects = (clone $projectsQuery)->with('expenses')->get();

        $totalBudget   = $projects->sum('budget');
        $totalExpenses = $projects->sum(fn ($p) => $p->total_expenses);
        $totalGain     = $totalBudget - $totalExpenses;

        // ─── Top 5 projets les plus rentables ─────────────────────
        // Tri en PHP car le gain brut est un accesseur calculé (non stocké en BDD)
        $topProfitable = (clone $projectsQuery)
            ->with('expenses', 'client')
            ->get()
            ->sortByDesc(fn ($p) => $p->gross_gain)
            ->take(5)
            ->values()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'client'       => $p->client->name ?? '-',
                'budget'       => $p->budget,
                'gross_gain'   => $p->gross_gain,
                'profitability' => $p->profitability_rate,
                'status'       => $p->status,
            ]);

        // ─── Dépenses par catégorie (pour le graphique Donut) ─────
        $expensesByCategoryQuery = Expense::with('category');
        if ($user->hasRole('chef_projet')) {
            // Filtrer les dépenses des projets appartenant au chef de projet connecté
            $expensesByCategoryQuery->whereHas('project', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }
        $expensesByCategory = $expensesByCategoryQuery->get()
            ->groupBy('category_id')
            ->map(function ($group) {
                $category = $group->first()->category;
                return [
                    'name'  => $category?->name ?? 'Sans catégorie',
                    'color' => $category?->color ?? '#6B7280',
                    'total' => (float) $group->sum('amount'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // ─── Évolution mensuelle sur 12 mois (pour le graphique Area) ───
        // On récupère toutes les dépenses des 12 derniers mois et on les groupe
        // par mois puis par type de coût (main d'œuvre, matériel, transport, autres)
        $expensesForTrend = Expense::with('category')
            ->where('date', '>=', now()->subMonths(12)->startOfMonth())
            ->when($user->hasRole('chef_projet'), function ($q) use ($user) {
                $q->whereHas('project', function ($qp) use ($user) {
                    $qp->where('created_by', $user->id);
                });
            })
            ->get();

        $monthlyTrend = $expensesForTrend->groupBy(function ($expense) {
            return $expense->date->format('Y-m'); // Clé de groupement : "2026-06"
        })->map(function ($group, $key) {
            list($year, $month) = explode('-', $key);
            return [
                'year'        => (int) $year,
                'month'       => (int) $month,
                'main_oeuvre' => (float) $group->filter(fn($e) => $e->category?->parent_type === 'main_oeuvre')->sum('amount'),
                'materiel'    => (float) $group->filter(fn($e) => $e->category?->parent_type === 'materiel')->sum('amount'),
                'transport'   => (float) $group->filter(fn($e) => $e->category?->parent_type === 'transport')->sum('amount'),
                'autres'      => (float) $group->filter(fn($e) => $e->category?->parent_type === 'autres' || is_null($e->category?->parent_type))->sum('amount'),
                'total'       => (float) $group->sum('amount'),
            ];
        })->values()->sortBy(fn ($item) => $item['year'] * 100 + $item['month'])->values();

        // ─── Top 5 projets les moins rentables ────────────────────
        $topLeastProfitable = (clone $projectsQuery)
            ->with('expenses', 'client')
            ->get()
            ->sortBy(fn ($p) => $p->gross_gain) // Tri croissant (les plus mauvais en premier)
            ->take(5)
            ->values()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'client'       => $p->client->name ?? '-',
                'budget'       => $p->budget,
                'gross_gain'   => $p->gross_gain,
                'profitability' => $p->profitability_rate,
                'status'       => $p->status,
            ]);

        // ─── 5 projets récemment créés ────────────────────────────
        $recentProjects = (clone $projectsQuery)
            ->with('client')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'client'       => $p->client->name ?? '-',
                'status'       => $p->status,
                'budget'       => $p->budget,
                'total_expenses' => $p->total_expenses,
                'gross_gain'   => $p->gross_gain,
                'profitability' => $p->profitability_rate,
                'start_date'   => $p->start_date->format('d/m/Y'),
            ]);

        // ─── Graphique Budget vs Dépenses (Projets en cours) ──────
        $activeProjectsForChart = (clone $projectsQuery)
            ->enCours()
            ->get()
            ->sortByDesc('budget')
            ->take(10)
            ->values()
            ->map(fn ($p) => [
                'name' => $p->name,
                'budget' => $p->budget,
                'expenses' => $p->total_expenses,
            ]);

        // ─── Rendu Inertia ────────────────────────────────────────
        // Toutes les données sont transmises comme props au composant Vue Dashboard.vue
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_projects'     => $totalProjects,
                'active_projects'    => $activeProjects,
                'paused_projects'    => $pausedProjects,
                'completed_projects' => $completedProjects,
                'total_budget'       => $totalBudget,
                'total_expenses'     => $totalExpenses,
                'total_gain'         => $totalGain,
                // Taux de rentabilité global en % (0 si aucun budget pour éviter division par zéro)
                'profitability_rate' => $totalBudget > 0
                    ? round(($totalGain / $totalBudget) * 100, 2) : 0,
            ],
            'top_profitable'       => $topProfitable,
            'top_least_profitable' => $topLeastProfitable,
            'expenses_by_category' => $expensesByCategory,
            'monthly_trend'        => $monthlyTrend,
            'recent_projects'      => $recentProjects,
            'projects_budget_chart'=> $activeProjectsForChart,
        ]);
    }
}
