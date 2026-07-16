<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use App\Models\Project;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur gérant la génération et le téléchargement des Rapports Financiers mensuels.
 * Permet d'exporter des rapports au format PDF ou CSV (Excel).
 */
class ReportController extends Controller
{
    use AuthorizesRequests;

    /**
     * Vérifie si l'utilisateur connecté est un chef de projet.
     */
    private function isChefProjet(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user && $user->hasRole('chef_projet');
    }

    /**
     * Affiche la liste des rapports mensuels générés, avec pagination.
     * Prépare également les données nécessaires aux filtres (mois et années disponibles).
     */
    public function index(Request $request): Response
    {
        $query = MonthlyReport::with('generator')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        if ($this->isChefProjet()) {
            $query->where('generated_by', Auth::id());
        }

        $reports = $query->paginate(15);

        // Noms des mois en français
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        // Obtenir les années disponibles à partir des dépenses et projets
        $years = collect([Carbon::now()->year]);
        $projectYears = Project::whereNotNull('start_date')->get()->map(fn($p) => $p->start_date->year);
        $expenseYears = Expense::whereNotNull('date')->get()->map(fn($e) => $e->date->year);
        $years = $years->merge($projectYears)->merge($expenseYears)->unique()->sortDesc()->values();

        return Inertia::render('Reports/Index', [
            'reports' => $reports->through(fn ($r) => [
                'id'             => $r->id,
                'name'           => $r->name,
                'month'          => $months[$r->month] ?? $r->month,
                'year'           => $r->year,
                'total_budget'   => $r->total_budget,
                'total_expenses' => $r->total_expenses,
                'net_profit'     => $r->net_profit,
                'file_type'      => $r->file_type,
                'generated_by'   => $r->generator?->name ?? 'Système',
                'generated_at'   => $r->generated_at->format('d/m/Y H:i'),
            ]),
            'months'  => $months,
            'years'   => $years,
        ]);
    }

    /**
     * Génère un nouveau rapport mensuel (PDF ou CSV) et le stocke.
     * Orchestre la collecte des données et délègue la génération du fichier
     * à des méthodes privées spécialisées pour rester lisible.
     *
     * @param Request $request Contient le mois, l'année et le format (pdf/excel)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month'     => 'required|integer|between:1,12',
            'year'      => 'required|integer',
            'file_type' => 'required|in:pdf,excel',
        ]);

        $month    = (int) $validated['month'];
        $year     = (int) $validated['year'];
        $fileType = $validated['file_type'];

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // ── Collecte des données du mois courant ──────────────────
        $monthData = $this->collectMonthData($startDate, $endDate);

        // ── Collecte des données du mois précédent (N-1) ──────────
        $prevData = $this->collectPreviousMonthStats($startDate);

        // ── Noms des mois et intitulé du rapport ───────────────
        $months        = $this->monthNames();
        $monthName     = $months[$month]             ?? 'Inconnu';
        $prevMonthName = $months[$prevData['month']] ?? 'Inconnu';
        $reportName    = "Rapport Financier - {$monthName} {$year}";
        $now           = Carbon::now();

        // ── Suppression d'un rapport existant pour ce mois/type ───
        $this->deleteExistingReport($month, $year, $fileType);

        // ── Génération du fichier ─────────────────────────────
        if ($fileType === 'pdf') {
            $filePath = $this->generatePdfFile(
                $month, $year, $monthName, $prevMonthName,
                $prevData, $monthData, $now
            );
        } else {
            $filePath = $this->generateCsvFile(
                $month, $year, $monthName, $prevMonthName,
                $prevData, $monthData
            );
        }

        // ── Enregistrement du rapport en BDD ──────────────────
        $report = MonthlyReport::create([
            'name'           => $reportName,
            'month'          => $month,
            'year'           => $year,
            'total_budget'   => $monthData['totalBudget'],
            'total_expenses' => $monthData['totalExpenses'],
            'net_profit'     => $monthData['netProfit'],
            'file_path'      => $filePath,
            'file_type'      => $fileType,
            'generated_by'   => Auth::id(),
            'generated_at'   => $now,
        ]);

        activity()->causedBy(Auth::user())
            ->performedOn($report)
            ->log('Rapport mensuel généré');

        return redirect()->route('reports.index')
            ->with('success', 'Rapport généré avec succès.');
    }

    // ──────────────────────────────────────────────────────────────
    // Méthodes privées — Collecte des données
    // ──────────────────────────────────────────────────────────────

    /**
     * Collecte toutes les données financières pour une période donnée.
     *
     * @return array{projects: \Illuminate\Support\Collection,
     *   monthlyExpenses: \Illuminate\Support\Collection,
     *   totalBudget: float, totalExpenses: float, netProfit: float,
     *   expensesByCategory: array, completedProjects: \Illuminate\Support\Collection,
     *   totalGainsCompleted: float}
     */
    private function collectMonthData(Carbon $startDate, Carbon $endDate): array
    {
        $projects = Project::where('start_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('actual_end_date')
                  ->orWhere('actual_end_date', '>=', $startDate);
            })
            ->when($this->isChefProjet(), fn ($q) => $q->where('created_by', Auth::id()))
            ->with(['client', 'expenses', 'payments'])
            ->get();

