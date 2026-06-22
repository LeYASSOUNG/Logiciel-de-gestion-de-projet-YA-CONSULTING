<template>
  <AppLayout title="Tableau de bord">
    <div class="animate-fade-up">

      <!-- En-tête -->
      <PageHeader title="Tableau de bord" description="Vue d'ensemble de la rentabilité de vos projets">
        <template v-slot:actions>
          <Button as="Link" :href="route('projects.create')" variant="accent">
            <template v-slot:icon-left>
              <Icon name="plus" :size="16" />
            </template>
            Nouveau projet
          </Button>
        </template>
      </PageHeader>

      <!-- KPIs — StatCard standard (harmonie avec les autres pages) -->
      <div class="stats-grid">
        <StatCard
          class="stagger-1"
          label="Total projets"
          :value="stats.total_projects"
          icon="briefcase"
          color="info"
          :footer="`${stats.active_projects} en cours · ${stats.paused_projects} en pause`"
        />

        <StatCard
          class="stagger-2"
          label="Projets terminés"
          :value="stats.completed_projects"
          icon="check-circle"
          color="success"
          footer="Fermeture et livraison réussies"
        />

        <StatCard
          class="stagger-3"
          label="Budget total"
          :value="formatAmount(stats.total_budget)"
          icon="banknotes"
          color="accent"
          footer="Enveloppe allouée aux projets"
        />

        <StatCard
          class="stagger-4"
          label="Gain brut total"
          :value="formatAmount(stats.total_gain)"
          icon="arrow-trending-up"
          :color="stats.total_gain >= 0 ? 'success' : 'danger'"
          :colorValueClass="stats.total_gain >= 0 ? 'text-success' : 'text-danger'"
        >
          <template v-slot:footer>
            <span :class="stats.profitability_rate >= 0 ? 'text-success' : 'text-danger'" style="font-weight:600;">
              {{ stats.profitability_rate >= 0 ? '+' : '' }}{{ stats.profitability_rate }}% rentabilité globale
            </span>
          </template>
        </StatCard>
      </div>

      <!-- Graphiques -->
      <div class="grid-2 stagger-5">
        <Card title="Évolution des dépenses par axe (12 mois)">
          <apexchart type="bar" height="230" :options="monthlyChartOptions" :series="monthlySeries" />
        </Card>

        <Card title="Dépenses par catégorie">
          <apexchart
            v-if="expenses_by_category.length"
            type="donut"
            height="230"
            :options="categoryChartOptions"
            :series="categorySeries"
          />
          <EmptyState
            v-else
            title="Aucune dépense enregistrée"
            description="Les statistiques par catégorie s'afficheront dès qu'une dépense aura été validée."
            icon="banknotes"
          />
        </Card>
      </div>

      <!-- Projets récents + Analyse rentabilité -->
      <div class="grid-2 mt-lg">

        <!-- Projets récents — liste visuelle (plus lisible que la table) -->
        <Card title="Projets récents">
          <template v-slot:actions>
            <Button as="Link" :href="route('projects.index')" variant="outline" size="sm">Voir tous</Button>
          </template>

          <div v-if="recent_projects.length" class="recent-list">
            <Link
              v-for="project in recent_projects"
              :key="project.id"
              :href="route('projects.show', project.id)"
              class="recent-row"
            >
              <!-- Avatar lettre -->
              <div class="recent-avatar" :style="`background:${avatarColor(project.name)}`">
                {{ project.name.charAt(0).toUpperCase() }}
              </div>

              <!-- Infos -->
              <div class="recent-info">
                <div class="recent-name">{{ project.name }}</div>
                <div class="recent-client">{{ project.client || 'Sans client' }}</div>
              </div>

              <!-- Statut + gain -->
              <div class="recent-right">
                <StatusBadge :status="project.status" />
                <span :class="project.gross_gain >= 0 ? 'amount-positive' : 'amount-negative'" style="font-size:.82rem;">
                  {{ project.gross_gain >= 0 ? '+' : '' }}{{ formatAmount(project.gross_gain) }}
                </span>
              </div>
            </Link>
          </div>

          <EmptyState
            v-else
            title="Aucun projet récent"
            description="Créez votre premier projet pour démarrer le suivi."
            icon="briefcase"
          />
        </Card>

        <!-- Analyse de rentabilité -->
        <Card>
          <template v-slot:header>
            <h2 class="card-title">Analyse de Rentabilité</h2>
          </template>
          <template v-slot:actions>
            <div class="segment-control">
              <button @click="showLeastProfitable = false" class="segment-btn" :class="{ active: !showLeastProfitable }">
                Plus rentables
              </button>
              <button @click="showLeastProfitable = true" class="segment-btn" :class="{ active: showLeastProfitable }">
                Moins rentables
              </button>
            </div>
          </template>

          <div style="margin-top: calc(-1 * var(--space-sm));">
            <!-- Top Profitables -->
            <div v-show="!showLeastProfitable">
              <div
                v-for="(project, i) in top_profitable"
                :key="'top-' + project.id"
                style="display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:1px solid var(--color-border);"
              >
                <div class="rank-badge top-success">{{ i + 1 }}</div>
                <div style="flex:1; min-width:0;">
                  <Link :href="route('projects.show', project.id)" class="link-primary ellipsis" style="font-size:.875rem; display:block; font-weight:600;">
                    {{ project.name }}
                  </Link>
                  <div class="profit-bar" style="margin-top:5px;">
                    <div class="profit-bar-fill positive" :style="`width:${Math.min(Math.abs(project.profitability), 100)}%`" />
                  </div>
                </div>
                <div class="amount-positive" style="font-size:.825rem; flex-shrink:0; font-weight:700;">
                  +{{ project.profitability }}%
                </div>
              </div>
              <div v-if="!top_profitable.length" style="padding:30px 0;">
                <EmptyState title="Aucun projet" description="Les données apparaîtront après la création de projets." icon="briefcase" />
              </div>
            </div>

            <!-- Moins Rentables -->
            <div v-show="showLeastProfitable">
              <div
                v-for="(project, i) in top_least_profitable"
                :key="'least-' + project.id"
                style="display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:1px solid var(--color-border);"
              >
                <div class="rank-badge top-danger">{{ i + 1 }}</div>
                <div style="flex:1; min-width:0;">
                  <Link :href="route('projects.show', project.id)" class="link-primary ellipsis" style="font-size:.875rem; display:block; font-weight:600;">
                    {{ project.name }}
                  </Link>
                  <div class="profit-bar" style="margin-top:5px;">
                    <div class="profit-bar-fill negative" :style="`width:${Math.min(Math.abs(project.profitability), 100)}%`" />
                  </div>
                </div>
                <div :class="project.gross_gain >= 0 ? 'amount-positive' : 'amount-negative'" style="font-size:.825rem; flex-shrink:0; font-weight:700;">
                  {{ project.profitability >= 0 ? '+' : '' }}{{ project.profitability }}%
                </div>
              </div>
              <div v-if="!top_least_profitable.length" style="padding:30px 0;">
                <EmptyState title="Aucun projet" description="Les données apparaîtront après la création de projets." icon="briefcase" />
              </div>
            </div>
          </div>
        </Card>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatCard from '@/Components/StatCard.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
  stats:                Object,
  top_profitable:       Array,
  top_least_profitable: Array,
  expenses_by_category: Array,
  monthly_trend:        Array,
  recent_projects:      Array,
});

