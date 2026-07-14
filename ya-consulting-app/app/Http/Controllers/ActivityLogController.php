<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur du Journal d'Activité (Audit Log).
 *
 * Ce contrôleur est EXCLUSIVEMENT réservé aux Administrateurs.
 * Il affiche l'historique de toutes les actions sensibles enregistrées
 * automatiquement par le package Spatie Laravel Activitylog.
 *
 * Chaque entrée du journal contient :
 * - La description de l'action ("Projet créé", "Dépense supprimée", etc.)
 * - Le nom de l'utilisateur responsable (causer)
 * - Le type et l'ID de l'entité concernée (subject_type / subject_id)
 * - Des propriétés supplémentaires en JSON si ajoutées manuellement
 * - La date et l'heure précise de l'action
 */
class ActivityLogController extends Controller
{
    /**
     * Affiche la liste paginée du journal d'activité.
     *
     * Les entrées sont triées de la plus récente à la plus ancienne.
     * Seules les données nécessaires au frontend sont exposées (pas de surcharge JSON).
     */
    public function index(Request $request): Response
    {
        // Sécurité défensive : double vérification que l'utilisateur est bien admin,
        // même si le middleware 'role:admin' de la route est la première ligne de défense.
        // Cela protège contre toute erreur de configuration future des routes.
        /** @var User|null $authUser */
        $authUser = Auth::user();
        abort_unless($authUser?->hasRole('admin'), 403, 'Accès réservé aux administrateurs.');

        // Chargement eager du 'causer' (l'utilisateur auteur de l'action)
        // pour éviter le problème N+1 (une requête par ligne sinon)
        $activities = Activity::with('causer')
            ->latest()    // ORDER BY created_at DESC
            ->paginate(25); // 25 entrées par page

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities->through(fn ($activity) => [
                'id'           => $activity->id,
                'description'  => $activity->description,             // Ex: "Projet créé"
                'causer_name'  => $activity->causer?->name ?? 'Système', // Auteur ou "Système" si action automatique
                'subject_type' => $activity->subject_type
                    ? class_basename($activity->subject_type)
                    : null, // Ex: "Project"
                'subject_id'   => $activity->subject_id,             // ID de l'entité concernée
                'properties'   => $activity->properties,             // Données JSON supplémentaires
                'created_at'   => $activity->created_at->format('d/m/Y H:i:s'), // Timestamp formaté
            ]),
        ]);
    }
}
