<template>
  <AppLayout title="Dépenses">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <PageHeader title="Dépenses" :description="`${expenses.total} dépense${expenses.total > 1 ? 's' : ''} enregistrée${expenses.total > 1 ? 's' : ''}`">
        <template v-slot:actions>
          <Button v-if="canCreate" as="Link" :href="route('expenses.create')" variant="accent">
            <template v-slot:icon-left>
              <Icon name="plus" :size="16" />
            </template>
            Nouveau décaissement
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
            placeholder="Rechercher..."
            @input="applyFilters"
          />
          <Icon name="magnifying-glass" :size="16" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-light);" />
        </div>

        <select v-model="filters.project_id" class="form-control" @change="applyFilters">
          <option value="">Tous les projets</option>
          <option v-for="project in projects" :key="project.id" :value="project.id">
            {{ project.name }}
          </option>
        </select>

        <select v-model="filters.category_id" class="form-control" @change="applyFilters">
          <option value="">Toutes les catégories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>

        <div style="display:flex; align-items:center; gap:8px;">
          <input
            v-model="filters.date_from"
            type="date"
            class="form-control"
            style="max-width:140px"
            @change="applyFilters"
          />
          <span style="font-size:.8rem; color:var(--color-text-muted)">au</span>
          <input
            v-model="filters.date_to"
            type="date"
            class="form-control"
            style="max-width:140px"
            @change="applyFilters"
          />
        </div>

        <Button v-if="hasFilters" variant="outline" size="sm" @click="clearFilters">
          <template v-slot:icon-left>
            <Icon name="x-mark" :size="14" />
          </template>
          Effacer
        </Button>
      </div>

      <!-- Tableau -->
      <Card>
        <div v-if="expenses.data.length" class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Projet</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Justificatif</th>
                <th>Auteur</th>
                <th class="text-right">Montant</th>
                <th v-if="canEdit" style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="expense in expenses.data" :key="expense.id">
                <td class="text-muted">{{ formatDate(expense.date) }}</td>
                <td>
                  <Link v-if="expense.project" :href="route('projects.show', expense.project.id)" class="link-primary">
                    {{ expense.project.name }}
                  </Link>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span style="display:inline-flex; align-items:center; gap:6px;">
                    <span :style="`width:10px; height:10px; border-radius: 0; background-color:${expense.category?.color || '#ccc'}`"></span>
                    {{ expense.category?.name || 'N/A' }}
                  </span>
                </td>
                <td>{{ expense.description || '—' }}</td>
                <td>
                  <a v-if="expense.receipt_path" :href="`/storage/${expense.receipt_path}`" target="_blank"
                    style="color:var(--color-accent-dark); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <Icon name="document-arrow-down" :size="14" />
                    Fichier
                  </a>
                  <span v-else class="text-muted">Aucun</span>
                </td>
                <td class="text-muted">{{ expense.creator?.name || '—' }}</td>
                <td class="amount-neutral text-right">{{ fmt(expense.amount) }}</td>
                <td v-if="canEdit" style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <Button as="Link" :href="route('expenses.edit', expense.id)" variant="outline" size="sm" style="padding: 6px 10px;">
                      <Icon name="pencil-square" :size="14" />
                    </Button>
                    <Button variant="outline" size="sm" class="text-danger" style="padding: 6px 10px;" @click="deleteExpense(expense.id)">
                      <Icon name="trash" :size="14" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else style="padding: var(--space-xl);">
          <EmptyState
            title="Aucune dépense trouvée"
            description="Essayez de modifier vos filtres ou de créer un nouveau décaissement."
            icon="credit-card"
          />
        </div>

        <!-- Pagination -->
        <Pagination :links="expenses.links" />
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  expenses:   Object,
  projects:   Array,
  categories: Array,
  filters:    Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Autorisations
const canCreate = computed(() => {
  return user.value.roles?.includes('admin') || user.value.roles?.includes('chef_projet');
});

const canEdit = computed(() => {
  return user.value.roles?.includes('admin') || user.value.roles?.includes('chef_projet');
});

const search = ref(props.filters.search || '');
const filters = ref({
  project_id:  props.filters.project_id  || '',
  category_id: props.filters.category_id || '',
  date_from:   props.filters.date_from   || '',
  date_to:     props.filters.date_to     || '',
});

const hasFilters = computed(() =>
  search.value || filters.value.project_id || filters.value.category_id || filters.value.date_from || filters.value.date_to
);

const fmt = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v ?? 0);

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR');
};

let debounce;
const applyFilters = () => {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    router.get(route('expenses.index'), { search: search.value, ...filters.value }, {
      preserveState: true, replace: true,
    });
  }, 350);
};

const clearFilters = () => {
  search.value = '';
  filters.value = { project_id: '', category_id: '', date_from: '', date_to: '' };
  router.get(route('expenses.index'));
};

const deleteExpense = (expenseId) => {
  if (confirm('Voulez-vous vraiment supprimer cette dépense ?')) {
    router.delete(route('expenses.destroy', expenseId));
  }
};
</script>