const showLeastProfitable = ref(false);

// ─── Avatar couleur par lettre ────────────────────────────────────
const PALETTE = ['#6366F1','#F59E0B','#10B981','#3B82F6','#EC4899','#8B5CF6','#14B8A6','#F97316'];
const avatarColor = (name) => PALETTE[name.charCodeAt(0) % PALETTE.length];

// ─── Formatage ────────────────────────────────────────────────────
const formatAmount = (v) => {
  if (v == null) return '—';
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
};

// ─── Graphique mensuel ────────────────────────────────────────────
const monthlySeries = computed(() => [
  { name: "Main d'œuvre", data: props.monthly_trend.map(m => m.main_oeuvre) },
  { name: "Matériel",     data: props.monthly_trend.map(m => m.materiel) },
  { name: "Transport",    data: props.monthly_trend.map(m => m.transport) },
  { name: "Autres",       data: props.monthly_trend.map(m => m.autres) },
]);

const monthlyChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, stacked: true, fontFamily: 'Inter, sans-serif' },
  colors: ['#1A2B4A', '#C9A84C', '#10B981', '#94A3B8'],
  plotOptions: {
    bar: { horizontal: false, columnWidth: '40%', borderRadius: 5, borderRadiusApplication: 'end', borderRadiusWhenStacked: 'last' }
  },
  stroke: { width: 0 },
  xaxis: {
    categories: props.monthly_trend.map(m => `${m.month}/${m.year}`),
    labels: { style: { colors: '#64748B', fontSize: '11px', fontWeight: 500 } },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: '#64748B', fontSize: '11px', fontWeight: 500 },
      formatter: v => v >= 1000 ? `${(v/1000).toFixed(0)}k` : v
    }
  },
  grid: { borderColor: '#F1F5F9', strokeDashArray: 4, xaxis: { lines: { show: false } }, yaxis: { lines: { show: true } } },
  tooltip: { theme: 'light', y: { formatter: v => formatAmount(v) } },
  legend: {
    position: 'top', horizontalAlign: 'right', fontSize: '12px',
    fontFamily: 'Inter, sans-serif', fontWeight: 500,
    markers: { radius: 12 }, itemMargin: { horizontal: 10, vertical: 0 }
  }
}));

