<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Template racine pour toutes les pages Inertia
     */
    protected $rootView = 'app';

    /**
     * Détermine la version des assets pour le rechargement de page
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Données partagées avec toutes les pages Vue.js
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames()->toArray(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'notifications' => function () use ($request) {
                if (!$request->user()) {
                    return [];
                }

                $notifications = [];

                // 1. Dépassements de budget & Échéances
                $projectsQuery = \App\Models\Project::query()
                    ->where('status', '!=', 'termine');

                if ($request->user()->hasRole('chef_projet')) {
                    $projectsQuery->where('created_by', $request->user()->id);
                }

                $activeProjects = $projectsQuery->get();

                foreach ($activeProjects as $project) {
                    // Budget dépassement
                    if ($project->total_expenses > $project->budget) {
                        $notifications[] = [
                            'id' => 'budget-' . $project->id,
                            'type' => 'danger',
                            'title' => 'Dépassement de budget',
                            'message' => "Le budget du projet {$project->name} a été dépassé ("
                                . number_format($project->total_expenses, 0, ',', ' ') . " / "
                                . number_format($project->budget, 0, ',', ' ') . " FCFA).",
                            'link' => route('projects.show', $project->id),
                            'created_at' => \Illuminate\Support\Carbon::now()->diffForHumans(),
                        ];
                    } elseif ($project->budget > 0 && $project->total_expenses >= ($project->budget * 0.8)) {
                        $notifications[] = [
                            'id' => 'budget-warn-' . $project->id,
                            'type' => 'warning',
                            'title' => 'Budget critique (80% atteint)',
                            'message' => "Attention, le projet {$project->name} a consommé plus de 80% de son budget ("
                                . number_format($project->total_expenses, 0, ',', ' ') . " / "
                                . number_format($project->budget, 0, ',', ' ') . " FCFA).",
                            'link' => route('projects.show', $project->id),
                            'created_at' => \Illuminate\Support\Carbon::now()->diffForHumans(),
                        ];
                    }

                    // Échéance
                    $plannedEnd = \Illuminate\Support\Carbon::parse($project->planned_end_date);
                    if ($plannedEnd->isPast()) {
                        $notifications[] = [
                            'id' => 'deadline-' . $project->id,
                            'type' => 'danger',
                            'title' => 'Échéance dépassée',
                            'message' => "La date de fin prévisionnelle du projet {$project->name}"
                                . " est dépassée depuis le " . $plannedEnd->format('d/m/Y') . ".",
                            'link' => route('projects.show', $project->id),
                            'created_at' => $plannedEnd->diffForHumans(),
                        ];
                    } elseif ($plannedEnd->diffInDays(\Illuminate\Support\Carbon::now()) <= 3) {
                        $notifications[] = [
                            'id' => 'deadline-' . $project->id,
                            'type' => 'warning',
                            'title' => 'Échéance proche',
                            'message' => "Le projet {$project->name} arrive à échéance le "
                                . $plannedEnd->format('d/m/Y') . ".",
                            'link' => route('projects.show', $project->id),
                            'created_at' => $plannedEnd->diffForHumans(),
                        ];
                    }
                }

                // 2. Dernières activités du système
                $activitiesQuery = \Spatie\Activitylog\Models\Activity::with('causer')
                    ->latest()
                    ->take(5);

                if ($request->user()->hasRole('chef_projet')) {
                    $activitiesQuery->where(function ($q) use ($request) {
                        $q->where('causer_id', $request->user()->id)
                          ->orWhere(function ($sq) use ($request) {
                              $sq->where('subject_type', \App\Models\Project::class)
                                 ->whereIn(
                                     'subject_id',
                                     \App\Models\Project::where('created_by', $request->user()->id)->pluck('id')
                                 );
                          })
                          ->orWhere(function ($sq) use ($request) {
                              $sq->where('subject_type', \App\Models\Expense::class)
                                 ->whereIn(
                                     'subject_id',
                                     \App\Models\Expense::whereHas('project', function ($qp) use ($request) {
                                         $qp->where('created_by', $request->user()->id);
                                     })->pluck('id')
                                 );
                          });
                    });
                }

                $activities = $activitiesQuery->get();

                foreach ($activities as $activity) {
                    $link = null;
                    if ($activity->subject_type === \App\Models\Project::class) {
                        $link = route('projects.show', $activity->subject_id);
                    } elseif ($activity->subject_type === \App\Models\Expense::class) {
                        $expense = \App\Models\Expense::find($activity->subject_id);
                        if ($expense) {
                            $link = route('projects.show', $expense->project_id);
                        }
                    }

                    $notifications[] = [
                        'id' => 'activity-' . $activity->id,
                        'type' => 'info',
                        'title' => $activity->description,
                        'message' => "Par " . ($activity->causer?->name ?? 'Système')
                            . " le " . $activity->created_at->format('d/m H:i') . ".",
                        'link' => $link,
                        'created_at' => $activity->created_at->diffForHumans(),
                    ];
                }

                return $notifications;
            },
        ]);
    }
}
