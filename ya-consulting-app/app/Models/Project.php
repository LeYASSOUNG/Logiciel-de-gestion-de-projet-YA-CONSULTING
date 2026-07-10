<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Eloquent représentant un Projet de YA CONSULTING.
 *
 * Un projet est l'entité centrale de l'application. Il possède un budget
 * décomposé par type de coût, un statut de suivi, et un ensemble de dépenses.
 * Les données financières (budgets) sont chiffrées au repos en base de données.
 *
 * @property int         $id
 * @property string      $name              Nom du projet
 * @property int         $client_id         Clé étrangère vers le client
 * @property string|null $description       Description détaillée
 * @property string      $status            Statut : en_cours | termine | en_pause
 * @property float       $budget            Budget total calculé (somme des 4 postes)
 * @property float       $budget_labor      Poste : Main d'œuvre
 * @property float       $budget_material   Poste : Matériel
 * @property float       $budget_transport  Poste : Transport
 * @property float       $budget_other      Poste : Autres coûts
 * @property int         $created_by        ID de l'utilisateur créateur
 * @property int|null    $updated_by        ID du dernier modificateur
 * @property string|null $deleted_at        Date de suppression logique (SoftDelete)
 * @property \Illuminate\Support\Carbon|null $start_date Date de début
 * @property \Illuminate\Support\Carbon|null $planned_end_date Date de fin prévue
 * @property \Illuminate\Support\Carbon|null $actual_end_date Date de fin réelle
 * @property float       $total_expenses
 * @property float       $gross_gain
 * @property float       $profitability_rate
 * @property bool        $is_profitable
 * @property float       $expenses_labor
 * @property float       $expenses_material
 * @property float       $expenses_transport
 * @property float       $expenses_other
 */
class Project extends Model
{
    // Permet la suppression logique : les enregistrements supprimés
    // sont marqués via `deleted_at` et non physiquement effacés.
    use SoftDeletes;

    /**
     * Champs autorisés à l'assignation de masse.
     * Seuls ces champs peuvent être remplis via ::create() ou ->fill().
     */
    protected $fillable = [
        'name',
        'client_id',
        'description',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'initial_budget',
        'budget',
        'budget_labor',
        'budget_material',
        'budget_transport',
        'budget_other',
        'status',
        'supplier_contact',
        'created_by',
        'updated_by',
    ];

    /**
     * Conversions automatiques des types lors de la lecture/écriture en BDD.
     * IMPORTANT : les champs `budget_*` sont chiffrés ('encrypted') pour
     * protéger les données financières sensibles au repos.
     */
    protected $casts = [
        'start_date'        => 'date',
        'planned_end_date'  => 'date',
        'actual_end_date'   => 'date',
        'initial_budget'    => 'encrypted',
        'budget'            => 'encrypted',
        'budget_labor'      => 'encrypted',
        'budget_material'   => 'encrypted',
        'budget_transport'  => 'encrypted',
        'budget_other'      => 'encrypted',
    ];

    // ─── Relations ──────────────────────────────────────────────

    /**
     * Le client associé à ce projet.
     * Un projet appartient à un seul client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Les dépenses enregistrées sur ce projet.
     * Un projet peut avoir plusieurs dépenses.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Les paiements (encaissements) enregistrés sur ce projet.
     * Un projet peut avoir plusieurs paiements.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * L'utilisateur qui a créé ce projet (champ created_by).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Le dernier utilisateur qui a modifié ce projet (champ updated_by).
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Accesseurs de rentabilité ───────────────────────────────

    /**
     * Calcule le total de toutes les dépenses liées à ce projet.
     * Utilise la relation Eloquent pour agréger les montants.
     *
     * @return float Total des dépenses en FCFA
     */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->expenses->sum('amount');
    }

    /**
     * Calcule le total encaissé (somme des paiements du client).
     *
     * @return float Total encaissé en FCFA
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    /**
     * Calcule le reste à payer par le client.
     * Formule : Reste à payer = Budget Actuel - Total Encaissé
     *
     * @return float Reste à payer en FCFA
     */
    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->budget - $this->total_paid);
    }

    /**
     * Calcule le gain brut du projet.
     * Formule : Gain Brut = Budget Total - Total Dépenses
     *
     * @return float Gain brut (positif = bénéfice, négatif = déficit)
     */
    public function getGrossGainAttribute(): float
    {
        return $this->total_paid - $this->total_expenses;
    }

    /**
     * Calcule le taux de rentabilité du projet en pourcentage.
     * Formule : (Gain Brut / Budget) * 100
     * Retourne 0 si le budget est nul pour éviter une division par zéro.
     *
     * @return float Taux de rentabilité arrondi à 2 décimales
     */
    public function getProfitabilityRateAttribute(): float
    {
        if ($this->total_paid == 0) return 0;
        return round(($this->gross_gain / $this->total_paid) * 100, 2);
    }

    /**
     * Détermine si le projet est financièrement bénéficiaire.
     *
     * @return bool true si le gain brut est >= 0
     */
    public function getIsProfitableAttribute(): bool
    {
        return $this->gross_gain >= 0;
    }

    /**
     * Total des dépenses de type Main d'œuvre (parent_type = 'main_oeuvre').
     *
     * @return float
     */
    public function getExpensesLaborAttribute(): float
    {
        return (float) $this->expenses->filter(fn($e) => $e->category?->parent_type === 'main_oeuvre')->sum('amount');
    }

    /**
     * Total des dépenses de type Matériel (parent_type = 'materiel').
     *
     * @return float
     */
    public function getExpensesMaterialAttribute(): float
    {
        return (float) $this->expenses->filter(fn($e) => $e->category?->parent_type === 'materiel')->sum('amount');
    }

    /**
     * Total des dépenses de type Transport (parent_type = 'transport').
     *
     * @return float
     */
    public function getExpensesTransportAttribute(): float
    {
        return (float) $this->expenses->filter(fn($e) => $e->category?->parent_type === 'transport')->sum('amount');
    }

    /**
     * Total des dépenses classées dans "Autres coûts"
     * (parent_type = 'autres' ou sans catégorie définie).
     *
     * @return float
     */
    public function getExpensesOtherAttribute(): float
    {
        return (float) $this->expenses->filter(fn($e) => $e->category?->parent_type === 'autres' || is_null($e->category?->parent_type))->sum('amount');
    }

    /**
     * Vérifie si le projet a au moins une dépense enregistrée.
     * Utilisé pour interdire la suppression ou la modification du budget
     * lorsque des dépenses existent déjà.
     *
     * @return bool
     */
    public function hasPendingExpenses(): bool
    {
        return $this->expenses()->exists();
    }

    // ─── Scopes ─────────────────────────────────────────────────
    // Les scopes permettent de filtrer les requêtes de façon réutilisable.

    /** Filtre les projets avec le statut "En cours". */
    public function scopeEnCours($query)
    {
        return $query->where('status', 'en_cours');
    }

    /** Filtre les projets avec le statut "Terminé". */
    public function scopeTermine($query)
    {
        return $query->where('status', 'termine');
    }

    /** Filtre les projets avec le statut "En pause". */
    public function scopeEnPause($query)
    {
        return $query->where('status', 'en_pause');
    }

    /** Filtre les projets appartenant à un client spécifique. */
    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /** Filtre les projets dont la date de début est dans une année donnée. */
    public function scopeByYear($query, int $year)
    {
        return $query->whereYear('start_date', $year);
    }
}
