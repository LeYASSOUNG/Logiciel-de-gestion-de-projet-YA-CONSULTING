<template>
  <AppLayout title="Dépenses">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Dépenses</h1>
          <p>{{ expenses.total }} dépense{{ expenses.total > 1 ? 's' : '' }} enregistrée{{ expenses.total > 1 ? 's' : '' }}</p>
        </div>
        <Link v-if="canCreate" :href="route('expenses.create')" class="btn btn-accent">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nouveau décaissement
        </Link>
      </div>

      <!-- Filtres -->
      <div class="filters-bar">
        <input
          v-model="search"
          class="form-control"
          placeholder="🔍 Rechercher..."
          @input="applyFilters"
        />

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
                <th>Date</th>
                <th>Projet</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Justificatif</th>
                <th>Auteur</th>
                <th class="text-right">Montant</th>
                <th v-if="canEdit" style="width:100px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="expense in expenses.data" :key="expense.id">
                <td style="color:var(--color-text-muted);">{{ formatDate(expense.date) }}</td>
                <td>
                  <Link v-if="expense.project" :href="route('projects.show', expense.project.id)"
                    style="font-weight:600; color:var(--color-primary); text-decoration:none;">
                    {{ expense.project.name }}
                  </Link>
                  <span v-else style="color:var(--color-text-muted)">—</span>
                </td>
                <td>
                  <span style="display:inline-flex; align-items:center; gap:6px;">
                    <span :style="`width:10px; height:10px; border-radius:50%; background-color:${expense.category?.color || '#ccc'}`"></span>
                    {{ expense.category?.name || 'N/A' }}
                  </span>
                </td>
                <td>{{ expense.description || '—' }}</td>
                <td>
                  <a v-if="expense.receipt_path" :href="`/storage/${expense.receipt_path}`" target="_blank"
                    style="color:var(--color-accent-dark); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Fichier
                  </a>
                  <span v-else style="color:var(--color-text-light);">Aucun</span>
                </td>
                <td style="color:var(--color-text-muted);">{{ expense.creator?.name || '—' }}</td>
                <td class="amount-neutral text-right">{{ fmt(expense.amount) }}</td>
                <td v-if="canEdit" style="text-align:right">
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    <Link :href="route('expenses.edit', expense.id)" class="btn btn-outline btn-sm" style="padding:4px 8px;">
                      ✏️
                    </Link>
                    <button @click="deleteExpense(expense.id)" class="btn btn-outline btn-sm text-danger" style="padding:4px 8px; border-color:var(--color-border)">
                      ✕
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!expenses.data.length">
                <td colspan="8" style="text-align:center; padding:40px; color:var(--color-text-muted);">
                  Aucune dépense trouvée
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="expenses.last_page > 1"
          style="display:flex; justify-content:center; gap:6px; padding:16px; border-top:1px solid var(--color-border);">
          <Link v-for="link in expenses.links" :key="link.label"
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
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
