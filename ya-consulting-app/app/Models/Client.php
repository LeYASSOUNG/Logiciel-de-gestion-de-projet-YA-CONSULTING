<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent représentant un Client de YA CONSULTING.
 *
 * Un client est une entité (entreprise ou personne) qui commande des projets.
 * Il peut être associé à plusieurs projets. La suppression d'un client est
 * bloquée tant qu'il possède des projets (règle dans ClientController).
 *
 * @property int         $id
 * @property string      $name            Nom du client / organisme
 * @property string|null $contact_email   Email de contact principal
 * @property string|null $contact_phone   Téléphone de contact
 * @property string|null $company         Nom de l'entreprise (si différent du nom)
 * @property string|null $address         Adresse postale
 * @property string|null $notes           Notes libres sur le client
 */
class Client extends Model
{
    // Suppression logique : un client supprimé reste en base avec deleted_at renseigné,
    // permettant de restaurer les données si nécessaire.
    use SoftDeletes;

    /**
     * Champs autorisés à l'assignation de masse.
     */
    protected $fillable = [
        'name',
        'contact_email',
        'contact_phone',
        'address',
        'company',
        'notes',
    ];

    // ─── Relations ───────────────────────────────────────────────

    /**
     * Tous les projets associés à ce client.
     * Un client peut avoir plusieurs projets (relation One-to-Many).
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
