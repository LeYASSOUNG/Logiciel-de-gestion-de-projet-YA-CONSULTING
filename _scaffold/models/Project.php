<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle de brouillon (Scaffold) représentant un Projet.
 * Un projet est relié à un client et regroupe plusieurs dépenses.
 */
class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'client_id',
        'description',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'budget',
        'status',
        'supplier_contact',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'planned_end_date'  => 'date',
        'actual_end_date'   => 'date',
        'budget'            => 'decimal:2',
    ];

    // ─── Relations ──────────────────────────────────────────────
    
    /**
     * Le client auquel ce projet est facturé/rattaché.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * La liste des dépenses affectées à ce projet.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * L'utilisateur qui a créé ce projet.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Le dernier utilisateur à avoir modifié ce projet.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Accesseurs de rentabilité ───────────────────────────────

    /**
     * Total des dépenses du projet
     */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    /**
     * Gain brut = Budget - Total dépenses
     */
    public function getGrossGainAttribute(): float
    {
        return (float) $this->budget - $this->total_expenses;
    }

    /**
     * Taux de rentabilité en %
     */
    public function getProfitabilityRateAttribute(): float
    {
        if ($this->budget == 0) return 0;
        return round(($this->gross_gain / $this->budget) * 100, 2);
    }

    /**
     * Est-ce un projet bénéficiaire ?
     */
    public function getIsProfitableAttribute(): bool
    {
        return $this->gross_gain >= 0;
    }

    /**
     * Vérifier si le projet a des dépenses enregistrées
     */
    public function hasPendingExpenses(): bool
    {
        return $this->expenses()->exists();
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeEnCours(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'en_cours');
    }

    public function scopeTermine(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'termine');
    }

    public function scopeEnPause(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'en_pause');
    }

    public function scopeByClient(\Illuminate\Database\Eloquent\Builder $query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByYear(\Illuminate\Database\Eloquent\Builder $query, int $year)
    {
        return $query->whereYear('start_date', $year);
    }
}