        $totalBudget = (float) $projects->sum('budget');

        $monthlyExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->when($this->isChefProjet(), function ($q) {
                $q->whereHas('project', fn ($qp) => $qp->where('created_by', Auth::id()));
            })
            ->with('category')
            ->get();

        $totalExpenses = (float) $monthlyExpenses->sum('amount');
        $totalPaid     = (float) \App\Models\Payment::whereBetween(
            'payment_date', [$startDate, $endDate]
        )
        ->when($this->isChefProjet(), function ($q) {
            $q->whereHas('project', fn ($qp) => $qp->where('created_by', Auth::id()));
        })
        ->get()->sum('amount');
        $netProfit     = $totalPaid - $totalExpenses;
        $profitabilityRate = $totalPaid > 0 ? round(($netProfit / $totalPaid) * 100, 2) : 0;

        $expensesByCategory = $monthlyExpenses
            ->groupBy('category_id')
            ->map(fn ($group) => [
                'name'  => $group->first()->category->name,
                'color' => $group->first()->category->color ?? '#94a3b8',
                'type'  => $group->first()->category->parent_type_label,
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ])
            ->values()
            ->toArray();

        $completedProjects   = Project::where('status', 'termine')
            ->whereBetween('actual_end_date', [$startDate, $endDate])
            ->when($this->isChefProjet(), fn ($q) => $q->where('created_by', Auth::id()))
            ->get();
        $totalGainsCompleted = (float) $completedProjects->sum(fn ($p) => $p->gross_gain);

