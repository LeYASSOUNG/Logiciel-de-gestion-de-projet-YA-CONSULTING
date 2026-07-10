<template>
  <AppLayout title="Historique et Statistiques">
    <!-- Header avec animation d'entrée -->
    <div class="stats-page-header slide-down">
      <div class="header-content">
        <h1 class="text-gradient">Historique & Statistiques</h1>
        <p>Analysez la performance globale, la rentabilité de vos projets et tracez toutes les actions du système.</p>
      </div>
    </div>

    <!-- Section 1: KPI (Statistiques) avec Glassmorphism -->
    <div class="kpi-grid fade-in-up">
      <!-- Total Projets -->
      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-blue">
          <Icon name="folder-open" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Projets Gérés</div>
          <div class="kpi-value">{{ stats.total_projects }}</div>
          <div class="kpi-subtext text-blue">
            <Icon name="check-circle" :size="12" /> {{ stats.completed_projects }} terminés
          </div>
        </div>
      </div>

      <!-- Rentabilité Moyenne -->
      <div class="kpi-card glass">
        <div class="kpi-icon-wrapper bg-emerald">
          <Icon name="chart-bar" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Rentabilité Moyenne</div>
          <div class="kpi-value">{{ stats.average_profitability }}<span class="percent">%</span></div>
          <div class="kpi-subtext text-emerald">Sur les projets filtrés</div>
        </div>
      </div>

      <!-- Plus Rentable -->
      <div class="kpi-card glass" v-if="stats.most_profitable">
        <div class="kpi-icon-wrapper bg-gold">
          <Icon name="arrow-trending-up" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Le plus rentable</div>
          <div class="kpi-value truncate" :title="stats.most_profitable.name">{{ stats.most_profitable.name }}</div>
          <div class="kpi-subtext text-gold">Gain : {{ formatCurrency(stats.most_profitable.gain) }}</div>
        </div>
      </div>

      <!-- Moins Rentable -->
      <div class="kpi-card glass" v-if="stats.least_profitable">
        <div class="kpi-icon-wrapper bg-red">
          <Icon name="arrow-trending-down" :size="24" />
        </div>
        <div class="kpi-info">
          <div class="kpi-label">Le moins rentable</div>
          <div class="kpi-value truncate" :title="stats.least_profitable.name">{{ stats.least_profitable.name }}</div>
          <div class="kpi-subtext" :class="stats.least_profitable.gain >= 0 ? 'text-emerald' : 'text-red'">
            Résultat : {{ formatCurrency(stats.least_profitable.gain) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Liste des Projets & Filtres -->
    <div class="glass-panel slide-up">
      <div class="panel-header">
        <h2>Annuaire des Projets</h2>

        <!-- Filtres Modernes -->
        <div class="modern-filters">
          <div class="filter-group">
            <select v-model="filterForm.client_id" @change="applyFilters">
              <option value="">Tous les clients</option>
              <option v-for="client in filters.clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
            <Icon name="chevron-down" :size="14" class="select-icon" />
          </div>

          <div class="filter-group">
            <select v-model="filterForm.status" @change="applyFilters">
              <option value="">Tous les statuts</option>
              <option value="en_cours">En cours</option>
              <option value="termine">Terminé</option>
              <option value="en_pause">En pause</option>
            </select>
            <Icon name="chevron-down" :size="14" class="select-icon" />
          </div>

          <div class="filter-group">
            <select v-model="filterForm.year" @change="applyFilters">
              <option value="">Toutes les années</option>
              <option v-for="year in filters.years" :key="year" :value="year">{{ year }}</option>
            </select>
            <Icon name="chevron-down" :size="14" class="select-icon" />
          </div>

          <button class="btn-reset" @click="resetFilters" title="Réinitialiser">
            <Icon name="arrow-path" :size="16" />
          </button>
        </div>
      </div>

      <div class="table-container">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Projet</th>
              <th>Client</th>
              <th>Création</th>
              <th>Statut</th>
              <th class="text-right">Budget</th>
              <th class="text-right">Dépenses</th>
              <th class="text-right">Gain Brut</th>
              <th>Rentabilité</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="project in projects" :key="project.id" class="table-row-animate">
              <td>
                <Link :href="route('projects.show', project.id)" class="project-link">{{ project.name }}</Link>
              </td>
              <td class="text-muted">{{ project.client }}</td>
              <td class="text-muted">{{ project.created_at }}</td>
              <td>
                <span class="modern-badge" :class="getStatusBadge(project.status)">
                  {{ getStatusLabel(project.status) }}
                </span>
              </td>
              <td class="text-right font-medium">{{ formatCurrency(project.budget) }}</td>
              <td class="text-right text-red">{{ formatCurrency(project.total_expenses) }}</td>
              <td class="text-right font-bold" :class="project.gross_gain >= 0 ? 'text-emerald' : 'text-red'">
                {{ formatCurrency(project.gross_gain) }}
              </td>
              <td style="width: 140px;">
                <div class="flex items-center gap-xs">
                  <span class="font-medium text-xs">{{ project.profitability >= 0 ? '+' : '' }}{{ project.profitability }}%</span>
                  <div class="progress-track">
                    <div class="progress-fill"
                         :class="project.profitability >= 0 ? 'bg-emerald' : 'bg-red'"
                         :style="{ width: Math.min(100, Math.abs(project.profitability)) + '%' }">
                    </div>
                  </div>
                </div>
              </td>
            </tr>
            <tr v-if="projects.length === 0">
              <td colspan="8" class="empty-state">
                <Icon name="inbox" :size="32" class="mb-sm text-muted" />
                <p>Aucun projet ne correspond à ces critères.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Section 3: Graphiques & Journal -->
    <div class="bottom-grid fade-in-up">
      <!-- Dépenses par catégorie -->
      <div class="glass-panel">
        <div class="panel-header border-b">
          <h2>Répartition des Dépenses</h2>
        </div>
        <div class="panel-body">
           <div v-if="stats.expenses_by_category.length > 0" class="category-bars">
             <div v-for="(cat, index) in stats.expenses_by_category" :key="cat.name" class="category-item" :style="{ animationDelay: (index * 0.1) + 's' }">
               <div class="category-info">
                 <span class="category-name">
                   <span class="color-dot" :style="{background: cat.color}"></span>
                   {{ cat.name }}
                 </span>
                 <span class="category-amount font-bold">{{ formatCurrency(cat.total) }}</span>
               </div>
               <div class="progress-track lg">
                  <div class="progress-fill animated" :style="{ background: cat.color, width: (cat.total / (stats.expenses_by_category.reduce((a,b)=>a+b.total, 0) || 1) * 100) + '%' }"></div>
               </div>
             </div>
           </div>
           <div v-else class="empty-state">
             <Icon name="chart-pie" :size="32" class="mb-sm text-muted" />
             <p>Aucune dépense enregistrée sur cette sélection.</p>
           </div>
        </div>
      </div>

      <!-- Journal d'activité -->
      <div class="glass-panel">
        <div class="panel-header border-b">
          <h2>Journal d'Activité</h2>
        </div>
        <div class="panel-body no-padding">
          <ul class="activity-timeline">
            <li v-for="(log, index) in activities.data" :key="log.id" class="timeline-item" :style="{ animationDelay: (index * 0.05) + 's' }">
              <div class="timeline-marker"></div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span class="timeline-user">
                    <Icon name="user" :size="12" /> {{ log.causer_name }}
                  </span>
                  <span class="timeline-date">{{ log.created_at }}</span>
                </div>
                <div class="timeline-desc">{{ log.description }}</div>

                <!-- Détail des modifications -->
                <div class="timeline-diff" v-if="log.properties && log.properties.old && log.properties.attributes">
                  <div class="diff-title">Modifications :</div>
                  <ul>
                    <li v-for="(val, key) in log.properties.attributes" :key="key" v-show="log.properties.old[key] != val">
                      <span class="diff-key">{{ key }}</span>:
                      <del>{{ log.properties.old[key] }}</del> <Icon name="arrow-right" :size="10" /> <strong>{{ val }}</strong>
                    </li>
                  </ul>
                </div>
              </div>
            </li>
            <li v-if="activities.data.length === 0" class="empty-state">
              <Icon name="clock" :size="32" class="mb-sm text-muted" />
              <p>Aucune activité enregistrée.</p>
            </li>
          </ul>

          <!-- Pagination stylisée -->
          <div class="modern-pagination" v-if="activities.links && activities.links.length > 3">
            <Link v-for="(link, i) in activities.links" :key="i"
                  :href="link.url || '#'"
                  class="page-link"
                  :class="{ 'active': link.active, 'disabled': !link.url }"
                  v-html="link.label"
                  preserve-scroll
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  projects: Array,
  stats: Object,
  activities: Object,
  filters: Object,
});

