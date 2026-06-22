<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent représentant une Catégorie de Dépense.
 *
 * Les catégories permettent de classer les dépenses par nature.
 * Chaque catégorie appartient à un type parent (parent_type) qui
 * regroupe les catégories en 4 grandes familles :
 *   - 'main_oeuvre' : Salaires, honoraires, sous-traitance...
 *   - 'materiel'    : Achat de fournitures, équipements...
 *   - 'transport'   : Déplacements, carburant, billets...
 *   - 'autres'      : Toute autre dépense non classifiable
 *
 * @property int     $id
 * @property string  $name        Nom de la catégorie (ex: "Carburant")
 * @property string  $parent_type Type parent ('main_oeuvre', 'materiel', 'transport', 'autres')
 * @property bool    $is_custom   true si créée manuellement par un admin, false si pré-définie
 * @property string  $color       Couleur HEX pour l'affichage dans les graphiques
 */
class ExpenseCategory extends Model
{
    /**
     * Champs autorisés à l'assignation de masse.
     */
    protected $fillable = [
        'name',
        'parent_type',
        'is_custom',
        'color',
    ];

    /**
     * Conversions automatiques des types.
     * is_custom est stocké comme 0/1 en BDD, converti en boolean PHP.
     */
    protected $casts = [
        'is_custom' => 'boolean',
    ];

    // ─── Relations ───────────────────────────────────────────────

    /**
     * Toutes les dépenses appartenant à cette catégorie.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Retourne le tableau de correspondance entre les codes parent_type
     * et leurs libellés lisibles pour l'affichage dans le frontend.
     *
     * @return array<string, string>
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

    /**
     * Accesseur : retourne le libellé lisible du type parent.
     * Exemple : 'main_oeuvre' → "Main d'œuvre"
     *
     * @return string
     */
    public function getParentTypeLabelAttribute(): string
    {
        return self::parentTypeLabels()[$this->parent_type] ?? $this->parent_type;
    }
}
