<template>
  <AppLayout title="Projets">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <PageHeader title="Projets" :description="`${projects.total} projet${projects.total > 1 ? 's' : ''} au total`">
        <template v-slot:actions>
          <Button v-if="can.create" as="Link" :href="route('projects.create')" variant="accent">
            <template v-slot:icon-left>
              <Icon name="plus" :size="16" />
            </template>
            Nouveau projet
          </Button>
        </template>
      </PageHeader>

      <!-- Filtres -->
      <div class="filters-bar">
        <div style="position: relative; flex: 1; min-width: 200px; max-width: 300px;">
          <input
            v-model="search"
            class="form-control"
            style="padding-left: 36px;"
            placeholder="Rechercher un projet..."
            @input="applyFilters"
          />
          <Icon name="magnifying-glass" :size="16" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-light);" />
        </div>
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
        <Button v-if="hasFilters" variant="outline" size="sm" @click="clearFilters">
          <template v-slot:icon-left>
            <Icon name="x-mark" :size="14" />
          </template>
          Effacer
        </Button>
      </div>

      <!-- Tableau / Liste -->
      <Card>
        <div v-if="projects.data.length" class="table-container">
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
                <th style="width: 120px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="project in projects.data" :key="project.id">
                <td>
                  <Link :href="route('projects.show', project.id)" class="link-primary">
                    {{ project.name }}
                  </Link>
                </td>
                <td class="text-muted">{{ project.client || '—' }}</td>
                <td><StatusBadge :status="project.status" /></td>
                <td class="amount-neutral">{{ fmt(project.budget) }}</td>
                <td style="color: var(--color-danger); font-weight: 600;">{{ fmt(project.total_expenses) }}</td>
                <td :class="project.gross_gain >= 0 ? 'amount-positive' : 'amount-negative'">
                  {{ project.gross_gain >= 0 ? '+' : '' }}{{ fmt(project.gross_gain) }}
                </td>
                <td>
                  <div style="min-width: 100px;">
                    <div style="font-size: .8rem; font-weight: 600; margin-bottom: 4px;"
                      :class="project.profitability >= 0 ? 'text-success' : 'text-danger'">
                      {{ project.profitability >= 0 ? '+' : '' }}{{ project.profitability }}%
                    </div>
                    <div class="profit-bar">
                      <div class="profit-bar-fill" :class="project.is_profitable ? 'positive' : 'negative'"
                        :style="`width: ${Math.min(Math.abs(project.profitability), 100)}%`" />
                    </div>
                  </div>
                </td>
                <td class="text-muted" style="font-size: .8rem;">{{ project.start_date }}</td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 6px; justify-content: flex-end;">
                    <Button as="Link" :href="route('projects.show', project.id)" variant="outline" size="sm">
                      Voir
                    </Button>
                    <Button v-if="can.edit" as="Link" :href="route('projects.edit', project.id)" variant="outline" size="sm" style="padding: 6px 10px;">
                      <Icon name="pencil-square" :size="14" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-else style="padding: var(--space-xl);">
          <EmptyState
            title="Aucun projet trouvé"
            description="Essayez de modifier vos filtres ou de créer un nouveau projet."
            icon="briefcase"
          />
        </div>

        <!-- Pagination -->
        <Pagination :links="projects.links" />
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import PageHeader from '@/Components/PageHeader.vue';

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
