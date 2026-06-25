<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle de brouillon (Scaffold) représentant une Dépense.
 * Attaché à un projet et à une catégorie de dépense.
 */
class Expense extends Model
{
    use SoftDeletes;

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

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    // ─── Relations ───────────────────────────────────────────────
    
    /**
     * Le projet auquel appartient cette dépense.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * La catégorie associée à cette dépense.
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
    public function hasReceipt(): bool
    {
        return !is_null($this->receipt_path);
    }
}
