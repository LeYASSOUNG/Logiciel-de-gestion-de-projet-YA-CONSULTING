<template>
  <AppLayout title="Catégories de Dépenses">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Catégories de Dépenses</h1>
          <p>{{ categories.length }} catégorie(s) configurée(s)</p>
        </div>
        <button @click="openCreateModal" class="btn btn-accent">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nouvelle catégorie
        </button>
      </div>

      <!-- Erreurs éventuelles -->
      <div v-if="$page.props.errors.delete" class="alert alert-danger mb-lg">
        {{ $page.props.errors.delete }}
      </div>

      <!-- Tableau -->
      <div class="card">
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 60px;">Couleur</th>
                <th>Nom</th>
                <th>Type de budget (Axe)</th>
                <th>Origine</th>
                <th class="text-right">Nombre de dépenses</th>
                <th style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td>
                  <span :style="`display:inline-block; width:24px; height:24px; border-radius:50%; background-color:${cat.color}; border: 1px solid var(--color-border);`" />
                </td>
                <td><strong>{{ cat.name }}</strong></td>
                <td>
                  <span class="badge" style="background-color: var(--color-bg-light); color: var(--color-primary); border: 1px solid var(--color-border);">
                    {{ cat.parent_label }}
                  </span>
                </td>
                <td>
                  <span v-if="cat.is_custom" class="badge" style="background-color: rgba(201,168,76,.1); color: var(--color-accent-dark);">Personnalisée</span>
                  <span v-else class="badge" style="background-color: #f1f5f9; color: #64748b;">Système</span>
                </td>
                <td class="text-right">{{ cat.expenses_count }}</td>
                <td style="text-align:right">
                  <div v-if="cat.is_custom" style="display:flex; gap:6px; justify-content:flex-end;">
                    <button @click="openEditModal(cat)" class="btn btn-outline btn-sm" style="padding:4px 8px;" title="Modifier">
                      ✏️
                    </button>
                    <button @click="deleteCategory(cat)" class="btn btn-outline btn-sm text-danger" :disabled="cat.expenses_count > 0" :title="cat.expenses_count > 0 ? 'Impossible de supprimer (dépenses existantes)' : 'Supprimer'" style="padding:4px 8px; border-color:var(--color-border)">
                      ✕
                    </button>
                  </div>
                  <span v-else style="font-size: .8rem; color: var(--color-text-muted); font-style: italic;">Verrouillée</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Formulaire (Créer / Modifier) -->
    <div v-if="showModal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,28,51,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(3px);">
      <div class="card animate-fade-up" style="width:100%; max-width:480px; margin:20px;">
        <div class="card-header">
          <h2 class="card-title">{{ modalTitle }}</h2>
          <button @click="closeModal" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--color-text-muted)">✕</button>
        </div>
        <div class="card-body">
          <form @submit.prevent="submit">
            <div class="form-group">
              <label class="form-label">Nom de la catégorie <span class="required">*</span></label>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" placeholder="Ex: Achat fournitures bureau" required />
              <div v-if="errors.name" class="form-error">{{ errors.name }}</div>
            </div>

            <div class="form-group">
              <label class="form-label">Type de budget alloué <span class="required">*</span></label>
              <select v-model="form.parent_type" class="form-control" :class="{ error: errors.parent_type }" required>
                <option value="">Sélectionner un type...</option>
                <option v-for="(label, value) in parent_types" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
              <div v-if="errors.parent_type" class="form-error">{{ errors.parent_type }}</div>
            </div>

            <div class="form-group">
              <label class="form-label">Couleur d'identification <span class="required">*</span></label>
              <div style="display:flex; gap:12px; align-items:center;">
                <input v-model="form.color" type="color" style="width: 48px; height: 38px; padding: 2px; border: 1px solid var(--color-border); border-radius: 6px; cursor: pointer;" />
                <input v-model="form.color" type="text" class="form-control" placeholder="#ffffff" style="max-width: 120px;" required />
              </div>
              <div v-if="errors.color" class="form-error">{{ errors.color }}</div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <button type="button" @click="closeModal" class="btn btn-outline">Annuler</button>
              <button type="submit" class="btn btn-accent" :disabled="form.processing">
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else>✓ Enregistrer</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  categories: Array,
  parent_types: Object,
});

const showModal = ref(false);
const editingCategoryId = ref(null);

const modalTitle = computed(() => editingCategoryId.value ? 'Modifier la catégorie' : 'Créer une catégorie');

const form = useForm({
  name:        '',
  parent_type: '',
  color:       '#6366f1',
});

const errors = form.errors;

const openCreateModal = () => {
  editingCategoryId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (cat) => {
  editingCategoryId.value = cat.id;
  form.name = cat.name;
  form.parent_type = cat.parent_type;
  form.color = cat.color;
  form.clearErrors();
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingCategoryId.value = null;
  form.reset();
};

const submit = () => {
  if (editingCategoryId.value) {
    form.put(route('categories.update', editingCategoryId.value), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('categories.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteCategory = (cat) => {
  if (confirm(`Voulez-vous vraiment supprimer la catégorie "${cat.name}" ?`)) {
    router.delete(route('categories.destroy', cat.id));
  }
};
</script>
