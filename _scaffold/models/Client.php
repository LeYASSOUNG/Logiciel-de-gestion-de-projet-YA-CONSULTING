<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de brouillon (Scaffold) représentant un Client.
 * Les clients peuvent être associés à plusieurs projets.
 */
class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'contact_email',
        'contact_phone',
        'address',
        'company',
        'notes',
    ];

    /**
     * Récupère la liste des projets associés à ce client.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
