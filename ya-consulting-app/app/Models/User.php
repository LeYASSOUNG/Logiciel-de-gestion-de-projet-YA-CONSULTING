<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent représentant un Utilisateur de l'application.
 *
 * Le système de gestion des rôles et permissions est assuré par le
 * package Spatie Laravel Permission (trait HasRoles). Les rôles disponibles
 * sont : 'admin', 'chef_projet', 'collaborateur'.
 *
 * @property int    $id
 * @property string $name    Nom complet de l'utilisateur
 * @property string $email   Adresse email (identifiant de connexion)
 * @property string $password Mot de passe (haché via bcrypt)
 */
class User extends Authenticatable
{
    // Notifiable : permet d'envoyer des notifications (email, SMS, etc.)
    // HasRoles   : injecte les méthodes hasRole(), hasPermissionTo(), etc.
    use Notifiable, HasRoles;

    /**
     * Champs autorisés à l'assignation de masse.
     * Seuls ces champs peuvent être remplis via ::create() ou ->fill().
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Champs masqués lors de la sérialisation (JSON/Array).
     * Garantit que le mot de passe et le token de session ne
     * sont jamais exposés dans les réponses API ou Inertia.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversions automatiques des types lors de la lecture en BDD.
     * - email_verified_at : converti en objet Carbon (datetime)
     * - password : hashé automatiquement lors de l'écriture
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────

    /**
     * Tous les projets créés par cet utilisateur.
     * La clé étrangère est `created_by` (et non `user_id`).
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Toutes les dépenses enregistrées par cet utilisateur.
     * La clé étrangère est `created_by`.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }
}
