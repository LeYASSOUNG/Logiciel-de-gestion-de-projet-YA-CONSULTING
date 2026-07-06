<template>
  <AppLayout title="Catégories de Dépenses">
    <div class="animate-fade-up">
      <!-- En-tête -->
      <PageHeader title="Catégories de Dépenses" :description="`${categories.length} catégorie(s) configurée(s)`">
        <template v-slot:actions>
          <Button variant="accent" @click="openCreateModal">
            <template v-slot:icon-left>
              <Icon name="plus" :size="16" />
            </template>
            Nouvelle catégorie
          </Button>
        </template>
      </PageHeader>

      <!-- Erreurs éventuelles -->
      <div v-if="$page.props.errors.delete" class="alert alert-danger mb-lg">
        {{ $page.props.errors.delete }}
      </div>

      <!-- Tableau -->
      <Card>
        <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 80px;">Couleur</th>
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
                  <span :style="`display:inline-block; width:24px; height:24px; border-radius: 0; background-color:${cat.color}; border: 1px solid var(--color-border);`" />
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
                    <Button variant="outline" size="sm" style="padding: 6px 10px;" title="Modifier" @click="openEditModal(cat)">
                      <Icon name="pencil-square" :size="14" />
                    </Button>
                    <Button variant="outline" size="sm" class="text-danger" :disabled="cat.expenses_count > 0" :title="cat.expenses_count > 0 ? 'Impossible de supprimer (dépenses existantes)' : 'Supprimer'" style="padding: 6px 10px;" @click="deleteCategory(cat)">
                      <Icon name="trash" :size="14" />
                    </Button>
                  </div>
                  <span v-else style="font-size: .8rem; color: var(--color-text-muted); font-style: italic;">Verrouillée</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
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
            <FormField label="Nom de la catégorie" :error="errors.name" required>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" placeholder="Ex: Achat fournitures bureau" required />
            </FormField>

            <FormField label="Type de budget alloué" :error="errors.parent_type" required>
              <select v-model="form.parent_type" class="form-control" :class="{ error: errors.parent_type }" required>
                <option value="">Sélectionner un type...</option>
                <option v-for="(label, value) in parent_types" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </FormField>

            <FormField label="Couleur d'identification" :error="errors.color" required>
              <div style="display:flex; gap:12px; align-items:center;">
                <input v-model="form.color" type="color" style="width: 48px; height: 38px; padding: 2px; border: 1px solid var(--color-border); border-radius: 0; cursor: pointer;" />
                <input v-model="form.color" type="text" class="form-control" placeholder="#ffffff" style="max-width: 120px;" required />
              </div>
            </FormField>

            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <Button type="button" variant="outline" @click="closeModal">Annuler</Button>
              <Button type="submit" variant="accent" :disabled="form.processing">
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else style="display: flex; align-items: center; gap: 8px;">
                  <Icon name="check" :size="16" />
                  Enregistrer
                </span>
              </Button>
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
import PageHeader from '@/Components/PageHeader.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import Icon from '@/Components/Icon.vue';

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
