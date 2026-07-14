<template>
  <AppLayout title="Projets">
    <!-- En-tête avec animation d'entrée -->
    <div class="page-header-premium slide-down">
      <div class="header-content">
        <h1 class="text-gradient">Gestion des Projets</h1>
        <p>Gérez et analysez vos {{ projects.total }} projets en cours et terminés.</p>
      </div>
      <div class="header-actions">
        <Link v-if="can.create" :href="route('projects.create')" class="btn-premium">
          <Icon name="plus" :size="16" />
          <span>Nouveau projet</span>
        </Link>
      </div>
    </div>

    <!-- Filtres Modernes et Glassmorphism -->
    <div class="glass-panel fade-in-up" style="margin-bottom: 2rem;">
      <div class="modern-filters">
        <!-- Recherche Globale -->
        <div class="search-box">
          <Icon name="magnifying-glass" :size="16" class="search-icon" />
          <input
            v-model="search"
            class="search-input"
            placeholder="Rechercher un projet..."
            @input="applyFilters"
          />
        </div>

        <!-- Filtres Déroulants -->
        <div class="filter-group">
          <select v-model="filters.status" @change="applyFilters">
            <option value="">Tous les statuts</option>
            <option value="en_cours">En cours</option>
            <option value="termine">Terminé</option>
            <option value="en_pause">En pause</option>
          </select>
          <Icon name="chevron-down" :size="14" class="select-icon" />
        </div>

        <div class="filter-group" v-if="clients && clients.length > 1">
          <select v-model="filters.client_id" @change="applyFilters">
            <option value="">Tous les clients</option>
            <option v-for="client in clients" :key="client.id" :value="client.id">
              {{ client.name }}
            </option>
          </select>
          <Icon name="chevron-down" :size="14" class="select-icon" />
        </div>

        <div class="filter-group">
          <select v-model="filters.year" @change="applyFilters">
            <option value="">Toutes les années</option>
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
          <Icon name="chevron-down" :size="14" class="select-icon" />
        </div>

        <button v-if="hasFilters" class="btn-reset" @click="clearFilters" title="Effacer les filtres">
          <Icon name="x-mark" :size="16" />
        </button>
      </div>
    </div>

    <!-- Tableau Premium -->
    <div class="glass-panel fade-in-up" style="animation-delay: 0.1s;">
      <div v-if="projects.data.length" class="table-container">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Projet</th>
              <th>Client</th>
              <th>Statut</th>
              <th class="text-right">Budget</th>
              <th v-if="!$page.props.auth.user.roles?.includes('client')" class="text-right">Dépenses</th>
              <th v-if="!$page.props.auth.user.roles?.includes('client')" class="text-right">Gain / Perte</th>
              <th v-if="!$page.props.auth.user.roles?.includes('client')">Rentabilité</th>
              <th>Début</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="project in projects.data" :key="project.id" class="table-row-animate">
              <td>
                <Link :href="route('projects.show', project.id)" class="project-title">
                  {{ project.name }}
                </Link>
              </td>
              <td class="text-muted">{{ project.client || '—' }}</td>
              <td>
                <span class="modern-badge" :class="getStatusBadge(project.status)">
                  {{ getStatusLabel(project.status) }}
                </span>
              </td>
              <td class="text-right font-medium text-slate">{{ fmt(project.budget) }}</td>
              <td v-if="!$page.props.auth.user.roles?.includes('client')" class="text-right font-medium text-red">{{ fmt(project.total_expenses) }}</td>
              <td v-if="!$page.props.auth.user.roles?.includes('client')" class="text-right font-bold" :class="project.gross_gain >= 0 ? 'text-emerald' : 'text-red'">
                {{ project.gross_gain >= 0 ? '+' : '' }}{{ fmt(project.gross_gain) }}
              </td>
              <td v-if="!$page.props.auth.user.roles?.includes('client')">
                <div class="profitability-cell">
                  <div class="profit-bar-bg">
                    <div class="profit-bar-fill" :class="project.profitability >= 0 ? 'bg-emerald' : 'bg-red'"
                         :style="`width: ${Math.min(Math.abs(project.profitability), 100)}%`"></div>
                  </div>
                  <span class="profit-value" :class="project.profitability >= 0 ? 'text-emerald' : 'text-red'">
                    {{ project.profitability >= 0 ? '+' : '' }}{{ project.profitability }}%
                  </span>
                </div>
              </td>
              <td class="text-muted text-sm">{{ project.start_date }}</td>
              <td>
                <div class="action-buttons">
                  <Link :href="route('projects.show', project.id)" class="btn-action view" title="Voir les détails">
                    <Icon name="eye" :size="16" />
                  </Link>
                  <Link v-if="can.edit" :href="route('projects.edit', project.id)" class="btn-action edit" title="Modifier le projet">
                    <Icon name="pencil-square" :size="16" />
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination Premium -->
        <div class="modern-pagination" v-if="projects.links && projects.links.length > 3">
          <Link v-for="(link, i) in projects.links" :key="i"
                :href="link.url || '#'"
                class="page-link"
                :class="{ 'active': link.active, 'disabled': !link.url }"
                v-html="link.label"
                preserve-scroll
          />
        </div>
      </div>

      <!-- État Vide -->
      <div v-else class="empty-state">
        <div class="empty-icon-wrapper">
          <Icon name="briefcase" :size="40" class="text-gold" />
        </div>
        <h3>Aucun projet trouvé</h3>
        <p>Modifiez vos filtres ou créez un nouveau projet pour commencer.</p>
        <Link v-if="can.create" :href="route('projects.create')" class="btn-premium mt-md">
          <Icon name="plus" :size="16" /> Créer un projet
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  projects: Object,
  clients:  Array,
  filters:  Object,
  years:    Array,
  can:      Object,
});

