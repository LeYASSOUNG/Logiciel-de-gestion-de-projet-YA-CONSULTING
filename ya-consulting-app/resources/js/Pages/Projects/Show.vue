<template>
  <AppLayout :title="`Projet : ${project.name}`">
    <!-- En-tête Premium Animé -->
    <div class="page-header-premium slide-down">
      <div class="header-content">
        <div class="header-subtitle">
          <span class="modern-badge" :class="getStatusBadge(project.status)">
            {{ getStatusLabel(project.status) }}
          </span>
          <span class="client-name" v-if="project.client">
            <Icon name="building-office" :size="14" /> {{ project.client.name }}
          </span>
        </div>
        <h1 class="text-gradient">{{ project.name }}</h1>
      </div>
      <div class="header-actions">
        <Link :href="route('projects.index')" class="btn-action-outline" title="Retour aux projets">
          <Icon name="arrow-left" :size="16" /> Retour
        </Link>
        <Link v-if="canEdit" :href="route('projects.edit', project.id)" class="btn-action-outline">
          <Icon name="pencil-square" :size="16" /> Modifier
        </Link>
        <Link v-if="canAddExpense" :href="route('payments.create-for-project', project.id)" class="btn-premium" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);">
          <Icon name="banknotes" :size="16" /> <span>Encaisser</span>
        </Link>
        <Link v-if="canAddExpense" :href="route('expenses.create-for-project', project.id)" class="btn-premium">
          <Icon name="plus" :size="16" /> <span>Dépenser</span>
        </Link>
      </div>
    </div>

    <!-- Alertes -->
    <div v-if="$page.props.errors.delete" class="alert-premium slide-down delay-1">
      <Icon name="exclamation-circle" :size="20" />
      <span>{{ $page.props.errors.delete }}</span>
    </div>

    <!-- KPIs Financiers (Glassmorphism) -->
    <div v-if="canViewFinances" class="kpi-grid fade-in-up delay-2">

      <!-- Budget Initial -->
      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-slate">
          <Icon name="archive-box" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Budget Initial</div>
          <div class="kpi-value text-slate">{{ formatAmount(project.initial_budget || project.budget) }}</div>
        </div>
      </div>

      <!-- Budget Actuel -->
      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-blue">
          <Icon name="banknotes" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Budget Actuel</div>
          <div class="kpi-value text-blue">{{ formatAmount(project.budget) }}</div>
          <div v-if="project.budget != project.initial_budget" class="kpi-subtext mt-xs" :class="project.budget > project.initial_budget ? 'text-emerald' : 'text-red'">
            {{ project.budget > project.initial_budget ? '+' : '' }}{{ formatAmount(project.budget - project.initial_budget) }}
          </div>
        </div>
      </div>

      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-emerald">
          <Icon name="arrow-down-tray" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Montant Encaissé</div>
          <div class="kpi-value text-emerald">{{ formatAmount(project.total_paid) }}</div>
        </div>
      </div>

      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper" :class="project.balance_due == 0 ? 'bg-slate' : 'bg-red'">
          <Icon name="scale" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Reste à payer</div>
          <div class="kpi-value" :class="project.balance_due == 0 ? 'text-slate' : 'text-red'">{{ formatAmount(project.balance_due) }}</div>
        </div>
      </div>

      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-red">
          <Icon name="credit-card" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Dépenses Cumulées</div>
          <div class="kpi-value text-red">{{ formatAmount(project.total_expenses) }}</div>
        </div>
      </div>

      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper" :class="project.is_profitable ? 'bg-emerald' : 'bg-red'">
          <Icon name="presentation-chart-line" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Marge Brute</div>
          <div class="kpi-value" :class="project.is_profitable ? 'text-emerald' : 'text-red'">
            {{ project.gross_gain >= 0 ? '+' : '' }}{{ formatAmount(project.gross_gain) }}
          </div>
          <div class="kpi-subtext" :class="project.is_profitable ? 'text-emerald' : 'text-red'">
            {{ project.is_profitable ? '+' : '' }}{{ project.profitability }}% de rentabilité
          </div>
        </div>
      </div>
    </div>

    <!-- KPIs Financiers Restreints (Pour le Client) -->
    <div v-if="!canViewFinances" class="kpi-grid fade-in-up delay-2">
      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-blue">
          <Icon name="banknotes" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Budget Actuel</div>
          <div class="kpi-value text-blue">{{ formatAmount(project.budget) }}</div>
        </div>
      </div>

      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-emerald">
          <Icon name="arrow-down-tray" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Montant Payé</div>
          <div class="kpi-value text-emerald">{{ formatAmount(project.total_paid) }}</div>
        </div>
      </div>

      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper" :class="project.balance_due == 0 ? 'bg-slate' : 'bg-red'">
          <Icon name="scale" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Reste à payer</div>
          <div class="kpi-value" :class="project.balance_due == 0 ? 'text-slate' : 'text-red'">{{ formatAmount(project.balance_due) }}</div>
        </div>
      </div>
    </div>

    <!-- Section 2: Détails et Graphique -->
    <div class="details-grid fade-in-up delay-3">
      <!-- Informations Projet -->
      <div class="glass-panel col-span-2 flex-col">
        <div class="panel-header border-b">
          <h2><Icon name="document-text" :size="18" class="mr-2 text-gold" /> Description & Planning</h2>
        </div>
        <div class="panel-body flex-1">
          <div class="description-box">
            <p v-if="project.description">{{ project.description }}</p>
            <p v-else class="text-muted italic">Aucune description fournie pour ce projet.</p>
          </div>

          <div class="info-split mt-lg pt-lg border-t">
            <div class="info-block">
              <span class="info-label">Calendrier</span>
              <ul class="info-list">
                <li><Icon name="calendar" :size="14" /> Début : <strong>{{ formatDate(project.start_date) }}</strong></li>
                <li><Icon name="clock" :size="14" /> Fin prévue : <strong>{{ formatDate(project.planned_end_date) }}</strong></li>
                <li v-if="project.actual_end_date"><Icon name="check-circle" :size="14" class="text-emerald" /> Fin réelle : <strong class="text-emerald">{{ formatDate(project.actual_end_date) }}</strong></li>
              </ul>
            </div>
            <div class="info-block">
              <span class="info-label">Contacts Clés</span>
              <ul class="info-list">
                <li v-if="project.client?.contact_email"><Icon name="envelope" :size="14" /> {{ project.client.contact_email }}</li>
                <li v-if="project.client?.contact_phone"><Icon name="phone" :size="14" /> {{ project.client.contact_phone }}</li>
                <li v-if="project.supplier_contact"><Icon name="truck" :size="14" /> Fournisseur: <strong>{{ project.supplier_contact }}</strong></li>
                <li v-if="!project.client?.contact_email && !project.client?.contact_phone && !project.supplier_contact" class="text-muted">Aucun contact enregistré</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Graphique Dépenses -->
      <div v-if="canViewFinances" class="glass-panel flex-col">
        <div class="panel-header border-b">
          <h2><Icon name="chart-pie" :size="18" class="mr-2 text-gold" /> Structure des Coûts</h2>
        </div>
        <div class="panel-body flex-1 flex-center">
          <apexchart
            v-if="expenses_by_category.length"
            type="donut"
            height="250"
            :options="chartOptions"
            :series="chartSeries"
          />
          <div v-else class="empty-state-mini">
            <Icon name="chart-pie" :size="32" class="text-muted mb-xs" />
            <p>Aucune dépense enregistrée</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Suivi Analytique du Budget -->
    <div v-if="canViewFinances" class="glass-panel fade-in-up delay-4 mb-xl">
      <div class="panel-header border-b">
        <h2><Icon name="calculator" :size="18" class="mr-2 text-gold" /> Suivi Analytique du Budget</h2>
      </div>
      <div class="panel-body">
        <div class="analytics-grid">

          <!-- Main d'oeuvre -->
          <div class="analytics-item glass">
            <div class="ax-header text-blue">
              <span class="ax-title">Main d'œuvre</span>
              <span class="ax-pct">{{ pct(project.expenses_labor, project.budget_labor) }}%</span>
            </div>
            <div class="ax-amounts">
              <div class="ax-spent">{{ formatAmount(project.expenses_labor) }}</div>
              <div class="ax-budget">sur {{ formatAmount(project.budget_labor) }}</div>
            </div>
            <div class="progress-track mt-sm">
              <div class="progress-fill animated bg-blue"
                   :class="{ 'bg-red': Number(project.expenses_labor) > Number(project.budget_labor) }"
                   :style="{ '--target-width': Math.min(pct(project.expenses_labor, project.budget_labor), 100) + '%' }">
              </div>
            </div>
            <div v-if="Number(project.expenses_labor) > Number(project.budget_labor)" class="ax-overrun text-red mt-xs">
              <Icon name="exclamation-triangle" :size="12" /> Dépassement : {{ formatAmount(project.expenses_labor - project.budget_labor) }}
            </div>
          </div>

          <!-- Matériel -->
          <div class="analytics-item glass">
            <div class="ax-header text-gold">
              <span class="ax-title">Matériel</span>
              <span class="ax-pct">{{ pct(project.expenses_material, project.budget_material) }}%</span>
            </div>
            <div class="ax-amounts">
              <div class="ax-spent">{{ formatAmount(project.expenses_material) }}</div>
              <div class="ax-budget">sur {{ formatAmount(project.budget_material) }}</div>
            </div>
            <div class="progress-track mt-sm">
              <div class="progress-fill animated bg-gold"
                   :class="{ 'bg-red': Number(project.expenses_material) > Number(project.budget_material) }"
                   :style="{ '--target-width': Math.min(pct(project.expenses_material, project.budget_material), 100) + '%' }">
              </div>
            </div>
            <div v-if="Number(project.expenses_material) > Number(project.budget_material)" class="ax-overrun text-red mt-xs">
              <Icon name="exclamation-triangle" :size="12" /> Dépassement : {{ formatAmount(project.expenses_material - project.budget_material) }}
            </div>
          </div>

          <!-- Transport -->
          <div class="analytics-item glass">
            <div class="ax-header text-emerald">
              <span class="ax-title">Transport</span>
              <span class="ax-pct">{{ pct(project.expenses_transport, project.budget_transport) }}%</span>
            </div>
            <div class="ax-amounts">
              <div class="ax-spent">{{ formatAmount(project.expenses_transport) }}</div>
              <div class="ax-budget">sur {{ formatAmount(project.budget_transport) }}</div>
            </div>
            <div class="progress-track mt-sm">
              <div class="progress-fill animated bg-emerald"
                   :class="{ 'bg-red': Number(project.expenses_transport) > Number(project.budget_transport) }"
                   :style="{ '--target-width': Math.min(pct(project.expenses_transport, project.budget_transport), 100) + '%' }">
              </div>
            </div>
            <div v-if="Number(project.expenses_transport) > Number(project.budget_transport)" class="ax-overrun text-red mt-xs">
              <Icon name="exclamation-triangle" :size="12" /> Dépassement : {{ formatAmount(project.expenses_transport - project.budget_transport) }}
            </div>
          </div>

          <!-- Autres -->
          <div class="analytics-item glass">
            <div class="ax-header text-slate">
              <span class="ax-title">Autres Coûts</span>
              <span class="ax-pct">{{ pct(project.expenses_other, project.budget_other) }}%</span>
            </div>
            <div class="ax-amounts">
              <div class="ax-spent">{{ formatAmount(project.expenses_other) }}</div>
              <div class="ax-budget">sur {{ formatAmount(project.budget_other) }}</div>
            </div>
            <div class="progress-track mt-sm">
              <div class="progress-fill animated bg-slate"
                   :class="{ 'bg-red': Number(project.expenses_other) > Number(project.budget_other) }"
                   :style="{ '--target-width': Math.min(pct(project.expenses_other, project.budget_other), 100) + '%' }">
              </div>
            </div>
            <div v-if="Number(project.expenses_other) > Number(project.budget_other)" class="ax-overrun text-red mt-xs">
              <Icon name="exclamation-triangle" :size="12" /> Dépassement : {{ formatAmount(project.expenses_other - project.budget_other) }}
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Liste des Paiements / Encaissements -->
    <div v-if="canViewFinances" class="glass-panel fade-in-up delay-4 mb-xl">
      <div class="panel-header border-b">
        <h2>
          <Icon name="banknotes" :size="18" class="mr-2 text-emerald" />
          Historique des Encaissements
          <span class="badge-count">{{ project.payments ? project.payments.length : 0 }}</span>
        </h2>
        <Link v-if="canAddExpense" :href="route('payments.create-for-project', project.id)" class="btn-action-outline">
          <Icon name="plus" :size="14" /> Nouvel encaissement
        </Link>
      </div>
      <div class="panel-body p-0">
        <div class="table-container">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Date</th>
                <th class="text-right">Montant</th>
                <th>Méthode</th>
                <th>Référence</th>
                <th>Saisi par</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in project.payments" :key="payment.id" class="table-row-animate">
                <td class="text-muted text-xs font-medium">{{ formatDate(payment.payment_date) }}</td>
                <td class="text-right font-bold text-emerald">{{ formatAmount(payment.amount) }}</td>
                <td>
                  <span class="modern-badge bg-slate">{{ payment.payment_method || 'Non précisé' }}</span>
                </td>
                <td class="text-muted">{{ payment.reference || '—' }}</td>
                <td>
                  <span class="user-tag"><Icon name="user" :size="12"/> {{ payment.creator?.name || '—' }}</span>
                </td>
                <td class="text-right">
                  <div class="action-buttons" style="justify-content: flex-end;">
                    <button v-if="canEdit" @click="confirmDeletePayment(payment)" class="btn-action delete" title="Supprimer">
                      <Icon name="trash" :size="14" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!project.payments || project.payments.length === 0">
                <td colspan="6" class="empty-state">
                  <Icon name="inbox" :size="32" class="text-muted mb-2" />
                  <p>Aucun paiement enregistré pour le moment.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Liste des Dépenses du projet -->
    <div v-if="canViewFinances" class="glass-panel fade-in-up delay-5 mb-xl">
      <div class="panel-header border-b">
        <h2>
          <Icon name="queue-list" :size="18" class="mr-2 text-gold" />
          Dépenses Enregistrées
          <span class="badge-count">{{ project.expenses.length }}</span>
        </h2>
      </div>

      <div class="table-container">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Catégorie</th>
              <th>Description</th>
              <th>Auteur</th>
              <th>Justificatif</th>
              <th class="text-right">Montant</th>
              <th v-if="canAddExpense" class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="exp in project.expenses" :key="exp.id" class="table-row-animate">
              <td class="text-muted text-xs font-medium">{{ formatDate(exp.date) }}</td>
              <td>
                <span class="category-tag">
                  <span class="color-dot" :style="{ backgroundColor: exp.category?.color || '#ccc' }"></span>
                  {{ exp.category?.name || 'N/A' }}
                </span>
              </td>
              <td>{{ exp.description || '—' }}</td>
              <td><span class="user-tag"><Icon name="user" :size="12"/> {{ exp.creator?.name || '—' }}</span></td>
              <td>
                <a v-if="exp.receipt_path" :href="`/storage/${exp.receipt_path}`" target="_blank" class="receipt-link">
                  <Icon name="document-arrow-down" :size="14" /> Télécharger
                </a>
                <span v-else class="text-muted text-xs italic">Aucun</span>
              </td>
              <td class="text-right font-bold text-slate">{{ formatAmount(exp.amount) }}</td>
              <td v-if="canAddExpense" class="text-right">
                <div class="action-buttons">
                  <Link :href="route('expenses.edit', exp.id)" class="btn-action edit" title="Modifier">
                    <Icon name="pencil-square" :size="14" />
                  </Link>
                  <button class="btn-action delete" title="Supprimer" @click="deleteExpense(exp.id)">
                    <Icon name="trash" :size="14" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!project.expenses.length">
              <td :colspan="canAddExpense ? 7 : 6" class="empty-state">
                <Icon name="inbox" :size="32" class="mb-sm text-muted" />
                <p>Aucune dépense enregistrée sur ce projet pour le moment.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Zone de danger (Admin uniquement) -->
    <div v-if="canDelete" class="fade-in-up delay-6" style="margin-top: 3rem;">
      <div class="danger-zone glass">
        <div class="danger-info">
          <div class="danger-icon"><Icon name="shield-exclamation" :size="24" /></div>
          <div>
            <h3>Zone de Danger</h3>
            <p>La suppression du projet est <strong>définitive et irréversible</strong>. Vous ne pouvez pas supprimer un projet contenant des dépenses actives.</p>
          </div>
        </div>
        <button class="btn-danger" @click="deleteProject">
          <Icon name="trash" :size="16" /> Supprimer le projet
        </button>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  project: Object,
  expenses_by_category: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// ─── Autorisations et Sécurité ──────────────────────────────────────
