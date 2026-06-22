<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Eloquent représentant un Rapport Mensuel généré.
 *
 * Un rapport mensuel est un document PDF ou CSV récapitulatif des
 * performances financières d'un mois donné. Il est généré par
 * ReportController et stocké sur le disque (storage/app/public/reports/).
 *
 * Règle métier : si un rapport pour le même mois/année existe déjà,
 * il est remplacé par le nouveau (l'ancien fichier physique est supprimé).
 *
 * @property int      $id
 * @property string   $name            Nom du rapport (ex: "Rapport Juin 2026")
 * @property int      $month           Mois (1-12)
 * @property int      $year            Année (ex: 2026)
 * @property float    $total_budget    Budget total de tous les projets actifs ce mois
 * @property float    $total_expenses  Total des dépenses enregistrées ce mois
 * @property float    $net_profit      Bénéfice net = total_budget - total_expenses
 * @property string   $file_path       Chemin relatif du fichier (dans le storage)
 * @property string   $file_type       Type du fichier : 'pdf' ou 'csv'
 * @property int      $generated_by    ID de l'utilisateur qui a généré le rapport
 * @property \Carbon  $generated_at    Date et heure de génération
 */
class MonthlyReport extends Model
{
    /**
     * Champs autorisés à l'assignation de masse.
     */
    protected $fillable = [
        'name',
        'month',
        'year',
        'total_budget',
        'total_expenses',
        'net_profit',
        'file_path',
        'file_type',
        'generated_by',
        'generated_at',
    ];

    /**
     * Conversions automatiques des types lors de la lecture en BDD.
     * Les montants financiers sont castés en decimal:2 pour éviter
     * les problèmes d'arrondi flottant.
     */
    protected $casts = [
        'month'          => 'integer',
        'year'           => 'integer',
        'total_budget'   => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_profit'     => 'decimal:2',
        'generated_at'   => 'datetime',
    ];

    // ─── Relations ───────────────────────────────────────────────

    /**
     * L'utilisateur qui a généré ce rapport (champ generated_by).
     * Permet d'afficher "Rapport généré par [Nom]" dans le frontend.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
