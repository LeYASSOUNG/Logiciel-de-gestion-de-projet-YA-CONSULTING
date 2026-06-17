<template>
  <AppLayout :title="`Projet : ${project.name}`">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <div style="display:flex; align-items:center; gap:10px;">
            <h1>{{ project.name }}</h1>
            <StatusBadge :status="project.status" />
          </div>
          <p>Client : <strong style="color:var(--color-primary)">{{ project.client?.name || 'N/A' }}</strong></p>
        </div>
        <div style="display:flex; gap:10px;">
          <Link :href="route('projects.index')" class="btn btn-outline">
            Retour
          </Link>
          <Link v-if="canEdit" :href="route('projects.edit', project.id)" class="btn btn-primary">
            Modifier le projet
          </Link>
          <Link v-if="canAddExpense" :href="route('expenses.create-for-project', project.id)" class="btn btn-accent">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter une dépense
          </Link>
        </div>
      </div>

      <!-- Erreurs éventuelles (par ex. impossibilité de supprimer) -->
      <div v-if="$page.props.errors.delete" class="alert alert-danger mb-lg">
        {{ $page.props.errors.delete }}
      </div>

      <!-- KPIs Financiers -->
      <div class="stats-grid">
        <div class="stat-card" style="--stat-color: var(--color-accent); --stat-bg: rgba(201,168,76,.1)">
          <div class="stat-card-icon">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-card-value">{{ formatAmount(project.budget) }}</div>
          <div class="stat-card-label">Budget alloué</div>
        </div>

        <div class="stat-card" style="--stat-color: var(--color-danger); --stat-bg: rgba(239,68,68,.08)">
          <div class="stat-card-icon">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="stat-card-value text-danger">{{ formatAmount(project.total_expenses) }}</div>
          <div class="stat-card-label">Dépenses cumulées</div>
        </div>

        <div class="stat-card" :style="`--stat-color: ${project.is_profitable ? 'var(--color-success)' : 'var(--color-danger)'}; --stat-bg: ${project.is_profitable ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.08)'}`">
          <div class="stat-card-icon">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
          <div class="stat-card-value" :class="project.is_profitable ? 'text-success' : 'text-danger'">
            {{ project.gross_gain >= 0 ? '+' : '' }}{{ formatAmount(project.gross_gain) }}
          </div>
          <div class="stat-card-label">Marge brute</div>
          <div style="margin-top: 8px; font-size: .8rem;" :class="project.is_profitable ? 'text-success' : 'text-danger'">
            {{ project.is_profitable ? '+' : '' }}{{ project.profitability }}% rentabilité
          </div>
        </div>
      </div>

      <!-- Informations Projet + Graphique répartition -->
      <div class="grid-3 mb-xl">
        <!-- Infos Détails (Col 1 & 2) -->
        <div class="card" style="grid-column: span 2">
          <div class="card-header">
            <h2 class="card-title">Description & Planning</h2>
          </div>
          <div class="card-body">
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
          </div>
        </div>

        <!-- Graphique Dépenses par catégorie (Col 3) -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Structure des Coûts</h2>
          </div>
          <div class="card-body">
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
          </div>
        </div>
      </div>

      <!-- Suivi Analytique du Budget -->
      <div class="card mb-xl animate-fade-up">
        <div class="card-header">
          <h2 class="card-title">💼 Suivi Analytique du Budget</h2>
        </div>
        <div class="card-body">
          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:var(--space-lg);">
            <!-- Main d'oeuvre -->
            <div style="background:var(--color-bg-light); padding:16px; border-radius:8px; border:1px solid var(--color-border);">
              <div style="display:flex; justify-content:space-between; font-size:.75rem; color:var(--color-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                <span>Main d'œuvre</span>
                <span>{{ pct(project.expenses_labor, project.budget_labor) }}%</span>
              </div>
              <div style="font-size:1.15rem; font-weight:700; margin:8px 0 4px; color:var(--color-primary);">
                {{ formatAmount(project.expenses_labor) }}
              </div>
              <div style="font-size:.8rem; color:var(--color-text-muted);">
                Budget : {{ formatAmount(project.budget_labor) }}
              </div>
              <div style="width:100%; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin-top:10px;">
                <div :style="`width: ${Math.min(pct(project.expenses_labor, project.budget_labor), 100)}%; height:100%; background: ${Number(project.expenses_labor) > Number(project.budget_labor) ? 'var(--color-danger)' : '#6366F1'};`"/>
              </div>
              <div v-if="Number(project.expenses_labor) > Number(project.budget_labor)" style="font-size:.7rem; color:var(--color-danger); margin-top:6px; font-weight:600;">
                ⚠️ Dépassement de {{ formatAmount(project.expenses_labor - project.budget_labor) }}
              </div>
            </div>

            <!-- Materiel -->
            <div style="background:var(--color-bg-light); padding:16px; border-radius:8px; border:1px solid var(--color-border);">
              <div style="display:flex; justify-content:space-between; font-size:.75rem; color:var(--color-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                <span>Matériel</span>
                <span>{{ pct(project.expenses_material, project.budget_material) }}%</span>
              </div>
              <div style="font-size:1.15rem; font-weight:700; margin:8px 0 4px; color:var(--color-primary);">
                {{ formatAmount(project.expenses_material) }}
              </div>
              <div style="font-size:.8rem; color:var(--color-text-muted);">
                Budget : {{ formatAmount(project.budget_material) }}
              </div>
              <div style="width:100%; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin-top:10px;">
                <div :style="`width: ${Math.min(pct(project.expenses_material, project.budget_material), 100)}%; height:100%; background: ${Number(project.expenses_material) > Number(project.budget_material) ? 'var(--color-danger)' : '#F59E0B'};`"/>
              </div>
              <div v-if="Number(project.expenses_material) > Number(project.budget_material)" style="font-size:.7rem; color:var(--color-danger); margin-top:6px; font-weight:600;">
                ⚠️ Dépassement de {{ formatAmount(project.expenses_material - project.budget_material) }}
              </div>
            </div>

            <!-- Transport -->
            <div style="background:var(--color-bg-light); padding:16px; border-radius:8px; border:1px solid var(--color-border);">
              <div style="display:flex; justify-content:space-between; font-size:.75rem; color:var(--color-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                <span>Transport</span>
                <span>{{ pct(project.expenses_transport, project.budget_transport) }}%</span>
              </div>
              <div style="font-size:1.15rem; font-weight:700; margin:8px 0 4px; color:var(--color-primary);">
                {{ formatAmount(project.expenses_transport) }}
              </div>
              <div style="font-size:.8rem; color:var(--color-text-muted);">
                Budget : {{ formatAmount(project.budget_transport) }}
              </div>
              <div style="width:100%; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin-top:10px;">
                <div :style="`width: ${Math.min(pct(project.expenses_transport, project.budget_transport), 100)}%; height:100%; background: ${Number(project.expenses_transport) > Number(project.budget_transport) ? 'var(--color-danger)' : '#10B981'};`"/>
              </div>
              <div v-if="Number(project.expenses_transport) > Number(project.budget_transport)" style="font-size:.7rem; color:var(--color-danger); margin-top:6px; font-weight:600;">
                ⚠️ Dépassement de {{ formatAmount(project.expenses_transport - project.budget_transport) }}
              </div>
            </div>

            <!-- Autres -->
            <div style="background:var(--color-bg-light); padding:16px; border-radius:8px; border:1px solid var(--color-border);">
              <div style="display:flex; justify-content:space-between; font-size:.75rem; color:var(--color-text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                <span>Autres coûts</span>
                <span>{{ pct(project.expenses_other, project.budget_other) }}%</span>
              </div>
              <div style="font-size:1.15rem; font-weight:700; margin:8px 0 4px; color:var(--color-primary);">
                {{ formatAmount(project.expenses_other) }}
              </div>
              <div style="font-size:.8rem; color:var(--color-text-muted);">
                Budget : {{ formatAmount(project.budget_other) }}
              </div>
              <div style="width:100%; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin-top:10px;">
                <div :style="`width: ${Math.min(pct(project.expenses_other, project.budget_other), 100)}%; height:100%; background: ${Number(project.expenses_other) > Number(project.budget_other) ? 'var(--color-danger)' : '#6B7280'};`"/>
              </div>
              <div v-if="Number(project.expenses_other) > Number(project.budget_other)" style="font-size:.7rem; color:var(--color-danger); margin-top:6px; font-weight:600;">
                ⚠️ Dépassement de {{ formatAmount(project.expenses_other - project.budget_other) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des Dépenses du projet -->
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Dépenses Enregistrées ({{ project.expenses.length }})</h2>
        </div>
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
                <th v-if="canAddExpense" style="width:100px"></th>
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
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Justificatif
                  </a>
                  <span v-else style="color:var(--color-text-light);">Aucun</span>
                </td>
                <td class="amount-neutral text-right">{{ formatAmount(exp.amount) }}</td>
                <td v-if="canAddExpense" style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <Link :href="route('expenses.edit', exp.id)" class="btn btn-outline btn-sm" style="padding:4px 8px;">
                      ✎
                    </Link>
                    <button @click="deleteExpense(exp.id)" class="btn btn-outline btn-sm text-danger" style="padding:4px 8px; border-color:var(--color-border)">
                      ✕
                    </button>
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
      </div>

      <!-- Section de suppression (admin uniquement) -->
      <div v-if="canDelete" style="margin-top:var(--space-2xl); border-top:1px solid var(--color-border); padding-top:var(--space-xl)">
        <div class="card" style="border-color:rgba(239,68,68,.3); background:rgba(239,68,68,.02)">
          <div class="card-body" style="display:flex; justify-content:between; align-items:center;">
            <div>
              <h3 style="margin:0 0 6px 0; color:var(--color-danger); font-size:1rem;">Zone de danger</h3>
              <p style="margin:0; font-size:.875rem; color:var(--color-text-muted);">
                La suppression du projet est définitive. Impossible si des dépenses y sont rattachées.
              </p>
            </div>
            <button @click="deleteProject" class="btn btn-danger">
              Supprimer le projet
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

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