const search  = ref(props.filters.search || '');
const filters = ref({
  status:    props.filters.status    || '',
  client_id: props.filters.client_id || '',
  year:      props.filters.year      || '',
});

const hasFilters = computed(() =>
  search.value || filters.value.status || filters.value.client_id || filters.value.year
);

const fmt = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v ?? 0);

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

let debounce;
const applyFilters = () => {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    router.get(route('projects.index'), { search: search.value, ...filters.value }, {
      preserveState: true, replace: true,
    });
  }, 350);
};

const clearFilters = () => {
  search.value = '';
  filters.value = { status: '', client_id: '', year: '' };
  router.get(route('projects.index'));
};
</script>

<style scoped>
/* =========================================
   PREMIUM DESIGN & GLASSMORPHISM
========================================= */

/* Header */
.page-header-premium {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.text-gradient {
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
  margin-bottom: 5px;
  font-size: 1.75rem;
  font-family: var(--font-heading);
}
.header-content p {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin: 0;
}
.btn-premium {
  background: var(--gradient-gold);
  color: var(--color-primary-dark);
  padding: 9px 20px;
  border-radius: var(--radius-sm);
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  box-shadow: var(--shadow-glow-sm);
  transition: var(--transition);
  font-family: var(--font-body);
  font-size: 0.875rem;
}
.btn-premium:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-glow);
  color: var(--color-primary-dark);
}

/* Glass Panels */
.glass-panel {
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  margin-bottom: var(--space-lg);
}

/* Modern Filters */
.modern-filters {
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
  border-bottom: 1px solid rgba(0,0,0,0.03);
}
.search-box {
  position: relative;
  flex: 1;
  min-width: 250px;
}
.search-input {
  width: 100%;
  padding: 9px 10px 9px 38px;
  border-radius: var(--radius-sm);
  border: 1.5px solid rgba(226,232,240,0.9);
  font-size: 0.875rem;
  font-family: var(--font-body);
  color: var(--color-text);
  background: #fff;
  outline: none;
  transition: var(--transition);
  box-shadow: var(--shadow-xs);
}
.search-input:focus {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px rgba(212,177,84,.12);
  background: #fff;
}
.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.filter-group {
  position: relative;
  min-width: 160px;
}
.filter-group select {
  width: 100%;
  appearance: none;
  background: #fff;
  border: 1.5px solid rgba(226,232,240,0.9);
  border-radius: var(--radius-sm);
  padding: 9px 30px 9px 12px;
  font-size: 0.875rem;
  color: var(--color-text);
  font-weight: 500;
  outline: none;
  transition: var(--transition);
  cursor: pointer;
  box-shadow: var(--shadow-xs);
}
.filter-group select:focus, .filter-group select:hover {
  background: #fff;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px rgba(212,177,84,.1);
}
.select-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}
.btn-reset {
  background: rgba(220, 38, 38, 0.08);
  color: var(--color-danger);
  border: 1.5px solid rgba(220,38,38,.2);
  border-radius: var(--radius-sm);
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
  flex-shrink: 0;
}
.btn-reset:hover {
  background: var(--color-danger);
  color: white;
  border-color: var(--color-danger);
}

