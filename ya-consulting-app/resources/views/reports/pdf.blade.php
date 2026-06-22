<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Financier - YA CONSULTING</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 3px solid #C9A84C;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #1A2B4A;
        }
        .logo-gold {
            color: #C9A84C;
        }
        .company-info {
            float: right;
            text-align: right;
            font-size: 11px;
            color: #666666;
            margin-top: 5px;
        }
        .report-title {
            text-align: center;
            font-size: 20px;
            color: #1A2B4A;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kpi-container {
            margin-bottom: 30px;
            width: 100%;
        }
        .kpi-card {
            width: 30%;
            display: inline-block;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin-right: 3%;
        }
        .kpi-card:last-child {
            margin-right: 0;
        }
        .kpi-title {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #1A2B4A;
        }
        .kpi-value.profit {
            color: #10B981;
        }
        .kpi-value.loss {
            color: #EF4444;
        }
        .text-success {
            color: #10B981;
        }
        .text-danger {
            color: #EF4444;
        }
        .text-bold {
            font-weight: bold;
        }
        h2 {
            color: #1A2B4A;
            font-size: 15px;
            border-left: 4px solid #C9A84C;
            padding-left: 8px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th {
            background-color: #1A2B4A;
            color: #ffffff;
            text-align: left;
            padding: 10px;
            font-size: 11px;
            text-transform: uppercase;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 10px;
            color: #666666;
            text-align: center;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

    <div class="header">
        <div style="float: left;">
            <div class="logo-text">YA <span class="logo-gold">CONSULTING</span></div>
        </div>
        <div class="company-info">
            <strong>YA CONSULTING </strong><br>
            Abidjan, Côte d'Ivoire<br>
            courriel@ya-consulting.com | (225) 01 52 22 63 12 || (225) 05 65 24 69 74
        </div>
        <div class="clear"></div>
    </div>

    <div class="report-title">
        Rapport d'Activité & de Rentabilité<br>
        <span style="font-size: 14px; color: #666;">Période : {{ $monthName }} {{ $year }}</span>
    </div>

    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-title">Budget Total</div>
            <div class="kpi-value">{{ number_format($total_budget, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Dépenses Totales</div>
            <div class="kpi-value" style="color: #EF4444;">{{ number_format($total_expenses, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Bénéfice Net</div>
            <div class="kpi-value {{ $net_profit >= 0 ? 'profit' : 'loss' }}">
                {{ number_format($net_profit, 0, ',', ' ') }} FCFA
            </div>
        </div>
    </div>

    <h2>Comparatifs & Performances</h2>
    <table style="margin-bottom: 25px;">
        <thead>
            <tr>
                <th>Indicateur de Performance</th>
                <th class="text-right">Période Actuelle</th>
                <th class="text-right">Période Précédente ({{ $prevMonthName }} {{ $prevYear }})</th>
                <th class="text-right">Évolution / Analyse</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Dépenses de la période</strong></td>
                <td class="text-right text-bold text-danger">{{ number_format($total_expenses, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($prev_total_expenses, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-bold {{ ($total_expenses - $prev_total_expenses) <= 0 ? 'text-success' : 'text-danger' }}">
                    {{ ($total_expenses - $prev_total_expenses) >= 0 ? '+' : '' }}{{ number_format($total_expenses - $prev_total_expenses, 0, ',', ' ') }} FCFA
                </td>
            </tr>
            <tr>
                <td><strong>Bénéfice net mensuel</strong></td>
                <td class="text-right text-bold {{ $net_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($net_profit, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($prev_net_profit, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-bold {{ ($net_profit - $prev_net_profit) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ ($net_profit - $prev_net_profit) >= 0 ? '+' : '' }}{{ number_format($net_profit - $prev_net_profit, 0, ',', ' ') }} FCFA
                </td>
            </tr>
            <tr>
                <td><strong>Gains des projets clôturés ce mois</strong></td>
                <td class="text-right text-bold text-success">{{ number_format($total_gains_completed, 0, ',', ' ') }} FCFA</td>
                <td class="text-right" style="color: #666; font-style: italic;">{{ $completed_projects_count }} projet(s) clos</td>
                <td class="text-right text-bold {{ $total_gains_completed >= 0 ? 'text-success' : 'text-danger' }}">Performance cumulée</td>
            </tr>
        </tbody>
    </table>

    <h2>Projets Actifs sur la Période</h2>
    <table>
        <thead>
            <tr>
                <th>Nom du Projet</th>
                <th>Client</th>
                <th class="text-right">Budget</th>
                <th class="text-right">Dépenses</th>
                <th class="text-right">Marge brute</th>
                <th class="text-right">Rentabilité</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>{{ $p->client?->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($p->budget, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($p->total_expenses, 0, ',', ' ') }}</td>
                    <td class="text-right {{ $p->gross_gain >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($p->gross_gain, 0, ',', ' ') }}
                    </td>
                    <td class="text-right">
                        <span class="badge {{ $p->gross_gain >= 0 ? 'badge-success' : 'badge-danger' }}">
                            {{ $p->profitability_rate }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #666;">Aucun projet actif sur cette période.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Dépenses de la Période par Catégorie</h2>
    <table>
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Type de coût</th>
                <th class="text-right">Nombre</th>
                <th class="text-right">Total Dépensé</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses_by_category as $cat)
                <tr>
                    <td>
                        <span {!! 'style="display: inline-block; width: 10px; height: 10px; background-color: ' . e($cat['color']) . '; margin-right: 5px; border-radius: 2px;"' !!}></span>
                        <strong>{{ $cat['name'] }}</strong>
                    </td>
                    <td>{{ $cat['type'] }}</td>
                    <td class="text-right">{{ $cat['count'] }}</td>
                    <td class="text-right"><strong>{{ number_format($cat['total'], 0, ',', ' ') }} FCFA</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #666;">Aucune dépense enregistrée sur cette période.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Généré par {{ $generated_by }} le {{ $generated_at->format('d/m/Y à H:i') }} | YA CONSULTING Project Manager
    </div>

</body>
</html>
