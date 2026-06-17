<template>
  <AppLayout title="Tableau de bord">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Tableau de bord</h1>
          <p>Vue d'ensemble de la rentabilité de vos projets</p>
        </div>
        <Link :href="route('projects.create')" class="btn btn-accent">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nouveau projet
        </Link>
      </div>

      <!-- KPIs -->
      <div class="stats-grid">
        <div class="stat-card" style="--stat-color: var(--color-info); --stat-bg: rgba(59,130,246,.1)">
          <div class="stat-card-icon">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
            </svg>
          </div>
          <div class="stat-card-value">{{ stats.total_projects }}</div>
          <div class="stat-card-label">Total projets</div>
          <div style="margin-top:8px; font-size:.75rem; color: var(--color-text-muted);">
            {{ stats.active_projects }} en cours · {{ stats.paused_projects }} en pause
          </div>
        </div>

        <div class="stat-card" style="--stat-color: var(--color-success); --stat-bg: rgba(16,185,129,.1)">
          <div class="stat-card-icon">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-card-value">{{ stats.completed_projects }}</div>
          <div class="stat-card-label">Projets terminés</div>
        </div>

        <div class="stat-card" style="--stat-color: var(--color-accent); --stat-bg: rgba(201,168,76,.1)">
          <div class="stat-card-icon">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-card-value">{{ formatAmount(stats.total_budget) }}</div>
          <div class="stat-card-label">Budget total</div>
        </div>

        <div class="stat-card" :style="`--stat-color: ${stats.total_gain >= 0 ? 'var(--color-success)' : 'var(--color-danger)'}; --stat-bg: ${stats.total_gain >= 0 ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.1)'}`">
          <div class="stat-card-icon">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
          <div class="stat-card-value" :class="stats.total_gain >= 0 ? 'text-success' : 'text-danger'">
            {{ formatAmount(stats.total_gain) }}
          </div>
          <div class="stat-card-label">Gain brut total</div>
          <div style="margin-top:8px; font-size:.75rem;" :class="stats.profitability_rate >= 0 ? 'text-success' : 'text-danger'">
            {{ stats.profitability_rate >= 0 ? '+' : '' }}{{ stats.profitability_rate }}% rentabilité
          </div>
        </div>
      </div>

      <!-- Graphiques + Top projets -->
      <div class="grid-2">
        <!-- Évolution mensuelle -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Évolution des dépenses (12 mois)</h2>
          </div>
          <div class="card-body">
            <apexchart
              type="area"
              height="220"
              :options="monthlyChartOptions"
              :series="monthlySeries"
            />
          </div>
        </div>

        <!-- Dépenses par catégorie -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Dépenses par catégorie</h2>
          </div>
          <div class="card-body">
            <apexchart
              v-if="expenses_by_category.length"
              type="donut"
              height="220"
              :options="categoryChartOptions"
              :series="categorySeries"
            />
            <div v-else style="text-align:center; color:var(--color-text-muted); padding: 40px 0;">
              Aucune dépense enregistrée
            </div>
          </div>
        </div>
      </div>

      <!-- Projets récents + Top rentables -->
      <div class="grid-2 mt-lg">
        <!-- Projets récents -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Projets récents</h2>
            <Link :href="route('projects.index')" class="btn btn-outline btn-sm">Voir tous</Link>
          </div>
          <div class="table-container">
            <table class="table">
              <thead>
                <tr>
                  <th>Projet</th>
                  <th>Statut</th>
                  <th>Gain</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="project in recent_projects" :key="project.id">
                  <td>
                    <Link :href="route('projects.show', project.id)" style="font-weight:600; color:var(--color-primary); text-decoration:none;">
                      {{ project.name }}
                    </Link>
                    <div style="font-size:.75rem; color:var(--color-text-muted);">{{ project.client }}</div>
                  </td>
                  <td><StatusBadge :status="project.status" /></td>
                  <td :class="project.gross_gain >= 0 ? 'amount-positive' : 'amount-negative'">
                    {{ project.gross_gain >= 0 ? '+' : '' }}{{ formatAmount(project.gross_gain) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top 5 rentables -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">🏆 Top projets rentables</h2>
          </div>
          <div class="card-body" style="padding-top:0;">
            <div v-for="(project, i) in top_profitable" :key="project.id"
              style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--color-border);">
              <div style="width:28px; height:28px; border-radius:50%; background:var(--color-accent); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.75rem; color:var(--color-primary-dark); flex-shrink:0;">
                {{ i + 1 }}
              </div>
              <div style="flex:1; min-width:0;">
                <div style="font-weight:600; font-size:.875rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ project.name }}</div>
                <div class="profit-bar">
                  <div class="profit-bar-fill" :class="project.gross_gain >= 0 ? 'positive' : 'negative'"
                    :style="`width: ${Math.min(Math.abs(project.profitability), 100)}%`" />
                </div>
              </div>
              <div :class="project.gross_gain >= 0 ? 'amount-positive' : 'amount-negative'" style="font-size:.825rem; flex-shrink:0;">
                {{ project.profitability >= 0 ? '+' : '' }}{{ project.profitability }}%
              </div>
            </div>
            <div v-if="!top_profitable.length" style="text-align:center; color:var(--color-text-muted); padding:30px 0;">
              Aucun projet disponible
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  stats: Object,
  top_profitable: Array,
  expenses_by_category: Array,
  monthly_trend: Array,
  recent_projects: Array,
});

// ─── Formatage ──────────────────────────────────────────────────
const formatAmount = (v) => {
  if (v == null) return '—';
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
};

// ─── Graphique mensuel ──────────────────────────────────────────
const monthlySeries = computed(() => [{
  name: 'Dépenses',
  data: props.monthly_trend.map(m => m.total),
}]);

const monthlyChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, sparkline: { enabled: false } },
  colors: ['#C9A84C'],
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
  stroke: { curve: 'smooth', width: 2.5 },
  xaxis: {
    categories: props.monthly_trend.map(m => `${m.month}/${m.year}`),
    labels: { style: { colors: '#64748B', fontSize: '11px' } },
    axisBorder: { show: false },
  },
  yaxis: { labels: { style: { colors: '#64748B', fontSize: '11px' }, formatter: v => `${(v/1000).toFixed(0)}k` } },
  grid: { borderColor: '#E2E8F0', strokeDashArray: 4 },
  tooltip: { y: { formatter: v => formatAmount(v) } },
}));

// ─── Graphique catégories ───────────────────────────────────────
const categorySeries = computed(() => props.expenses_by_category.map(c => Number(c.total)));

const categoryChartOptions = computed(() => ({
  labels: props.expenses_by_category.map(c => c.name),
  colors: props.expenses_by_category.map(c => c.color),
  legend: { position: 'bottom', fontSize: '12px', fontFamily: 'Inter' },
  dataLabels: { enabled: false },
  stroke: { width: 2 },
  tooltip: { y: { formatter: v => formatAmount(v) } },
  plotOptions: { pie: { donut: { size: '65%' } } },
}));
</script>
