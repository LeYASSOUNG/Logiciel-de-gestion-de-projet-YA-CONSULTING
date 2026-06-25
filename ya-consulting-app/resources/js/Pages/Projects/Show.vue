<template>
  <AppLayout :title="`Projet : ${project.name}`">
    <div class="animate-fade-up" style="animation-delay:.03s">
      <!-- En-tête -->
      <PageHeader :title="project.name" :description="project.client ? `Client : ${project.client.name}` : 'Pas de client associé'">
        <template v-slot:badge>
          <StatusBadge :status="project.status" />
        </template>
        <template v-slot:actions>
          <Button as="Link" :href="route('projects.index')" variant="outline">
            Retour
          </Button>
          <Button v-if="canEdit" as="Link" :href="route('projects.edit', project.id)" variant="primary">
            Modifier le projet
          </Button>
          <Button v-if="canAddExpense" as="Link" :href="route('expenses.create-for-project', project.id)" variant="accent">
            <template v-slot:icon-left>
              <Icon name="plus" :size="16" />
            </template>
            Ajouter une dépense
          </Button>
        </template>
      </PageHeader>

      <!-- Erreurs éventuelles (par ex. impossibilité de supprimer) -->
      <div v-if="$page.props.errors.delete" class="alert alert-danger mb-lg">
        {{ $page.props.errors.delete }}
      </div>

      <!-- KPIs Financiers -->
      <div class="stats-grid mb-xl">
        <StatCard
          class="stagger-1"
          label="Budget alloué"
          :value="formatAmount(project.budget)"
          icon="banknotes"
          color="accent"
        />

        <StatCard
          class="stagger-2"
          label="Dépenses cumulées"
          :value="formatAmount(project.total_expenses)"
          icon="credit-card"
          color="danger"
          colorValueClass="text-danger"
        />

        <StatCard
          class="stagger-3"
          label="Marge brute"
          :value="(project.gross_gain >= 0 ? '+' : '') + formatAmount(project.gross_gain)"
          icon="presentation-chart-line"
          :color="project.is_profitable ? 'success' : 'danger'"
          :colorValueClass="project.is_profitable ? 'text-success' : 'text-danger'"
        >
          <template v-slot:footer>
            <div :class="project.is_profitable ? 'text-success' : 'text-danger'" style="font-weight: 600; font-size: 0.85rem;">
              {{ project.is_profitable ? '+' : '' }}{{ project.profitability }}% rentabilité
            </div>
          </template>
        </StatCard>
      </div>

      <!-- Informations Projet + Graphique répartition -->
      <div class="grid-3 mb-xl">
        <!-- Infos Détails (Col 1 & 2) -->
        <Card title="Description & Planning" style="grid-column: span 2">
          <p v-if="project.description" style="margin-top:0; white-space:pre-line; color:var(--color-text)">
            {{ project.description }}
          </p>
          <p v-else style="margin-top:0; color:var(--color-text-muted); font-style:italic">
            Aucune description fournie.
          </p>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-lg); margin-top:var(--space-lg); border-top:1px solid var(--color-border); padding-top:var(--space-md)">
            <div>
              <span style="font-size:.75rem; color:var(--color-text-muted); text-transform:uppercase; font-weight:600">Calendrier</span>
              <div style="margin-top:4px; font-size:.875rem">
                <div>Début : <strong>{{ formatDate(project.start_date) }}</strong></div>
                <div>Fin prévisionnelle : <strong>{{ formatDate(project.planned_end_date) }}</strong></div>
                <div v-if="project.actual_end_date">Fin réelle : <strong class="text-success">{{ formatDate(project.actual_end_date) }}</strong></div>
              </div>
            </div>
            <div>
              <span style="font-size:.75rem; color:var(--color-text-muted); text-transform:uppercase; font-weight:600">Contacts clés</span>
              <div style="margin-top:4px; font-size:.875rem">
                <div v-if="project.client?.contact_email">Client email : <strong>{{ project.client.contact_email }}</strong></div>
                <div v-if="project.client?.contact_phone">Client tél : <strong>{{ project.client.contact_phone }}</strong></div>
                <div v-if="project.supplier_contact">Fournisseur : <strong>{{ project.supplier_contact }}</strong></div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Graphique Dépenses par catégorie (Col 3) -->
        <Card title="Structure des Coûts">
          <apexchart
            v-if="expenses_by_category.length"
            type="donut"
            height="200"
            :options="chartOptions"
            :series="chartSeries"
          />
          <div v-else style="text-align:center; color:var(--color-text-muted); padding: 40px 0;">
            Aucune dépense enregistrée sur ce projet.
          </div>
        </Card>
      </div>

      <!-- Suivi Analytique du Budget -->
      <Card title="Suivi Analytique du Budget" class="mb-xl">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:var(--space-lg);">

          <!-- Main d'oeuvre — Indigo -->
          <div class="analytics-card ax-indigo">
            <div class="analytics-card-header">
              <div>
                <div class="analytics-card-label">Main d'œuvre</div>
              </div>
              <div style="display:flex; align-items:center; gap:8px;">
                <span class="analytics-card-pct">{{ pct(project.expenses_labor, project.budget_labor) }}%</span>
                <div class="analytics-card-icon">
                  <Icon name="user-group" :size="16" />
                </div>
              </div>
            </div>
            <div class="analytics-card-value">{{ formatAmount(project.expenses_labor) }}</div>
            <div class="analytics-card-sub">Budget : {{ formatAmount(project.budget_labor) }}</div>
            <div class="analytics-bar">
              <div class="analytics-bar-fill" :class="{ overrun: Number(project.expenses_labor) > Number(project.budget_labor) }"
                :style="`width: ${Math.min(pct(project.expenses_labor, project.budget_labor), 100)}%`" />
            </div>
            <div v-if="Number(project.expenses_labor) > Number(project.budget_labor)" class="analytics-overrun">
              <Icon name="exclamation-triangle" :size="11" />
              Dépassement de {{ formatAmount(project.expenses_labor - project.budget_labor) }}
            </div>
          </div>

          <!-- Matériel — Amber -->
          <div class="analytics-card ax-amber">
            <div class="analytics-card-header">
              <div>
                <div class="analytics-card-label">Matériel</div>
              </div>
              <div style="display:flex; align-items:center; gap:8px;">
                <span class="analytics-card-pct">{{ pct(project.expenses_material, project.budget_material) }}%</span>
                <div class="analytics-card-icon">
                  <Icon name="wrench-screwdriver" :size="16" />
                </div>
              </div>
            </div>
            <div class="analytics-card-value">{{ formatAmount(project.expenses_material) }}</div>
            <div class="analytics-card-sub">Budget : {{ formatAmount(project.budget_material) }}</div>
            <div class="analytics-bar">
              <div class="analytics-bar-fill" :class="{ overrun: Number(project.expenses_material) > Number(project.budget_material) }"
                :style="`width: ${Math.min(pct(project.expenses_material, project.budget_material), 100)}%`" />
            </div>
            <div v-if="Number(project.expenses_material) > Number(project.budget_material)" class="analytics-overrun">
              <Icon name="exclamation-triangle" :size="11" />
              Dépassement de {{ formatAmount(project.expenses_material - project.budget_material) }}
            </div>
          </div>

          <!-- Transport — Emerald -->
          <div class="analytics-card ax-emerald">
            <div class="analytics-card-header">
              <div>
                <div class="analytics-card-label">Transport</div>
              </div>
              <div style="display:flex; align-items:center; gap:8px;">
                <span class="analytics-card-pct">{{ pct(project.expenses_transport, project.budget_transport) }}%</span>
                <div class="analytics-card-icon">
                  <Icon name="truck" :size="16" />
                </div>
              </div>
            </div>
            <div class="analytics-card-value">{{ formatAmount(project.expenses_transport) }}</div>
            <div class="analytics-card-sub">Budget : {{ formatAmount(project.budget_transport) }}</div>
            <div class="analytics-bar">
              <div class="analytics-bar-fill" :class="{ overrun: Number(project.expenses_transport) > Number(project.budget_transport) }"
                :style="`width: ${Math.min(pct(project.expenses_transport, project.budget_transport), 100)}%`" />
            </div>
            <div v-if="Number(project.expenses_transport) > Number(project.budget_transport)" class="analytics-overrun">
              <Icon name="exclamation-triangle" :size="11" />
              Dépassement de {{ formatAmount(project.expenses_transport - project.budget_transport) }}
            </div>
          </div>

          <!-- Autres — Slate -->
          <div class="analytics-card ax-slate">
            <div class="analytics-card-header">
              <div>
                <div class="analytics-card-label">Autres coûts</div>
              </div>
              <div style="display:flex; align-items:center; gap:8px;">
                <span class="analytics-card-pct">{{ pct(project.expenses_other, project.budget_other) }}%</span>
                <div class="analytics-card-icon">
                  <Icon name="ellipsis-horizontal-circle" :size="16" />
                </div>
              </div>
            </div>
            <div class="analytics-card-value">{{ formatAmount(project.expenses_other) }}</div>
            <div class="analytics-card-sub">Budget : {{ formatAmount(project.budget_other) }}</div>
            <div class="analytics-bar">
              <div class="analytics-bar-fill" :class="{ overrun: Number(project.expenses_other) > Number(project.budget_other) }"
                :style="`width: ${Math.min(pct(project.expenses_other, project.budget_other), 100)}%`" />
            </div>
            <div v-if="Number(project.expenses_other) > Number(project.budget_other)" class="analytics-overrun">
              <Icon name="exclamation-triangle" :size="11" />
              Dépassement de {{ formatAmount(project.expenses_other - project.budget_other) }}
            </div>
          </div>

        </div>
      </Card>

      <!-- Liste des Dépenses du projet -->
      <Card :title="`Dépenses Enregistrées (${project.expenses.length})`" class="mb-xl">
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Auteur</th>
                <th>Justificatif</th>
                <th class="text-right">Montant</th>
                <th v-if="canAddExpense" style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="exp in project.expenses" :key="exp.id">
                <td>{{ formatDate(exp.date) }}</td>
                <td>
                  <span style="display:inline-flex; align-items:center; gap:6px;">
                    <span :style="`width:10px; height:10px; border-radius:50%; background-color:${exp.category?.color || '#ccc'}`"></span>
                    {{ exp.category?.name || 'N/A' }}
                  </span>
                </td>
                <td>{{ exp.description || '—' }}</td>
                <td>{{ exp.creator?.name || '—' }}</td>
                <td>
                  <a v-if="exp.receipt_path" :href="`/storage/${exp.receipt_path}`" target="_blank"
                    style="color:var(--color-accent-dark); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <Icon name="document-arrow-down" :size="14" />
                    Justificatif
                  </a>
                  <span v-else style="color:var(--color-text-light);">Aucun</span>
                </td>
                <td class="amount-neutral text-right">{{ formatAmount(exp.amount) }}</td>
                <td v-if="canAddExpense" style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <Button as="Link" :href="route('expenses.edit', exp.id)" variant="outline" size="sm" style="padding: 6px 10px;">
                      <Icon name="pencil-square" :size="14" />
                    </Button>
                    <Button variant="outline" size="sm" class="text-danger" style="padding: 6px 10px;" @click="deleteExpense(exp.id)">
                      <Icon name="trash" :size="14" />
                    </Button>
                  </div>
                </td>
              </tr>
              <tr v-if="!project.expenses.length">
                <td colspan="7" style="text-align:center; color:var(--color-text-muted); padding:30px 0;">
                  Aucune dépense enregistrée sur ce projet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Section de suppression (admin uniquement) -->
      <div v-if="canDelete" style="margin-top:var(--space-2xl); border-top:1px solid var(--color-border); padding-top:var(--space-xl)">
        <Card class="danger-zone-card">
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:var(--space-md)">
            <div>
              <div class="danger-zone-header">
                <div class="danger-zone-icon">
                  <Icon name="shield-exclamation" :size="18" />
                </div>
                <h3 style="margin:0; color:var(--color-danger); font-size:1rem; font-weight:700;">Zone de danger</h3>
              </div>
              <p style="margin:0 0 0 46px; font-size:.875rem; color:var(--color-text-muted); line-height:1.5;">
                La suppression du projet est <strong>définitive et irréversible</strong>. Cette action est impossible si des dépenses sont rattachées au projet.
              </p>
            </div>
            <Button variant="danger" @click="deleteProject">
              <template v-slot:icon-left><Icon name="trash" :size="15" /></template>
              Supprimer le projet
            </Button>
          </div>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import StatCard from '@/Components/StatCard.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  project: Object,
  expenses_by_category: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// ─── Autorisations ──────────────────────────────────────────────