// Ces propriétés calculées déterminent dynamiquement si l'utilisateur
// a le droit d'effectuer certaines actions en fonction de son rôle.

/**
 * Détermine si l'utilisateur peut modifier les informations du projet.
 * - L'admin a tous les droits.
 * - Le chef de projet ne peut modifier que les projets qu'il a lui-même créés.
 */
const canEdit = computed(() => {
  if (user.value.roles?.includes('admin')) return true;
  if (user.value.roles?.includes('chef_projet')) {
    return props.project.created_by === user.value.id;
  }
  return false;
});

/**
 * Détermine si l'utilisateur peut ajouter de nouvelles dépenses au projet.
 * Les règles sont identiques à celles de l'édition du projet.
 */
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

const canViewFinances = computed(() => {
  return !user.value.roles?.includes('client');
});

// ─── Fonctions de Formatage ─────────────────────────────────────────

/**
 * Formate un nombre en devise locale (Franc CFA - XOF).
 * @param {Number|String} v Le montant à formater
 * @returns {String} Le montant formaté (ex: 15 000 F CFA) ou '—' si null
 */
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

// Statuts
const getStatusBadge = (status) => {
  switch (status) {
    case 'en_cours': return 'status-blue';
    case 'termine':  return 'status-emerald';
    case 'en_pause': return 'status-gold';
    default:         return 'status-gray';
  }
};
const getStatusLabel = (status) => {
  switch (status) {
    case 'en_cours': return 'En cours';
    case 'termine':  return 'Terminé';
    case 'en_pause': return 'En pause';
    default:         return status;
  }
};