// ─── Graphique catégories ─────────────────────────────────────────
const categorySeries = computed(() => props.expenses_by_category.map(c => Number(c.total)));

const categoryChartOptions = computed(() => ({
  labels:  props.expenses_by_category.map(c => c.name),
  colors:  props.expenses_by_category.map(c => c.color),
  legend:  { position: 'bottom', fontSize: '12px', fontFamily: 'Inter, sans-serif', fontWeight: 500, itemMargin: { horizontal: 8, vertical: 4 } },
  dataLabels: { enabled: false },
  stroke:  { width: 2, colors: ['#fff'] },
  tooltip: { theme: 'light', y: { formatter: v => formatAmount(v) } },
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: {
          show: true,
          name:  { show: true, fontSize: '13px', fontFamily: 'Inter, sans-serif', fontWeight: 500, color: '#64748B', offsetY: -6 },
          value: { show: true, fontSize: '17px', fontFamily: 'Inter, sans-serif', fontWeight: 800, color: '#1E293B', offsetY: 6, formatter: v => formatAmount(v) },
          total: {
            show: true, label: 'Total dépenses', fontFamily: 'Inter, sans-serif', color: '#64748B', fontWeight: 500,
            formatter: (w) => { const t = w.globals.seriesTotals.reduce((a, b) => a + b, 0); return formatAmount(t); }
          }
        }
      }
    }
  },
}));
</script>

<style scoped>
/* Projets récents — lignes visuelles */
.recent-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin: 0 calc(-1 * var(--space-xl));
  padding: 0 var(--space-xl);
}

.recent-row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: 9px var(--space-md);
  border-radius: var(--radius-md);
  text-decoration: none;
  transition: background .15s ease;
  margin: 0 calc(-1 * var(--space-md));
}

.recent-row:hover { background: rgba(26,43,74,.04); }

.recent-avatar {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: .9rem;
  color: #fff;
  flex-shrink: 0;
}

.recent-info { flex: 1; min-width: 0; }

.recent-name {
  font-size: .875rem;
  font-weight: 600;
  color: var(--color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.recent-client {
  font-size: .72rem;
  color: var(--color-text-muted);
  margin-top: 1px;
}

.recent-right {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  flex-shrink: 0;
}
</style>