/* Modern Table */
.table-container {
  overflow-x: auto;
}
.modern-table {
  width: 100%;
  border-collapse: collapse;
}
.modern-table th {
  background: rgba(248, 250, 252, 0.6);
  padding: 14px 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  font-weight: 700;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}
.modern-table td {
  padding: 16px 1.5rem;
  font-size: 0.85rem;
  border-bottom: 1px solid rgba(0,0,0,0.03);
  color: #334155;
  vertical-align: middle;
}
.table-row-animate {
  transition: background 0.2s;
}
.table-row-animate:hover {
  background: rgba(248, 250, 252, 0.8);
}
.project-title {
  font-weight: 700;
  color: #1a2b4a;
  text-decoration: none;
  font-size: 0.95rem;
  transition: color 0.2s;
}
.project-title:hover {
  color: #C9A84C;
}

/* Utils */
.text-slate { color: #334155; }
.text-emerald { color: #10b981; }
.text-gold { color: #C9A84C; }
.text-red { color: #ef4444; }
.text-muted { color: #94a3b8; }
.text-xs { font-size: 0.75rem; }
.font-medium { font-weight: 500; }
.font-bold { font-weight: 700; }
.bg-emerald { background-color: #10b981; }
.bg-red { background-color: #ef4444; }

.modern-badge {
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  letter-spacing: 0.2px;
}
.status-blue    { background: rgba(37,99,235,.1);  color: #2563eb; }
.status-emerald { background: rgba(5,150,105,.1);   color: var(--color-success); }
.status-gold    { background: rgba(212,177,84,.15);  color: var(--color-accent-dark); }
.status-gray    { background: rgba(71,85,105,.1);   color: #475569; }

/* Progress Bars */
.progress-track {
  width: 100%;
  background: var(--color-bg-light);
  border-radius: var(--radius-full);
  height: 6px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: var(--radius-full);
}
.progress-fill.animated {
  animation: loadBar 1s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  width: 0 !important;
}
@keyframes loadBar {
  to { width: var(--target-width); }
}

/* Action Buttons */
.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 6px;
}
.btn-action {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-light);
  color: var(--color-text-muted);
  transition: var(--transition);
  border: 1px solid var(--color-border-light);
  text-decoration: none;
}
.btn-action.view:hover { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
.btn-action.edit:hover { background: rgba(212,177,84,.1); color: var(--color-accent-dark); border-color: rgba(212,177,84,.3); }

/* Empty State */
.empty-state {
  padding: 4rem 2rem;
  text-align: center;
}
.empty-icon-wrapper {
  background: rgba(201, 168, 76, 0.1);
  width: 80px;
  height: 80px;
  border-radius: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}
.empty-state h3 {
  font-size: 1.25rem;
  color: #1a2b4a;
  margin: 0 0 0.5rem 0;
}
.empty-state p {
  color: #64748b;
  margin: 0;
}
.mt-md { margin-top: 1.5rem; }

/* Pagination Premium */
.modern-pagination {
  display: flex;
  justify-content: center;
  gap: 5px;
  padding: 1.5rem;
  flex-wrap: wrap;
}
.page-link {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  border-radius: 0;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  background: transparent;
  text-decoration: none;
  transition: all 0.2s;
}
.page-link:hover:not(.disabled) {
  background: rgba(0,0,0,0.05);
}
.page-link.active {
  background: #1a2b4a;
  color: white;
  box-shadow: 0 4px 6px rgba(26, 43, 74, 0.2);
}
.page-link.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Animations */
.slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