// Actions
const deleteProject = () => {
  if (confirm('Voulez-vous vraiment supprimer ce projet de façon permanente ?')) {
    router.delete(route('projects.destroy', props.project.id));
  }
};

const confirmDeletePayment = (payment) => {
  if (confirm(`Confirmer la suppression de cet encaissement de ${formatAmount(payment.amount)} ?`)) {
    router.delete(route('payments.destroy', payment.id), { preserveScroll: true });
  }
};

const deleteExpense = (expenseId) => {
  if (confirm('Voulez-vous vraiment supprimer cette dépense ?')) {
    router.delete(route('expenses.destroy', expenseId));
  }
};

// Graphiques
const chartSeries = computed(() => props.expenses_by_category.map(c => Number(c.total)));
const chartOptions = computed(() => ({
  labels: props.expenses_by_category.map(c => c.name),
  colors: props.expenses_by_category.map(c => c.color),
  legend: { position: 'bottom', fontSize: '12px', fontFamily: "'Times New Roman', Times, serif", markers: { radius: 12 } },
  dataLabels: { enabled: false },
  stroke: { width: 0 },
  tooltip: {
    y: { formatter: v => formatAmount(v) },
    theme: 'light',
  },
  plotOptions: {
    pie: {
      donut: {
        size: '75%',
        labels: {
          show: true,
          name: { show: true, fontSize: '14px', color: '#64748b' },
          value: { show: true, fontSize: '20px', fontWeight: 800, color: '#1a2b4a', formatter: v => formatAmount(v) },
          total: { show: true, label: 'Dépenses', color: '#64748b', fontSize: '14px', formatter: () => formatAmount(props.project.total_expenses) }
        }
      }
    }
  },
}));
</script>

