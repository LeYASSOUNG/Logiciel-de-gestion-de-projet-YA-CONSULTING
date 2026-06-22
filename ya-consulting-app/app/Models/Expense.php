<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Eloquent représentant une Dépense liée à un projet.
 *
 * Chaque dépense est attachée à un projet et une catégorie, avec un montant
 * chiffré pour la sécurité. Un justificatif (PDF ou image) peut être joint.
 *
 * @property int         $id
 * @property int         $project_id              Projet associé
 * @property int         $category_id             Catégorie de la dépense
 * @property \Carbon     $date                    Date de la dépense
 * @property float       $amount                  Montant (chiffré en BDD)
 * @property string|null $description             Description libre
 * @property string|null $receipt_path            Chemin du justificatif stocké
 * @property string|null $receipt_original_name   Nom original du fichier justificatif
 * @property int         $created_by              ID de l'utilisateur créateur
 */
class Expense extends Model
{
    // Permet la suppression logique : les dépenses supprimées ne sont pas
    // physiquement effacées, elles sont simplement marquées avec `deleted_at`.
    use SoftDeletes;

    /**
     * Champs autorisés à l'assignation de masse.
     */
    protected $fillable = [
        'project_id',
        'category_id',
        'date',
        'amount',
        'description',
        'receipt_path',
        'receipt_original_name',
        'created_by',
        'updated_by',
    ];

    /**
     * Conversions automatiques des types.
     * IMPORTANT : le montant `amount` est chiffré ('encrypted') pour protéger
     * les données financières sensibles même en cas de fuite de base de données.
     */
    protected $casts = [
        'date'   => 'date',
        'amount' => 'encrypted',
    ];

    // ─── Relations ───────────────────────────────────────────────

    /**
     * Le projet auquel cette dépense est rattachée.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * La catégorie de cette dépense (ex: Transport, Main d'œuvre...).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * L'utilisateur qui a enregistré cette dépense.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Vérifie si un justificatif (reçu) est joint à cette dépense.
     *
     * @return bool true si un fichier justificatif existe
     */
    public function hasReceipt(): bool
    {
        return !is_null($this->receipt_path);
    }
}
