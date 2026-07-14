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
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        $projectsQuery = $this->getBaseQuery($user);

        // ─── KPIs de comptage ─────────────────────────────────────
        $totalProjects     = $projectsQuery->count();
        $activeProjects    = (clone $projectsQuery)->enCours()->count();
        $pausedProjects    = (clone $projectsQuery)->enPause()->count();
        $completedProjects = (clone $projectsQuery)->termine()->count();

        // ─── Calcul de la rentabilité globale ─────────────────────
        $projects = (clone $projectsQuery)->with(['expenses', 'payments'])->get();

        $totalBudget   = $projects->sum('budget');
        $totalPaid     = $projects->sum(fn ($p) => $p->total_paid);
        $totalExpenses = $projects->sum(fn ($p) => $p->total_expenses);
        $totalGain     = $totalPaid - $totalExpenses;

        // ─── Rendu Inertia ────────────────────────────────────────
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_projects'     => $totalProjects,
                'active_projects'    => $activeProjects,
                'paused_projects'    => $pausedProjects,
                'completed_projects' => $completedProjects,
                'total_budget'       => $totalBudget,
                'total_paid'         => $totalPaid,
                'total_expenses'     => $totalExpenses,
                'total_gain'         => $totalGain,
                'profitability_rate' => $totalPaid > 0
                    ? round(($totalGain / $totalPaid) * 100, 2) : 0,
            ],
            'top_profitable'       => $this->getTopProfitable(clone $projectsQuery),
            'top_least_profitable' => $this->getTopLeastProfitable(clone $projectsQuery),
            'expenses_by_category' => $this->getExpensesByCategory($user),
            'monthly_trend'        => $this->getMonthlyTrend($user),
            'recent_projects'      => $this->getRecentProjects(clone $projectsQuery),
            'projects_budget_chart'=> $this->getActiveProjectsChart(clone $projectsQuery),
        ]);
    }

    private function getBaseQuery(\App\Models\User $user)
    {
        $query = Project::query();
        if ($user->hasRole('chef_projet')) {
            $query->where('created_by', $user->id);
        }
        return $query;
    }

    private function getTopProfitable(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->with(['expenses', 'payments', 'client'])
            ->get()
            ->sortByDesc(fn ($p) => $p->gross_gain)
            ->take(5)
            ->values()
            ->map(fn ($p) => $this->mapProjectSummary($p));
    }

    private function mapProjectSummary(\App\Models\Project $p): array
    {
        return [
            'id'            => $p->id,
            'name'          => $p->name,
            'client'        => $p->client->name ?? '-',
            'budget'        => $p->budget,
            'gross_gain'    => $p->gross_gain,
            'profitability' => $p->profitability_rate,
            'status'        => $p->status,
        ];
    }

    private function getTopLeastProfitable(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->with(['expenses', 'payments', 'client'])
            ->get()
            ->sortBy(fn ($p) => $p->gross_gain)
            ->take(5)
            ->values()
            ->map(fn ($p) => $this->mapProjectSummary($p));
    }

    private function getExpensesByCategory(\App\Models\User $user)
    {
        $query = Expense::with('category');
        if ($user->hasRole('chef_projet')) {
            $query->whereHas('project', fn ($q) => $q->where('created_by', $user->id));
        }
        return $query->get()
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
    }

    private function getMonthlyTrend(\App\Models\User $user)
    {
        $expenses = Expense::with('category')
            ->where('date', '>=', now()->subMonths(12)->startOfMonth())
            ->when($user->hasRole('chef_projet'), function ($q) use ($user) {
                $q->whereHas('project', fn ($qp) => $qp->where('created_by', $user->id));
            })
            ->get();

        return $expenses->groupBy(fn ($expense) => $expense->date->format('Y-m'))
            ->map(function ($group, $key) {
                list($year, $month) = explode('-', $key);
                return [
                    'year'        => (int) $year,
                    'month'       => (int) $month,
                    'main_oeuvre' => (float) $group
                        ->filter(fn($e) => $e->category?->parent_type === 'main_oeuvre')
                        ->sum('amount'),
                    'materiel'    => (float) $group
                        ->filter(fn($e) => $e->category?->parent_type === 'materiel')
                        ->sum('amount'),
                    'transport'   => (float) $group
                        ->filter(fn($e) => $e->category?->parent_type === 'transport')
                        ->sum('amount'),
                    'autres'      => (float) $group
                        ->filter(fn($e) => in_array($e->category?->parent_type, ['autres', null]))
                        ->sum('amount'),
                    'total'       => (float) $group->sum('amount'),
                ];
            })
            ->values()
            ->sortBy(fn ($item) => $item['year'] * 100 + $item['month'])
            ->values();
    }

    private function getRecentProjects(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->with(['client', 'payments', 'expenses'])
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

    private function getActiveProjectsChart(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->enCours()->with(['expenses', 'payments'])
            ->get()
            ->sortByDesc('budget')
            ->take(10)
            ->values()
            ->map(fn ($p) => [
                'name' => $p->name,
                'budget' => $p->budget,
                'expenses' => $p->total_expenses,
            ]);
    }
}