<style scoped>
/* =========================================
   PREMIUM DESIGN & GLASSMORPHISM
========================================= */

/* Header */
.page-header-premium {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.5rem;
}
.header-subtitle {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.client-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
  background: rgba(255,255,255,0.6);
  padding: 4px 10px;
  border-radius: 0;
  border: 1px solid rgba(0,0,0,0.05);
}
.text-gradient {
  background: linear-gradient(135deg, #1a2b4a 0%, #C9A84C 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
  margin: 0;
  font-size: 2.2rem;
  line-height: 1.2;
}
.header-actions {
  display: flex;
  gap: 10px;
}
.btn-action-outline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 0;
  font-weight: 600;
  font-size: 0.9rem;
  color: #475569;
  background: rgba(255,255,255,0.7);
  border: 1px solid rgba(0,0,0,0.1);
  text-decoration: none;
  transition: all 0.2s;
}
.btn-action-outline:hover {
  background: white;
  color: #1a2b4a;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.btn-premium {
  background: linear-gradient(135deg, #C9A84C 0%, #b4933a 100%);
  color: white;
  padding: 10px 20px;
  border-radius: 0;
  font-weight: 600;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  box-shadow: 0 4px 15px rgba(201, 168, 76, 0.3);
  transition: all 0.3s ease;
}
.btn-premium:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(201, 168, 76, 0.4);
  color: white;
}

