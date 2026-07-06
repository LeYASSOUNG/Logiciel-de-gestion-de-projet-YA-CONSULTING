<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Financier - YA CONSULTING</title>
    <style>
        @page {
            margin: 40px 50px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* En-tête */
        table.header-table {
            width: 100%;
            border-bottom: 2px solid #C9A84C;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .logo-text {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -1px;
        }
        .logo-gold {
            color: #C9A84C;
        }
        .company-info {
            text-align: right;
            font-size: 10px;
            color: #64748b;
            line-height: 1.4;
        }
        .company-info strong {
            color: #0f172a;
            font-size: 12px;
        }

        /* Titre du rapport */
        .report-banner {
            background-color: #0f172a;
            color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        .report-title {
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 5px 0;
            color: #ffffff;
        }
        .report-period {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* KPI Cards */
        table.kpi-table {
            width: 100%;
            margin-bottom: 35px;
            border-collapse: separate;
            border-spacing: 15px 0;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            width: 33.33%;
        }
        .kpi-title {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .kpi-value {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-value.profit {
            color: #10b981; /* Emeraude */
        }
        .kpi-value.loss {
            color: #ef4444; /* Rouge */
        }

        /* Titres de section */
        h2 {
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-top: 35px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        h2 span {
            color: #C9A84C;
            margin-right: 5px;
        }

        /* Tableaux de données */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 12px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #cbd5e1;
        }
        table.data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
            color: #334155;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }

        /* Utilitaires de texte */
        .text-bold { font-weight: bold; }
        .text-success { color: #10b981; }
        .text-danger { color: #ef4444; }
        .text-muted { color: #64748b; }

        /* Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-neutral { background-color: #f1f5f9; color: #475569; }

        /* Pied de page */
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }

        /* Éviter la coupure des lignes dans les tableaux lors du saut de page */
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>

    <!-- En-tête -->
    <table class="header-table" cellspacing="0" cellpadding="0">
        <tr>
            <td style="vertical-align: bottom;">
                <div class="logo-text">YA<span class="logo-gold">CONSULTING</span></div>
            </td>
            <td class="company-info" style="vertical-align: bottom;">
                <strong>YA CONSULTING</strong><br>
                Abidjan, Côte d'Ivoire<br>
                courriel@ya-consulting.com<br>
                +225 01 52 22 63 12 | +225 05 65 24 69 74
            </td>
        </tr>
    </table>

    <!-- Bannière Titre -->
    <div class="report-banner">
        <h1 class="report-title">Rapport d'Activité & Rentabilité</h1>
        <div class="report-period">Période du mois de : {{ $monthName }} {{ $year }}</div>
    </div>

    <!-- KPI -->
    <table class="kpi-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="kpi-card">
                <div class="kpi-title">Budget Total Actif</div>
                <div class="kpi-value">{{ number_format($total_budget, 0, ',', ' ') }} FCFA</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-title">Dépenses du mois</div>
                <div class="kpi-value" style="color: #ef4444;">{{ number_format($total_expenses, 0, ',', ' ') }} FCFA</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-title">Résultat (Bénéfice Net)</div>
                <div class="kpi-value {{ $net_profit >= 0 ? 'profit' : 'loss' }}">
                    {{ number_format($net_profit, 0, ',', ' ') }} FCFA
                </div>
            </td>
        </tr>
    </table>

    <!-- Comparatifs -->
    <h2><span>&#9632;</span> Synthèse & Comparatif (N / N-1)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Indicateur Clé</th>
                <th class="text-right">Période Actuelle</th>
                <th class="text-right text-muted">Mois Précédent ({{ $prevMonthName }} {{ $prevYear }})</th>
                <th class="text-right">Évolution</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Total des dépenses</strong></td>
                <td class="text-right text-bold">{{ number_format($total_expenses, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-muted">{{ number_format($prev_total_expenses, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-bold {{ ($total_expenses - $prev_total_expenses) <= 0 ? 'text-success' : 'text-danger' }}">
                    {{ ($total_expenses - $prev_total_expenses) >= 0 ? '+' : '' }}{{ number_format($total_expenses - $prev_total_expenses, 0, ',', ' ') }} FCFA
                </td>
            </tr>
            <tr>
                <td><strong>Bénéfice net global</strong></td>
                <td class="text-right text-bold">{{ number_format($net_profit, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-muted">{{ number_format($prev_net_profit, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-bold {{ ($net_profit - $prev_net_profit) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ ($net_profit - $prev_net_profit) >= 0 ? '+' : '' }}{{ number_format($net_profit - $prev_net_profit, 0, ',', ' ') }} FCFA
                </td>
            </tr>
            <tr>
                <td><strong>Gains des projets clôturés ce mois</strong></td>
                <td class="text-right text-bold text-success">{{ number_format($total_gains_completed, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-muted"><em>Donnée cumulée</em></td>
                <td class="text-right"><span class="badge badge-success">Gains définitifs</span></td>
            </tr>
        </tbody>
    </table>

    <!-- Dépenses par Catégorie -->
    <h2><span>&#9632;</span> Répartition des Dépenses</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Catégorie de coût</th>
                <th>Classification</th>
                <th class="text-right">Volume</th>
                <th class="text-right">Montant Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses_by_category as $cat)
                <tr>
                    <td>
                        <span style="display:inline-block; width:10px; height:10px; margin-right:8px; border-radius:2px;" bgcolor="{{ $cat['color'] }}">
                            <!-- Color box fallback -->
                            <table width="10px" height="10px" cellpadding="0" cellspacing="0" style="display:inline-table;">
                                <tr><td bgcolor="{{ $cat['color'] }}"></td></tr>
                            </table>
                        </span>
                        <strong>{{ $cat['name'] }}</strong>
                    </td>
                    <td class="text-muted">{{ $cat['type'] }}</td>
                    <td class="text-right">{{ $cat['count'] }} transaction(s)</td>
                    <td class="text-right text-bold">{{ number_format($cat['total'], 0, ',', ' ') }} FCFA</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Aucune dépense n'a été enregistrée sur cette période.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Projets Actifs -->
    <div style="page-break-before: always;"></div>
    <h2><span>&#9632;</span> Détail des Projets Actifs</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nom du Projet</th>
                <th>Client</th>
                <th class="text-right">Budget Alloué</th>
                <th class="text-right">Dépenses</th>
                <th class="text-right">Gain Brut</th>
                <th class="text-right">Rentabilité</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td class="text-muted">{{ $p->client?->name ?? 'Non défini' }}</td>
                    <td class="text-right">{{ number_format($p->budget, 0, ',', ' ') }}</td>
                    <td class="text-right text-danger">{{ number_format($p->total_expenses, 0, ',', ' ') }}</td>
                    <td class="text-right text-bold {{ $p->gross_gain >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($p->gross_gain, 0, ',', ' ') }}
                    </td>
                    <td class="text-right">
                        <div style="margin-bottom: 2px;">
                            <span class="badge {{ $p->gross_gain >= 0 ? 'badge-success' : 'badge-danger' }}">
                                {{ $p->profitability_rate }}%
                            </span>
                        </div>
                        @if($p->budget > 0)
                            @php
                                $barWidth = min(100, max(0, ($p->total_expenses / $p->budget) * 100));
                                $bgColor = $p->gross_gain >= 0 ? '#10b981' : '#ef4444';
                                $remWidth = 100 - $barWidth;
                            @endphp
                            <table width="100%" height="4" cellpadding="0" cellspacing="0" style="background: #e2e8f0; border-radius: 2px; margin-top: 4px;">
                                <tr>
                                    <td width="{{ $barWidth }}%" bgcolor="{{ $bgColor }}" style="line-height: 0; font-size: 0;">&nbsp;</td>
                                    <td width="{{ $remWidth }}%" style="line-height: 0; font-size: 0;">&nbsp;</td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Aucun projet n'est actif sur cette période.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        Document financier généré le {{ $generated_at->format('d/m/Y à H:i') }} par {{ $generated_by }} &bull; Logiciel de gestion YA CONSULTING
    </div>

</body>
</html>
