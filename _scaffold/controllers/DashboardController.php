<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Expense;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur gérant le tableau de bord (Dashboard) principal.
 * Responsable de la récupération des KPI et données statistiques.
 */
class DashboardController extends Controller
{
    /**
     * Affiche la vue principale du tableau de bord avec l'ensemble des données aggrégées.
     * @return Response
     */
    public function index(): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // ─── KPIs globaux ─────────────────────────────────────────
        $projectsQuery = Project::query();

        // Les chefs de projet ne voient que leurs projets
        if ($user && $user->hasRole('chef_projet')) {
            $projectsQuery->where(['created_by' => $user->id]);
        }

        $totalProjects    = $projectsQuery->count();
        $activeProjects   = (clone $projectsQuery)->enCours()->count();
        $pausedProjects   = (clone $projectsQuery)->enPause()->count();
        $completedProjects = (clone $projectsQuery)->termine()->count();

        // ─── Rentabilité globale ──────────────────────────────────
        $projects = (clone $projectsQuery)->with(['expenses'])->get();

        $totalBudget   = $projects->sum(fn ($p) => $p->budget);
        $totalExpenses = $projects->sum(fn ($p) => $p->total_expenses);
        $totalGain     = $totalBudget - $totalExpenses;

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_projects'     => $totalProjects,
                'active_projects'    => $activeProjects,
                'paused_projects'    => $pausedProjects,
                'completed_projects' => $completedProjects,
                'total_budget'       => $totalBudget,
                'total_expenses'     => $totalExpenses,
                'total_gain'         => $totalGain,
                'profitability_rate' => $totalBudget > 0
                    ? round(($totalGain / $totalBudget) * 100, 2) : 0,
            ],
            'top_profitable'       => $this->getTopProfitable($projectsQuery),
            'expenses_by_category' => $this->getExpensesByCategory($user),
            'monthly_trend'        => $this->getMonthlyTrend($user),
            'recent_projects'      => $this->getRecentProjects($projectsQuery),
        ]);
    }

    /**
     * Récupère le Top 5 des projets les plus rentables.
     * @param \Illuminate\Database\Eloquent\Builder $projectsQuery
     */
    private function getTopProfitable(\Illuminate\Database\Eloquent\Builder $projectsQuery)
    {
        return (clone $projectsQuery)
            ->with(['expenses', 'client'])
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
    }

    /**
     * Agrège les dépenses totales par catégorie, en appliquant les restrictions de rôle.
     * @param \App\Models\User|null $user
     */
    private function getExpensesByCategory(?\App\Models\User $user)
    {
        return Expense::query()
            ->join('expense_categories', function ($join) {
                $join->on('expenses.category_id', '=', 'expense_categories.id');
            })
            ->select(
                'expense_categories.name',
                'expense_categories.color',
                DB::raw('SUM(expenses.amount) as total')
            )
            ->when($user && $user->hasRole('chef_projet'), function ($q) use ($user) {
                $q->join('projects', function ($join) {
                      $join->on('expenses.project_id', '=', 'projects.id');
                  })
                  ->where(['projects.created_by' => $user->id]);
            })
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->orderBy(DB::raw('total'), 'desc')
            ->get();
    }

    /**
     * Récupère la tendance mensuelle des dépenses sur les 12 derniers mois.
     * @param \App\Models\User|null $user
     */
    private function getMonthlyTrend(?\App\Models\User $user)
    {
        return Expense::query()
            ->selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(amount) as total')
            ->where('date', '>=', now()->subMonths(12)->startOfMonth())
            ->when($user && $user->hasRole('chef_projet'), function ($q) use ($user) {
                $q->join('projects', function ($join) {
                      $join->on('expenses.project_id', '=', 'projects.id');
                  })
                  ->where(['projects.created_by' => $user->id]);
            })
            ->groupByRaw('YEAR(date), MONTH(date)')
            ->orderByRaw('YEAR(date), MONTH(date)')
            ->get();
    }

    /**
     * Récupère la liste des 5 derniers projets créés.
     * @param \Illuminate\Database\Eloquent\Builder $projectsQuery
     */
    private function getRecentProjects(\Illuminate\Database\Eloquent\Builder $projectsQuery)
    {
        return (clone $projectsQuery)
            ->with(['client'])
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
    }
}