/* Alertes */
.alert-premium {
  background: rgba(239, 68, 68, 0.1);
  border-left: 4px solid #ef4444;
  color: #b91c1c;
  padding: 1rem 1.5rem;
  border-radius: var(--radius-sm);
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.kpi-card {
  padding: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1.25rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.kpi-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
}
.kpi-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.kpi-label {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  font-weight: 700;
  margin-bottom: 6px;
}
.kpi-value {
  font-size: 1.7rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 5px;
}
.kpi-subtext {
  font-size: 0.8rem;
  font-weight: 600;
}

/* Layout Details */
.details-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}
@media (max-width: 1024px) {
  .details-grid { grid-template-columns: 1fr; }
}

/* Glass Panels */
.glass {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: var(--radius-lg);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.glass-panel {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.panel-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
}
.panel-header h2 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a2b4a;
  margin: 0;
  display: flex;
  align-items: center;
}
.badge-count {
  background: #f1f5f9;
  color: #475569;
  font-size: 0.8rem;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  margin-left: 10px;
}
.border-b { border-bottom: 1px solid rgba(0,0,0,0.06); }
.border-t { border-top: 1px solid rgba(0,0,0,0.06); }
.panel-body { padding: 1.5rem; }

/* Info Project */
.description-box p {
  color: #334155;
  line-height: 1.6;
  margin: 0;
  font-size: 0.95rem;
  white-space: pre-line;
}
.info-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}
.mt-lg { margin-top: 1.5rem; }
.pt-lg { padding-top: 1.5rem; }
.info-label {
  display: block;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #94a3b8;
  font-weight: 700;
  margin-bottom: 12px;
}
.info-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.info-list li {
  font-size: 0.9rem;
  color: #475569;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.info-list li strong { color: #1a2b4a; }

/* Analytics Budget */
.analytics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}
.analytics-item {
  padding: 1.25rem;
}
.ax-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  font-weight: 700;
  font-size: 0.9rem;
}
.ax-pct {
  background: rgba(0,0,0,0.05);
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
}
.ax-amounts { margin-bottom: 12px; }
.ax-spent { font-size: 1.25rem; font-weight: 800; color: #1a2b4a; }
.ax-budget { font-size: 0.8rem; color: #94a3b8; font-weight: 600; }
.ax-overrun { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 4px; }
.mt-sm { margin-top: 0.75rem; }
.mt-xs { margin-top: 0.5rem; }

/* Table */
.table-container { overflow-x: auto; }
.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th {
  background: rgba(248, 250, 252, 0.6);
  padding: 12px 1rem;
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 700;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  white-space: nowrap;
}
.modern-table td {
  padding: 14px 1rem;
  font-size: 0.85rem;
  border-bottom: 1px solid rgba(0,0,0,0.03);
  color: #334155;
  vertical-align: middle;
  white-space: nowrap;
}
.table-row-animate { transition: background 0.2s; }
.table-row-animate:hover { background: rgba(248, 250, 252, 0.8); }

.category-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(241, 245, 249, 0.8);
  padding: 4px 10px;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
}
.color-dot { width: 10px; height: 10px; border-radius: var(--radius-full); }
.user-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #64748b;
  font-size: 0.8rem;
}
.receipt-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #C9A84C;
  font-weight: 600;
  text-decoration: none;
  font-size: 0.8rem;
  transition: color 0.2s;
}
.receipt-link:hover { color: #a38531; }

.action-buttons { display: flex; justify-content: flex-end; gap: 8px; }
.btn-action {
  width: 32px; height: 32px;
  border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  background: rgba(241, 245, 249, 0.8);
  color: #64748b;
  border: none; cursor: pointer;
  transition: all 0.2s;
}
.btn-action.edit:hover { background: #eff6ff; color: #3b82f6; }
.btn-action.delete:hover { background: #fee2e2; color: #ef4444; }

/* Danger Zone */
.danger-zone {
  background: rgba(254, 226, 226, 0.5);
  border: 1px solid rgba(239, 68, 68, 0.2);
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}
.danger-info { display: flex; align-items: flex-start; gap: 1rem; }
.danger-icon { color: #ef4444; }
.danger-zone h3 { margin: 0 0 4px 0; color: #b91c1c; font-size: 1.1rem; }
.danger-zone p { margin: 0; color: #7f1d1d; font-size: 0.9rem; }
.btn-danger {
  background: var(--color-danger); color: white;
  border: none; padding: 10px 20px;
  border-radius: var(--radius-sm); font-weight: 600;
  display: inline-flex; align-items: center; gap: 8px;
  cursor: pointer; transition: all 0.2s;
}
.btn-danger:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(239,68,68,0.3); }

/* Progress Bars */
.progress-track { width: 100%; background: var(--color-bg-light); border-radius: var(--radius-full); height: 6px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: var(--radius-full); }
.progress-fill.animated { animation: loadBar 1s cubic-bezier(0.4, 0, 0.2, 1) forwards; width: 0 !important; }
@keyframes loadBar { to { width: var(--target-width); } }

/* Badges */
.modern-badge { padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 600; }
.status-blue { background: rgba(37,99,235,.1); color: #2563eb; }
.status-emerald { background: rgba(5,150,105,.1); color: var(--color-success); }
.status-gold { background: rgba(212,177,84,.15); color: var(--color-accent-dark); }
.status-gray { background: rgba(71,85,105,.1); color: #475569; }

/* Utils */
.text-slate { color: #334155; }
.text-emerald { color: #10b981; }
.text-gold { color: #C9A84C; }
.text-red { color: #ef4444; }
.text-blue { color: #3b82f6; }
.text-muted { color: #94a3b8; }
.bg-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.bg-emerald { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.bg-gold { background: rgba(201, 168, 76, 0.1); color: #C9A84C; }
.bg-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.bg-slate { background: rgba(100, 116, 139, 0.1); color: #64748b; }

.font-medium { font-weight: 500; }
.font-bold { font-weight: 700; }
.text-right { text-align: right; }
.text-xs { font-size: 0.75rem; }
.italic { font-style: italic; }
.mr-2 { margin-right: 0.5rem; }
.mb-xl { margin-bottom: 2rem; }
.mb-xs { margin-bottom: 0.5rem; }

.flex-1 { flex: 1; }
.flex-col { display: flex; flex-direction: column; }
.flex-center { display: flex; align-items: center; justify-content: center; }

/* Empty States */
.empty-state { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
.empty-state-mini { text-align: center; color: #94a3b8; padding: 2rem; }

/* Animations */
.slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }
.delay-5 { animation-delay: 0.5s; }
.delay-6 { animation-delay: 0.6s; }

@keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
</style>
