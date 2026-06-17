<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Expense;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        // ─── KPIs globaux ─────────────────────────────────────────
        $projectsQuery = Project::query();

        // Les chefs de projet ne voient que leurs projets
        if ($user->hasRole('chef_projet')) {
            $projectsQuery->where('created_by', $user->id);
        }

        $totalProjects    = $projectsQuery->count();
        $activeProjects   = (clone $projectsQuery)->enCours()->count();
        $pausedProjects   = (clone $projectsQuery)->enPause()->count();
        $completedProjects = (clone $projectsQuery)->termine()->count();

        // ─── Rentabilité globale ──────────────────────────────────
        $projects = (clone $projectsQuery)->with('expenses')->get();

        $totalBudget   = $projects->sum('budget');
        $totalExpenses = $projects->sum(fn ($p) => $p->total_expenses);
        $totalGain     = $totalBudget - $totalExpenses;

        // ─── Top projets ──────────────────────────────────────────
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

        // ─── Dépenses par catégorie ───────────────────────────────
        $expensesByCategory = Expense::query()
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name',
                'expense_categories.color',
                DB::raw('SUM(expenses.amount) as total')
            )
            ->when($user->hasRole('chef_projet'), function ($q) use ($user) {
                $q->join('projects', 'expenses.project_id', '=', 'projects.id')
                  ->where('projects.created_by', $user->id);
            })
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total')
            ->get();

        // ─── Évolution mensuelle (12 derniers mois) ───────────────
        $expensesForTrend = Expense::query()
            ->where('date', '>=', now()->subMonths(12)->startOfMonth())
            ->when($user->hasRole('chef_projet'), function ($q) use ($user) {
                $q->whereHas('project', function ($qp) use ($user) {
                    $qp->where('created_by', $user->id);
                });
            })
            ->get();

        $monthlyTrend = $expensesForTrend->groupBy(function ($expense) {
            return $expense->date->format('Y-m');
        })->map(function ($group, $key) {
            list($year, $month) = explode('-', $key);
            return [
                'year'  => (int) $year,
                'month' => (int) $month,
                'total' => (float) $group->sum('amount'),
            ];
        })->values()->sortBy(fn ($item) => $item['year'] * 100 + $item['month'])->values();

        // ─── Projets récents ──────────────────────────────────────
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
            'top_profitable'       => $topProfitable,
            'expenses_by_category' => $expensesByCategory,
            'monthly_trend'        => $monthlyTrend,
            'recent_projects'      => $recentProjects,
        ]);
    }
}
