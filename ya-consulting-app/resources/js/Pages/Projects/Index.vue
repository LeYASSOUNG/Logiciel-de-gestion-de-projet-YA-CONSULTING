<template>
  <AppLayout title="Projets">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Projets</h1>
          <p>{{ projects.total }} projet{{ projects.total > 1 ? 's' : '' }} au total</p>
        </div>
        <Link v-if="can.create" :href="route('projects.create')" class="btn btn-accent">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nouveau projet
        </Link>
      </div>

      <!-- Filtres -->
      <div class="filters-bar">
        <input
          v-model="search"
          class="form-control"
          placeholder="🔍 Rechercher un projet..."
          @input="applyFilters"
        />
        <select v-model="filters.status" class="form-control" @change="applyFilters">
          <option value="">Tous les statuts</option>
          <option value="en_cours">En cours</option>
          <option value="termine">Terminé</option>
          <option value="en_pause">En pause</option>
        </select>
        <select v-model="filters.client_id" class="form-control" @change="applyFilters">
          <option value="">Tous les clients</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
        <select v-model="filters.year" class="form-control" @change="applyFilters">
          <option value="">Toutes les années</option>
          <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </select>
        <button v-if="hasFilters" class="btn btn-outline btn-sm" @click="clearFilters">
          ✕ Effacer
        </button>
      </div>

      <!-- Tableau -->
      <div class="card">
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Projet</th>
                <th>Client</th>
                <th>Statut</th>
                <th>Budget</th>
                <th>Dépenses</th>
                <th>Gain / Perte</th>
                <th>Rentabilité</th>
                <th>Début</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="project in projects.data" :key="project.id">
                <td>
                  <Link :href="route('projects.show', project.id)"
                    style="font-weight:600; color:var(--color-primary); text-decoration:none;">
                    {{ project.name }}
                  </Link>
                </td>
                <td style="color:var(--color-text-muted);">{{ project.client || '—' }}</td>
                <td><StatusBadge :status="project.status" /></td>
                <td class="amount-neutral">{{ fmt(project.budget) }}</td>
                <td style="color:var(--color-danger);">{{ fmt(project.total_expenses) }}</td>
                <td :class="project.gross_gain >= 0 ? 'amount-positive' : 'amount-negative'">
                  {{ project.gross_gain >= 0 ? '+' : '' }}{{ fmt(project.gross_gain) }}
                </td>
                <td>
                  <div style="min-width:100px;">
                    <div style="font-size:.8rem; font-weight:600; margin-bottom:4px;"
                      :class="project.profitability >= 0 ? 'text-success' : 'text-danger'">
                      {{ project.profitability >= 0 ? '+' : '' }}{{ project.profitability }}%
                    </div>
                    <div class="profit-bar">
                      <div class="profit-bar-fill" :class="project.is_profitable ? 'positive' : 'negative'"
                        :style="`width: ${Math.min(Math.abs(project.profitability), 100)}%`" />
                    </div>
                  </div>
                </td>
                <td style="color:var(--color-text-muted); font-size:.8rem;">{{ project.start_date }}</td>
                <td>
                  <div style="display:flex; gap:6px;">
                    <Link :href="route('projects.show', project.id)" class="btn btn-outline btn-sm">Voir</Link>
                    <Link v-if="can.edit" :href="route('projects.edit', project.id)" class="btn btn-outline btn-sm">✏️</Link>
                  </div>
                </td>
              </tr>
              <tr v-if="!projects.data.length">
                <td colspan="9" style="text-align:center; padding:40px; color:var(--color-text-muted);">
                  Aucun projet trouvé
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="projects.last_page > 1"
          style="display:flex; justify-content:center; gap:6px; padding:16px; border-top:1px solid var(--color-border);">
          <Link v-for="link in projects.links" :key="link.label"
            :href="link.url || '#'"
            class="btn btn-outline btn-sm"
            :style="link.active ? 'background:var(--color-primary); color:#fff; border-color:var(--color-primary)' : ''"
            v-html="link.label" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

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