const filterForm = ref({
  client_id: props.filters.current.client_id || '',
  status: props.filters.current.status || '',
  year: props.filters.current.year || '',
});

const applyFilters = () => {
  router.get(route('history-stats.index'), filterForm.value, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
  filterForm.value = { client_id: '', status: '', year: '' };
  applyFilters();
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(amount);
};

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
</script>

<style scoped>
/* =========================================
   PREMIUM DESIGN & GLASSMORPHISM
========================================= */

/* Typography & Colors */
.text-gradient {
  background: linear-gradient(135deg, #1a2b4a 0%, #C9A84C 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
  margin-bottom: 5px;
}
.text-blue { color: #3b82f6; }
.text-emerald { color: #10b981; }
.text-gold { color: #C9A84C; }
.text-red { color: #ef4444; }
.text-muted { color: #64748b; }
.bg-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.bg-emerald { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.bg-gold { background: rgba(201, 168, 76, 0.1); color: #C9A84C; }
.bg-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

/* Header */
.stats-page-header {
  margin-bottom: 2rem;
}
.header-content p {
  color: #64748b;
  font-size: 0.95rem;
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}
.kpi-card {
  padding: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.kpi-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
}
.kpi-icon-wrapper {
  width: 50px;
  height: 50px;
  border-radius: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.kpi-label {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 4px;
}
.kpi-value {
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin-bottom: 5px;
}
.kpi-value .percent {
  font-size: 1.2rem;
  color: #94a3b8;
}
.kpi-subtext {
  font-size: 0.75rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}
.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

/* Glass Panels */
.glass {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.glass-panel {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(15px);
  border-radius: 0;
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
  margin-bottom: 2.5rem;
  overflow: hidden;
}
.panel-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}
.panel-header.border-b {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.panel-header h2 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a2b4a;
  margin: 0;
}
.panel-body {
  padding: 1.5rem;
}
.panel-body.no-padding {
  padding: 0;
}

/* Modern Filters */
.modern-filters {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}
.filter-group {
  position: relative;
}
.filter-group select {
  appearance: none;
  background: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 0;
  padding: 8px 30px 8px 12px;
  font-size: 0.85rem;
  color: #334155;
  font-weight: 500;
  outline: none;
  transition: all 0.2s;
  cursor: pointer;
}
.filter-group select:focus, .filter-group select:hover {
  background: #fff;
  border-color: #C9A84C;
  box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.1);
}
.select-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}
.btn-reset {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: none;
  border-radius: 0;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-reset:hover {
  background: #ef4444;
  color: white;
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
  padding: 12px 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  font-weight: 700;
  border-top: 1px solid rgba(0,0,0,0.05);
  border-bottom: 1px solid rgba(0,0,0,0.05);
}
.modern-table td {
  padding: 14px 1.5rem;
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
.project-link {
  font-weight: 600;
  color: #1a2b4a;
  text-decoration: none;
  transition: color 0.2s;
}
.project-link:hover {
  color: #C9A84C;
}
.modern-badge {
  padding: 4px 10px;
  border-radius: 0;
  font-size: 0.75rem;
  font-weight: 600;
}
.status-blue { background: #eff6ff; color: #2563eb; }
.status-emerald { background: #ecfdf5; color: #059669; }
.status-gold { background: #fefce8; color: #ca8a04; }
.status-gray { background: #f1f5f9; color: #475569; }

/* Progress Bars */
.progress-track {
  width: 100%;
  background: #e2e8f0;
  border-radius: 0;
  height: 6px;
  overflow: hidden;
}
.progress-track.lg { height: 8px; }
.progress-fill {
  height: 100%;
  border-radius: 0;
  transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
}
.progress-fill.animated {
  animation: loadBar 1s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  width: 0 !important;
}
@keyframes loadBar {
  to { width: var(--target-width); }
}

/* Bottom Grid */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}
@media (max-width: 1024px) {
  .bottom-grid { grid-template-columns: 1fr; }
}

/* Category Bars */
.category-bars {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.category-item {
  opacity: 0;
  animation: fadeInRight 0.5s forwards;
}
.category-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 6px;
  font-size: 0.85rem;
}
.color-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 0;
  margin-right: 6px;
}

/* Timeline */
.activity-timeline {
  list-style: none;
  padding: 1.5rem;
  margin: 0;
}
.timeline-item {
  position: relative;
  padding-left: 2rem;
  padding-bottom: 1.5rem;
  opacity: 0;
  animation: fadeInLeft 0.5s forwards;
}
.timeline-item:last-child {
  padding-bottom: 0;
}
.timeline-marker {
  position: absolute;
  left: 0;
  top: 4px;
  width: 12px;
  height: 12px;
  border-radius: 0;
  background: #C9A84C;
  border: 3px solid #fff;
  box-shadow: 0 0 0 2px rgba(201, 168, 76, 0.2);
  z-index: 1;
}
.timeline-item:not(:last-child)::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 16px;
  bottom: 0;
  width: 2px;
  background: rgba(0, 0, 0, 0.05);
}
.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}
.timeline-user {
  font-size: 0.75rem;
  font-weight: 600;
  color: #1a2b4a;
  background: rgba(26, 43, 74, 0.05);
  padding: 2px 8px;
  border-radius: 0;
  display: flex;
  align-items: center;
  gap: 4px;
}
.timeline-date {
  font-size: 0.7rem;
  color: #94a3b8;
}
.timeline-desc {
  font-size: 0.85rem;
  color: #334155;
  font-weight: 500;
}
.timeline-diff {
  margin-top: 8px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 0;
  padding: 10px;
}
.diff-title {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 700;
  margin-bottom: 4px;
}
.timeline-diff ul {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.75rem;
}
.timeline-diff li {
  margin-bottom: 2px;
}
.diff-key {
  text-transform: capitalize;
  color: #475569;
}

/* Pagination */
.modern-pagination {
  display: flex;
  justify-content: center;
  gap: 5px;
  padding: 1rem;
  border-top: 1px solid rgba(0,0,0,0.05);
  flex-wrap: wrap;
}
.page-link {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  height: 32px;
  border-radius: 0;
  font-size: 0.8rem;
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

/* Empty State */
.empty-state {
  padding: 3rem 1rem !important;
  text-align: center;
  color: #94a3b8;
  font-size: 0.9rem;
}

/* Animations */
.slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1); }

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInLeft {
  from { opacity: 0; transform: translateX(-20px); }
  to { opacity: 1; transform: translateX(0); }
}
@keyframes fadeInRight {
  from { opacity: 0; transform: translateX(20px); }
  to { opacity: 1; transform: translateX(0); }
}
</style>
