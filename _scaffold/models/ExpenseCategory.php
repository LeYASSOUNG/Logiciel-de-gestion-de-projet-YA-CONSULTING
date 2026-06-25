<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de brouillon (Scaffold) représentant une Catégorie de Dépense.
 * Exemples: Main d'œuvre, Matériel, Transport.
 */
class ExpenseCategory extends Model
{
    protected $fillable = [
        'name',
        'parent_type',
        'is_custom',
        'color',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    /**
     * Récupère la liste des dépenses liées à cette catégorie.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    /**
     * Labels lisibles pour le frontend
     */
    public static function parentTypeLabels(): array
    {
        return [
            'main_oeuvre' => "Main d'œuvre",
            'materiel'    => 'Matériel',
            'transport'   => 'Transport',
            'autres'      => 'Autres coûts',
        ];
    }

    public function getParentTypeLabelAttribute(): string
    {
        return self::parentTypeLabels()[$this->parent_type] ?? $this->parent_type;
    }
}