        return compact(
            'projects', 'monthlyExpenses',
            'totalBudget', 'totalPaid', 'totalExpenses', 'netProfit', 'profitabilityRate',
            'expensesByCategory', 'completedProjects', 'totalGainsCompleted'
        );
    }

    /**
     * Collecte les statistiques du mois précédent (N-1) pour la comparaison.
     *
     * @return array{month: int, year: int, totalBudget: float, totalExpenses: float, netProfit: float}
     */
    private function collectPreviousMonthStats(Carbon $currentStartDate): array
    {
        $prevMonthDate = $currentStartDate->clone()->subMonth();
        $prevStartDate = $prevMonthDate->clone()->startOfMonth();
        $prevEndDate   = $prevMonthDate->clone()->endOfMonth();

        $prevProjects = Project::where('start_date', '<=', $prevEndDate)
            ->where(function ($q) use ($prevStartDate) {
                $q->whereNull('actual_end_date')
                  ->orWhere('actual_end_date', '>=', $prevStartDate);
            })
            ->when($this->isChefProjet(), fn ($q) => $q->where('created_by', Auth::id()))
            ->with('payments')
            ->get();

        $prevTotalBudget   = (float) $prevProjects->sum('budget');
        $prevTotalExpenses = (float) Expense::whereBetween('date', [$prevStartDate, $prevEndDate])
            ->when($this->isChefProjet(), function ($q) {
                $q->whereHas('project', fn ($qp) => $qp->where('created_by', Auth::id()));
            })
            ->get()->sum('amount');
        $prevTotalPaid = (float) \App\Models\Payment::whereBetween('payment_date', [$prevStartDate, $prevEndDate])
            ->when($this->isChefProjet(), function ($q) {
                $q->whereHas('project', fn ($qp) => $qp->where('created_by', Auth::id()));
            })
            ->get()->sum('amount');

        $prevNetProfit = $prevTotalPaid - $prevTotalExpenses;
        $prevProfitabilityRate = $prevTotalPaid > 0 ? round(($prevNetProfit / $prevTotalPaid) * 100, 2) : 0;

        return [
            'month'         => $prevMonthDate->month,
            'year'          => $prevMonthDate->year,
            'totalBudget'   => $prevTotalBudget,
            'totalPaid'     => $prevTotalPaid,
            'totalExpenses' => $prevTotalExpenses,
            'netProfit'     => $prevNetProfit,
            'profitabilityRate' => $prevProfitabilityRate,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Méthodes privées — Génération des fichiers
    // ──────────────────────────────────────────────────────────────

    /** @param array $prevData @param array $monthData */
    private function generatePdfFile(
        int $month, int $year,
        string $monthName, string $prevMonthName,
        array $prevData, array $monthData, Carbon $now
    ): string {
        $pdf      = Pdf::loadView('reports.pdf', [
            'monthName'                => $monthName,
            'year'                     => $year,
            'total_budget'             => $monthData['totalBudget'],
            'total_paid'               => $monthData['totalPaid'],
            'total_expenses'           => $monthData['totalExpenses'],
            'net_profit'               => $monthData['netProfit'],
            'profitability_rate'       => $monthData['profitabilityRate'],
            'projects'                 => $monthData['projects'],
            'expenses_by_category'     => $monthData['expensesByCategory'],
            'generated_by'             => Auth::user()?->name ?? 'Système',
            'generated_at'             => $now,
            'prevMonthName'            => $prevMonthName,
            'prevYear'                 => $prevData['year'],
            'prev_total_paid'          => $prevData['totalPaid'],
            'prev_total_expenses'      => $prevData['totalExpenses'],
            'prev_net_profit'          => $prevData['netProfit'],
            'prev_profitability_rate'  => $prevData['profitabilityRate'],
            'total_gains_completed'    => $monthData['totalGainsCompleted'],
            'completed_projects_count' => $monthData['completedProjects']->count(),
        ]);
        $fileName = "reports/rapport_{$year}_" . sprintf('%02d', $month) . '_' . time() . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());
        return $fileName;
    }

    /** @param array $prevData @param array $monthData */
    private function generateCsvFile(
        int $month, int $year,
        string $monthName, string $prevMonthName,
        array $prevData, array $monthData
    ): string {
        $fileName = "reports/rapport_{$year}_" . sprintf('%02d', $month) . '_' . time() . '.csv';
        $handle   = fopen('php://temp', 'r+');
        // Ajout du BOM UTF-8 pour Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $delimiter = ';';

        fputcsv($handle, ["RAPPORT D'ACTIVITÉ & RENTABILITÉ - {$monthName} {$year}"], $delimiter);
        fputcsv($handle, [], $delimiter);
        
        fputcsv($handle, ['1. RÉSUMÉ DES INDICATEURS DE PERFORMANCE'], $delimiter);
        fputcsv($handle, ['Indicateur', 'Valeur (FCFA)'], $delimiter);
        fputcsv($handle, ['Budget Total des projets actifs', $monthData['totalBudget']], $delimiter);
        fputcsv($handle, ['Montant Encaissé',               $monthData['totalPaid']], $delimiter);
        fputcsv($handle, ['Dépenses Totales',               $monthData['totalExpenses']], $delimiter);
        fputcsv($handle, ['Bénéfice Net Global',            $monthData['netProfit']], $delimiter);
        fputcsv($handle, ['Taux de Rentabilité',            $monthData['profitabilityRate'] . '%'], $delimiter);
        fputcsv($handle, [], $delimiter);
        
        fputcsv($handle, ['2. COMPARAISON MOIS N vs N-1'], $delimiter);
        fputcsv($handle, [
            'Indicateur',
            "Mois en cours ({$monthName} {$year})",
            "Mois précédent ({$prevMonthName} {$prevData['year']})",
            'Évolution',
        ], $delimiter);

        $paidDiff = $monthData['totalPaid'] - $prevData['totalPaid'];
        $paidPct  = $prevData['totalPaid'] > 0 ? round(($paidDiff / $prevData['totalPaid']) * 100, 2) : 0;
        fputcsv($handle, ['Montant Encaissé', $monthData['totalPaid'], $prevData['totalPaid'],
            $paidDiff . ' (' . ($paidPct >= 0 ? '+' : '') . $paidPct . '%)'], $delimiter);

        $expDiff = $monthData['totalExpenses'] - $prevData['totalExpenses'];
        $expPct  = $prevData['totalExpenses'] > 0 ? round(($expDiff / $prevData['totalExpenses']) * 100, 2) : 0;
        fputcsv($handle, ['Dépenses Totales', $monthData['totalExpenses'], $prevData['totalExpenses'],
            $expDiff . ' (' . ($expPct >= 0 ? '+' : '') . $expPct . '%)'], $delimiter);

        $profDiff = $monthData['netProfit'] - $prevData['netProfit'];
        $profPct  = $prevData['netProfit'] != 0 ? round(($profDiff / abs($prevData['netProfit'])) * 100, 2) : 0;
        fputcsv($handle, ['Bénéfice Net', $monthData['netProfit'], $prevData['netProfit'],
            $profDiff . ' (' . ($profPct >= 0 ? '+' : '') . $profPct . '%)'], $delimiter);

        $rateDiff = round($monthData['profitabilityRate'] - $prevData['profitabilityRate'], 2);
        fputcsv($handle, [
            'Taux de Rentabilité',
            $monthData['profitabilityRate'] . '%',
            $prevData['profitabilityRate'] . '%',
            ($rateDiff >= 0 ? '+' : '') . $rateDiff . '%',
        ], $delimiter);
            
        fputcsv($handle, [], $delimiter);

        fputcsv($handle, ['3. DÉTAIL DES PROJETS ACTIFS'], $delimiter);
        fputcsv($handle, [
            'Nom du projet', 'Client', 'Budget Fixé',
            'Dépenses Réelles', 'Marge brute', 'Taux de rentabilité',
        ], $delimiter);
        foreach ($monthData['projects'] as $proj) {
            fputcsv($handle, [$proj->name, $proj->client?->name ?? 'N/A', $proj->budget,
                $proj->total_expenses, $proj->gross_gain, $proj->profitability_rate . '%'], $delimiter);
        }
        fputcsv($handle, [], $delimiter);
        
        fputcsv($handle, ['4. DÉPENSES PAR CATÉGORIE DE DÉPENSE'], $delimiter);
        fputcsv($handle, ['Catégorie', 'Type de budget', 'Nombre de saisies', 'Montant total (FCFA)'], $delimiter);
        foreach ($monthData['expensesByCategory'] as $cat) {
            fputcsv($handle, [$cat['name'], $cat['type'], $cat['count'], $cat['total']], $delimiter);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        Storage::disk('public')->put($fileName, $content);
        return $fileName;
    }

    /** Supprime un rapport existant (fichier + BDD) pour éviter les doublons. */
    private function deleteExistingReport(int $month, int $year, string $fileType): void
    {
        $existing = MonthlyReport::where('month', $month)
            ->where('year', $year)
            ->where('file_type', $fileType)
            ->when($this->isChefProjet(), fn ($q) => $q->where('generated_by', Auth::id()))
            ->first();
        if ($existing) {
            if ($existing->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $existing->delete();
        }
    }

    /**
     * Retourne le tableau des noms de mois en français (indexé de 1 à 12).
     *
     * @return array<int, string>
     */
    private function monthNames(): array
    {
        return [
            1 => 'Janvier',   2 => 'Février',  3 => 'Mars',      4 => 'Avril',
            5 => 'Mai',       6 => 'Juin',      7 => 'Juillet',   8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre',  11 => 'Novembre', 12 => 'Décembre',
        ];
    }

    /**
     * Télécharge le fichier physique d'un rapport existant.
     *
     * @param MonthlyReport $report Le modèle du rapport
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(MonthlyReport $report)
    {
        // Contrôle d'accès explicite : seuls les admin et chef_projet peuvent télécharger les rapports.
        // Note : le middleware 'role:admin|chef_projet' de la route est la première protection,
        // mais cette vérification ajoute une sécurité défensive supplémentaire.
        /** @var User|null $authUser */
        $authUser = Auth::user();
        abort_unless(
            $authUser?->hasAnyRole(['admin', 'chef_projet']),
            403,
            'Accès non autorisé au téléchargement de rapports financiers.'
        );

        if (!Storage::disk('public')->exists($report->file_path)) {
            $report->delete();
            return back()->withErrors([
                'error' => 'Le fichier n\'existe plus sur le serveur.'
                    . ' L\'entrée obsolète a été supprimée, veuillez générer un nouveau rapport.',
            ]);
        }

        $absolutePath = Storage::disk('public')->path($report->file_path);
        $fileName = $report->name . '.' . ($report->file_type === 'pdf' ? 'pdf' : 'csv');

        return response()->download($absolutePath, $fileName);
    }
}
