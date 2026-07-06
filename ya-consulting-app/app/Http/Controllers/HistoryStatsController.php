<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Expense;
use App\Models\Client;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class HistoryStatsController extends Controller
{
    /**
     * Affiche la page globale d'Historique et Statistiques.
     * 
     * Cette méthode rassemble et prépare toutes les données nécessaires pour le tableau de bord analytique.
     * Le code a été découpé en plusieurs sous-méthodes pour une meilleure lisibilité et pour éviter
     * les avertissements de l'analyseur statique (Intelephense).
     *
     * @param Request $request Contient les paramètres de filtrage optionnels depuis l'URL.
     * @return Response La page Inertia 'HistoryStats/Index' avec les données injectées.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $filters = $request->only(['client_id', 'year', 'status']);

        // 1. Récupération des projets filtrés
        $projects = $this->getFilteredProjects($user, $filters);
        
        // 2 & 3. Calcul des Statistiques Globales et Évolution des dépenses
        $stats = $this->calculateStatistics($projects);
        $stats['expenses_by_category'] = $this->getExpensesByCategory($user);
        
        // 4. Journal d'activité
        $activities = $this->getRecentActivities($user);

        return Inertia::render('HistoryStats/Index', [
            'projects' => $this->formatProjects($projects),
            'stats' => $stats,
            'activities' => $this->formatActivities($activities),
            'filters' => $this->getFilterData($filters),
        ]);
    }

    /**
     * Initialisation de la requête des projets avec filtrage par rôle et filtres URL.
     */
    private function getFilteredProjects(\App\Models\User $user, array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = Project::query()->with(['client', 'expenses']);
        
        if ($user->hasRole('chef_projet')) {
            $query->where('created_by', $user->id);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        return $query->latest()->get();
    }

    /**
     * Calcul des Statistiques Globales et Indicateurs de Performance (KPIs).
     */
    private function calculateStatistics(\Illuminate\Database\Eloquent\Collection $projects): array
    {
        $totalProjects = $projects->count();
        $completedProjectsCount = $projects->where('status', 'terminé')->count();
        
        $totalBudget = $projects->sum('budget');
        $totalExpenses = $projects->sum('total_expenses');
        // La rentabilité est calculée en pourcentage de marge brute par rapport au budget initial
        $averageProfitability = $totalBudget > 0 ? (($totalBudget - $totalExpenses) / $totalBudget) * 100 : 0;
        
        // Trier les projets par gain brut pour identifier les plus et moins rentables
        $profitableProjects = $projects->sortByDesc('gross_gain')->values();
        $mostProfitable = $profitableProjects->first();
        $leastProfitable = $profitableProjects->last();

        return [
            'total_projects' => $totalProjects,
            'completed_projects' => $completedProjectsCount,
            'average_profitability' => round($averageProfitability, 2),
            'most_profitable' => $mostProfitable ? [
                'name' => $mostProfitable->name,
                'gain' => $mostProfitable->gross_gain
            ] : null,
            'least_profitable' => $leastProfitable ? [
                'name' => $leastProfitable->name,
                'gain' => $leastProfitable->gross_gain
            ] : null,
        ];
    }

    /**
     * Évolution des dépenses par catégorie.
     */
    private function getExpensesByCategory(\App\Models\User $user)
    {
        $query = Expense::with('category');
        if ($user->hasRole('chef_projet')) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
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

    /**
     * Journal des actions utilisateurs (limité aux admins ou lié aux projets du chef).
     */
    private function getRecentActivities(\App\Models\User $user)
    {
        $query = Activity::with('causer')->latest();
        if ($user->hasRole('chef_projet')) {
            $query->where('causer_id', $user->id);
        }
        
        return $query->paginate(15)->withQueryString();
    }

    /**
     * Formate la collection de projets pour Inertia.
     */
    private function formatProjects(\Illuminate\Database\Eloquent\Collection $projects)
    {
        return $projects->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'client' => $p->client->name ?? '-',
                'status' => $p->status,
                'budget' => $p->budget,
                'total_expenses' => $p->total_expenses,
                'gross_gain' => $p->gross_gain,
                'created_at' => $p->created_at->format('Y-m-d'),
            ];
        });
    }

    /**
     * Formate la pagination des activités pour Inertia.
     */
    private function formatActivities($activities)
    {
        return $activities->through(fn ($activity) => [
            'id'           => $activity->id,
            'description'  => $activity->description,
            'causer_name'  => $activity->causer?->name ?? 'Système',
            'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
            'created_at'   => $activity->created_at->format('d/m/Y H:i:s'),
            'properties'   => $activity->properties,
        ]);
    }

    /**
     * Récupération des données pour les filtres.
     */
    private function getFilterData(array $filters): array
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);
        $years = Project::selectRaw('EXTRACT(YEAR FROM created_at) as year')->distinct()->orderByDesc('year')->pluck('year');

        return [
            'clients' => $clients,
            'years' => $years,
            'current' => $filters,
        ];
    }
}
