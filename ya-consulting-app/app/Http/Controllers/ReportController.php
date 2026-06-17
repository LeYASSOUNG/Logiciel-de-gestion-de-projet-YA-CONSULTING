<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use App\Models\Project;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $reports = MonthlyReport::with('generator')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(15);

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

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month'     => 'required|integer|between:1,12',
            'year'      => 'required|integer',
            'file_type' => 'required|in:pdf,excel',
        ]);

        $month = $validated['month'];
        $year = $validated['year'];
        $fileType = $validated['file_type'];

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Projets actifs pendant le mois
        $projects = Project::where('start_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('actual_end_date')
                  ->orWhere('actual_end_date', '>=', $startDate);
            })
            ->with(['client', 'expenses'])
            ->get();

        $totalBudget = $projects->sum('budget');

        // 2. Dépenses enregistrées au cours du mois
        $monthlyExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->get();

        $totalExpenses = $monthlyExpenses->sum('amount');
        $netProfit = $totalBudget - $totalExpenses;

        // Groupement des dépenses par catégorie
        $expensesByCategory = $monthlyExpenses->groupBy('category_id')->map(fn ($group) => [
            'name'  => $group->first()->category->name,
            'color' => $group->first()->category->color ?? '#94a3b8',
            'type'  => $group->first()->category->parent_type_label,
            'total' => $group->sum('amount'),
            'count' => $group->count(),
        ])->values()->toArray();

        // 3. Stats mois précédent (N-1)
        $prevMonthDate = $startDate->clone()->subMonth();
        $prevMonth = $prevMonthDate->month;
        $prevYear = $prevMonthDate->year;
        $prevStartDate = $prevMonthDate->clone()->startOfMonth();
        $prevEndDate = $prevMonthDate->clone()->endOfMonth();

        $prevProjects = Project::where('start_date', '<=', $prevEndDate)
            ->where(function ($q) use ($prevStartDate) {
                $q->whereNull('actual_end_date')
                  ->orWhere('actual_end_date', '>=', $prevStartDate);
            })->get();
        $prevTotalBudget = $prevProjects->sum('budget');
        $prevTotalExpenses = Expense::whereBetween('date', [$prevStartDate, $prevEndDate])->get()->sum('amount');
        $prevNetProfit = $prevTotalBudget - $prevTotalExpenses;

        // 4. Gains des projets terminés ce mois
        $completedProjects = Project::where('status', 'termine')
            ->whereBetween('actual_end_date', [$startDate, $endDate])
            ->get();
        $totalGainsCompleted = $completedProjects->sum(fn($p) => $p->gross_gain);

        // Noms des mois en français
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        $monthName = $months[$month] ?? 'Inconnu';
        $prevMonthName = $months[$prevMonth] ?? 'Inconnu';

        $reportName = "Rapport Financier - " . $monthName . " " . $year;

        // Vérifier si un rapport existe déjà pour ce mois et ce type
        $existingReport = MonthlyReport::where('month', $month)
            ->where('year', $year)
            ->where('file_type', $fileType)
            ->first();

        if ($existingReport) {
            // Supprimer l'ancien fichier
            if ($existingReport->file_path) {
                Storage::disk('public')->delete($existingReport->file_path);
            }
            $existingReport->delete();
        }

        $filePath = '';
        $now = Carbon::now();

        if ($fileType === 'pdf') {
            $data = [
                'monthName'                => $monthName,
                'year'                     => $year,
                'total_budget'             => $totalBudget,
                'total_expenses'           => $totalExpenses,
                'net_profit'               => $netProfit,
                'projects'                 => $projects,
                'expenses_by_category'     => $expensesByCategory,
                'generated_by'             => Auth::user()->name,
                'generated_at'             => $now,
                // Nouvelles stats pour comparaison N vs N-1
                'prevMonthName'            => $prevMonthName,
                'prevYear'                 => $prevYear,
                'prev_total_expenses'      => $prevTotalExpenses,
                'prev_net_profit'          => $prevNetProfit,
                'total_gains_completed'    => $totalGainsCompleted,
                'completed_projects_count' => $completedProjects->count(),
            ];

            $pdf = Pdf::loadView('reports.pdf', $data);
            $fileName = "reports/rapport_" . $year . "_" . sprintf("%02d", $month) . "_" . time() . ".pdf";
            Storage::disk('public')->put($fileName, $pdf->output());
            $filePath = $fileName;
        } else {
            $fileName = "reports/rapport_" . $year . "_" . sprintf("%02d", $month) . "_" . time() . ".csv";
            
            $handle = fopen('php://temp', 'r+');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Titre principal
            fputcsv($handle, ["Rapport Financier - " . $monthName . " " . $year]);
            fputcsv($handle, []);
            
            // Section Indicateurs
            fputcsv($handle, ["RÉSUMÉ DES INDICATEURS DE PERFORMANCE"]);
            fputcsv($handle, ["Indicateur", "Valeur (FCFA)"]);
            fputcsv($handle, ["Budget Total des projets actifs", $totalBudget]);
            fputcsv($handle, ["Dépenses Totales", $totalExpenses]);
            fputcsv($handle, ["Bénéfice Net Global", $netProfit]);
            fputcsv($handle, []);
            
            // Comparatifs N vs N-1
            fputcsv($handle, ["COMPARAISON MOIS N vs N-1"]);
            fputcsv($handle, ["Indicateur", "Mois en cours ({$monthName} {$year})", "Mois précédent ({$prevMonthName} {$prevYear})", "Évolution"]);
            
            $expensesDiff = $totalExpenses - $prevTotalExpenses;
            $expensesPct = $prevTotalExpenses > 0 ? round(($expensesDiff / $prevTotalExpenses) * 100, 2) : 0;
            fputcsv($handle, ["Dépenses Totales", $totalExpenses, $prevTotalExpenses, $expensesDiff . " (" . ($expensesPct >= 0 ? '+' : '') . $expensesPct . "%)"]);
            
            $profitDiff = $netProfit - $prevNetProfit;
            $profitPct = $prevNetProfit != 0 ? round(($profitDiff / abs($prevNetProfit)) * 100, 2) : 0;
            fputcsv($handle, ["Bénéfice Net", $netProfit, $prevNetProfit, $profitDiff . " (" . ($profitPct >= 0 ? '+' : '') . $profitPct . "%)"]);
            
            fputcsv($handle, []);
            
            // Projets Actifs
            fputcsv($handle, ["DÉTAIL DES PROJETS ACTIFS"]);
            fputcsv($handle, ["Nom du projet", "Client", "Budget Fixé", "Dépenses Réelles", "Marge brute", "Taux de rentabilité"]);
            foreach ($projects as $proj) {
                fputcsv($handle, [
                    $proj->name,
                    $proj->client?->name ?? 'N/A',
                    $proj->budget,
                    $proj->total_expenses,
                    $proj->gross_gain,
                    $proj->profitability_rate . "%"
                ]);
            }
            fputcsv($handle, []);
            
            // Dépenses par catégorie
            fputcsv($handle, ["DÉPENSES PAR CATÉGORIE DE DÉPENSE"]);
            fputcsv($handle, ["Catégorie", "Type de budget", "Nombre de saisies", "Montant total (FCFA)"]);
            foreach ($expensesByCategory as $catInfo) {
                fputcsv($handle, [
                    $catInfo['name'],
                    $catInfo['type'],
                    $catInfo['count'],
                    $catInfo['total']
                ]);
            }
            
            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);
            
            Storage::disk('public')->put($fileName, $csvContent);
            $filePath = $fileName;
        }

        $report = MonthlyReport::create([
            'name'           => $reportName,
            'month'          => $month,
            'year'           => $year,
            'total_budget'   => $totalBudget,
            'total_expenses' => $totalExpenses,
            'net_profit'     => $netProfit,
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

    public function download(MonthlyReport $report)
    {
        if (!Storage::disk('public')->exists($report->file_path)) {
            return back()->withErrors(['error' => 'Le fichier du rapport n\'existe pas sur le serveur.']);
        }

        return Storage::disk('public')->download($report->file_path, $report->name . '.' . ($report->file_type === 'pdf' ? 'pdf' : 'csv'));
    }
}
