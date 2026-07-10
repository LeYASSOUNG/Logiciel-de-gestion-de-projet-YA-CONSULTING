<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

/**
 * Policy (Politique d'autorisation) pour le modèle Expense.
 *
 * Cette classe définit les règles d'accès granulaires pour chaque action
 * sur une dépense. Elle est appelée via $this->authorize() dans ExpenseController.
 *
 * La particularité des dépenses est qu'elles sont liées à un projet :
 * le chef de projet ne peut accéder qu'aux dépenses des projets qu'il a créés.
 * La vérification s'effectue via `expense->project->created_by === user->id`.
 *
 * Résumé des règles :
 * - Admin       : accès total à toutes les dépenses.
 * - Chef Projet : accès limité aux dépenses de SES projets (created_by du projet).
 * - Collaborateur: lecture seule uniquement (viewAny/view), aucune écriture.
 * - Client : lecture seule de SES propres projets (client_id du projet).
 */
class ExpensePolicy
{
    /**
     * L'utilisateur peut-il voir la liste de toutes les dépenses ?
     * (La restriction par projet est appliquée dans le contrôleur, pas ici)
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view expenses');
    }

    /**
     * L'utilisateur peut-il voir les détails d'une dépense spécifique ?
     *
     * - Admin : oui, toutes les dépenses.
     * - Chef de projet : oui, UNIQUEMENT les dépenses des projets qu'il a créés.
     * - Collaborateur : oui, via la permission 'view expenses' (lecture seule).
     */
    public function view(User $user, Expense $expense): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            // Vérification de l'ownership via le projet parent
            return $expense->project?->created_by === $user->id;
        }

        if ($user->hasRole('client')) {
            return $expense->project?->client_id === $user->client_id;
        }

        return $user->hasPermissionTo('view expenses');
    }

    /**
     * L'utilisateur peut-il créer une dépense ?
     * Autorisé pour : admin et chef_projet.
     * Refusé pour   : collaborateur.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create expenses');
    }

    /**
     * L'utilisateur peut-il modifier une dépense existante ?
     *
     * - Admin : oui, toutes les dépenses.
     * - Chef de projet : oui, UNIQUEMENT les dépenses de ses propres projets.
     * - Collaborateur : non.
     */
    public function update(User $user, Expense $expense): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            return $expense->project?->created_by === $user->id;
        }

        return false;
    }

    /**
     * L'utilisateur peut-il supprimer une dépense ?
     *
     * - Admin : oui, toutes les dépenses.
     * - Chef de projet : oui, UNIQUEMENT les dépenses de ses propres projets.
     * - Collaborateur : non.
     *
     * Note : la suppression physique du justificatif est gérée dans le contrôleur.
     */
    public function delete(User $user, Expense $expense): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            return $expense->project?->created_by === $user->id;
        }

        return false;
    }
}
