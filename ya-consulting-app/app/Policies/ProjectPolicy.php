<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Policy (Politique d'autorisation) pour le modèle Project.
 *
 * Cette classe définit les règles d'accès granulaires à chaque action
 * sur un projet. Elle est appelée automatiquement par $this->authorize()
 * dans ProjectController, garantissant que les contrôles de sécurité
 * sont centralisés et non dispersés dans le code.
 *
 * Résumé des règles :
 * - Admin       : accès total à toutes les actions sur tous les projets.
 * - Chef Projet : accès limité à SES propres projets (created_by === user->id).
 * - Collaborateur: lecture seule (view/viewAny), aucune écriture.
 */
class ProjectPolicy
{
    /**
     * L'utilisateur peut-il voir la liste de tous les projets ?
     *
     * Autorisé si l'utilisateur a la permission 'view projects' (accordée
     * aux 3 rôles via le seeder de permissions).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view projects');
    }

    /**
     * L'utilisateur peut-il voir les détails d'un projet spécifique ?
     *
     * - Admin : oui, tous les projets.
     * - Chef de projet : oui, mais UNIQUEMENT ses projets (created_by === user->id).
     * - Collaborateur : oui, via la permission 'view projects' (lecture seule).
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            // Restriction : le chef de projet ne voit que ses propres projets
            return $project->created_by === $user->id;
        }

        if ($user->hasRole('client')) {
            // Restriction : le client ne voit que ses propres projets
            return $project->client_id === $user->client_id;
        }

        return $user->hasPermissionTo('view projects');
    }

    /**
     * L'utilisateur peut-il créer un nouveau projet ?
     *
     * Autorisé pour : admin et chef_projet.
     * Refusé pour   : collaborateur (n'a pas la permission 'create projects').
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create projects');
    }

    /**
     * L'utilisateur peut-il modifier un projet existant ?
     *
     * - Admin : oui, tous les projets.
     * - Chef de projet : oui, mais UNIQUEMENT les projets qu'il a créés.
     * - Collaborateur : non (lecture seule).
     */
    public function update(User $user, Project $project): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            // Restriction stricte : seul le créateur peut modifier son projet
            return $project->created_by === $user->id;
        }

        return false;
    }

    /**
     * L'utilisateur peut-il supprimer un projet ?
     *
     * UNIQUEMENT l'administrateur peut supprimer un projet.
     * Cette restriction protège l'intégrité des données financières.
     *
     * Note : même l'admin ne peut pas supprimer un projet
     * qui possède des dépenses (vérification dans le contrôleur).
     */
    public function delete(User $user, Project $project): bool
    {
        // Admin only
        return $user->hasRole('admin');
    }
}