const canEdit = computed(() => {
  if (user.value.roles?.includes('admin')) return true;
  if (user.value.roles?.includes('chef_projet')) {
    return props.project.created_by === user.value.id;
  }
  return false;
});

const canAddExpense = computed(() => {
  if (user.value.roles?.includes('admin')) return true;
  if (user.value.roles?.includes('chef_projet')) {
    return props.project.created_by === user.value.id;
  }
  return false;
});

const canDelete = computed(() => {
  return user.value.roles?.includes('admin');
});

// ─── Formatage ──────────────────────────────────────────────────
const formatAmount = (v) => {
  if (v == null) return '—';
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR');
};

const pct = (expenses, budget) => {
  if (!budget || Number(budget) === 0) return 0;
  return Math.round((Number(expenses) / Number(budget)) * 100);
};

// ─── Actions ────────────────────────────────────────────────────
const deleteProject = () => {
  if (confirm('Voulez-vous vraiment supprimer ce projet ?')) {
    router.delete(route('projects.destroy', props.project.id));
  }
};

const deleteExpense = (expenseId) => {
  if (confirm('Voulez-vous vraiment supprimer cette dépense ?')) {
    router.delete(route('expenses.destroy', expenseId));
  }
};

// ─── ApexCharts options ─────────────────────────────────────────
const chartSeries = computed(() => props.expenses_by_category.map(c => Number(c.total)));

const chartOptions = computed(() => ({
  labels: props.expenses_by_category.map(c => c.name),
  colors: props.expenses_by_category.map(c => c.color),
  legend: { position: 'bottom', fontSize: '11px', fontFamily: 'Inter' },
  dataLabels: { enabled: false },
  stroke: { width: 1.5 },
  tooltip: { y: { formatter: v => formatAmount(v) } },
  plotOptions: { pie: { donut: { size: '60%' } } },
}));
</script>
