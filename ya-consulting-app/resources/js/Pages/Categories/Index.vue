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

      <!-- Grille de catégories -->
      <div class="categories-grid">
        <div
          v-for="cat in categories"
          :key="cat.id"
          class="category-card"
          :style="{ '--cat-color': cat.color }"
        >
          <!-- Barre de couleur -->
          <div class="cat-color-bar" />

          <div class="cat-content">
            <!-- Icône colorée -->
            <div class="cat-icon" :style="{ backgroundColor: cat.color + '22', color: cat.color }">
              <Icon name="tag" :size="20" />
            </div>

            <div class="cat-info">
              <div class="cat-name">{{ cat.name }}</div>
              <div class="cat-meta">
                <span class="cat-type-badge">{{ cat.parent_label }}</span>
                <span v-if="cat.is_custom" class="cat-custom-badge">Personnalisée</span>
                <span v-else class="cat-system-badge">Système</span>
              </div>
            </div>
          </div>

          <div class="cat-footer">
            <div class="cat-count">
              <Icon name="receipt-percent" :size="13" />
              {{ cat.expenses_count }} dépense{{ cat.expenses_count > 1 ? 's' : '' }}
            </div>
            <div v-if="cat.is_custom" class="action-group">
              <button class="action-btn" title="Modifier" @click="openEditModal(cat)">
                <Icon name="pencil-square" :size="14" />
              </button>
              <button
                class="action-btn danger"
                :class="{ locked: cat.expenses_count > 0 }"
                :disabled="cat.expenses_count > 0"
                :title="cat.expenses_count > 0 ? 'Impossible (dépenses existantes)' : 'Supprimer'"
                @click="deleteCategory(cat)"
              >
                <Icon name="trash" :size="14" />
              </button>
            </div>
            <div v-else class="cat-locked">
              <Icon name="lock-closed" :size="12" />
              Système
            </div>
          </div>
        </div>
      </div>

      <!-- État vide -->
      <Card v-if="categories.length === 0">
        <EmptyState
          title="Aucune catégorie trouvée"
          description="Créez votre première catégorie de dépense."
          icon="tag"
        >
          <template v-slot:action>
            <Button variant="accent" size="sm" @click="openCreateModal">
              <template v-slot:icon-left><Icon name="plus" :size="14" /></template>
              Créer une catégorie
            </Button>
          </template>
        </EmptyState>
      </Card>
    </div>

    <!-- Modal Formulaire -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-card animate-scale-in">
            <div class="modal-header">
              <div class="modal-header-icon">
                <Icon name="tag" :size="20" />
              </div>
              <h2 class="modal-title">{{ modalTitle }}</h2>
              <button class="modal-close" @click="closeModal" title="Fermer">
                <Icon name="x-mark" :size="18" />
              </button>
            </div>

            <div class="modal-body">
              <form @submit.prevent="submit">
                <FormField label="Nom de la catégorie" :error="errors.name" required>
                  <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }"
                    placeholder="Ex: Achat fournitures bureau" required />
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
                  <div class="color-picker-row">
                    <input v-model="form.color" type="color" class="color-input" />
                    <div class="color-preview" :style="{ backgroundColor: form.color }">
                      <span>Aperçu</span>
                    </div>
                    <input v-model="form.color" type="text" class="form-control" placeholder="#6366f1"
                      style="max-width: 120px;" required />
                  </div>
                </FormField>

                <div class="modal-actions">
                  <Button type="button" variant="outline" @click="closeModal">Annuler</Button>
                  <Button type="submit" variant="accent" :disabled="form.processing">
                    <template v-if="form.processing">Enregistrement...</template>
                    <template v-else>
                      <Icon name="check" :size="16" /> Enregistrer
                    </template>
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
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
import EmptyState from '@/Components/EmptyState.vue';
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
  form.color = '#6366f1';
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (cat) => {
  editingCategoryId.value = cat.id;
  form.name        = cat.name;
  form.parent_type = cat.parent_type;
  form.color       = cat.color;
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
    form.put(route('categories.update', editingCategoryId.value), { onSuccess: () => closeModal() });
  } else {
    form.post(route('categories.store'), { onSuccess: () => closeModal() });
  }
};

const deleteCategory = (cat) => {
  if (confirm(`Voulez-vous vraiment supprimer la catégorie "${cat.name}" ?`)) {
    router.delete(route('categories.destroy', cat.id));
  }
};
</script>

<style scoped>
/* Grille catégories */
.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--space-lg);
}

.category-card {
  background: #fff;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border-light);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: var(--transition);
  display: flex;
  flex-direction: column;
}

.category-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
  border-color: var(--cat-color, rgba(212,177,84,.3));
}

.cat-color-bar {
  height: 4px;
  background: var(--cat-color, var(--color-accent));
}

.cat-content {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 18px 18px 14px;
  flex: 1;
}

.cat-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.cat-name {
  font-weight: 700;
  color: var(--color-primary);
  font-size: 0.95rem;
  margin-bottom: 6px;
  font-family: var(--font-heading);
}

.cat-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.cat-type-badge,
.cat-custom-badge,
.cat-system-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: var(--radius-full);
  font-family: var(--font-body);
}

.cat-type-badge {
  background: var(--color-bg-light);
  color: var(--color-text-muted);
  border: 1px solid var(--color-border-light);
}

.cat-custom-badge {
  background: rgba(212,177,84,.12);
  color: var(--color-accent-dark);
}

.cat-system-badge {
  background: var(--color-bg-light);
  color: var(--color-text-light);
}

.cat-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 18px;
  border-top: 1px solid var(--color-border-light);
  background: rgba(248,250,252,0.5);
}

.cat-count {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.78rem;
  color: var(--color-text-muted);
  font-weight: 500;
}

.cat-locked {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  color: var(--color-text-light);
  font-style: italic;
}

/* Boutons d'action */
.action-group {
  display: flex;
  align-items: center;
  gap: 6px;
}

.action-btn {
  width: 30px;
  height: 30px;
  border-radius: var(--radius-sm);
  background: var(--color-bg-light);
  border: 1px solid var(--color-border-light);
  color: var(--color-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}
.action-btn:hover {
  background: #eff6ff;
  color: #3b82f6;
  border-color: #bfdbfe;
}
.action-btn.danger:not(.locked):hover {
  background: #fee2e2;
  color: var(--color-danger);
  border-color: #fecaca;
}
.action-btn.locked {
  opacity: 0.35;
  cursor: not-allowed;
}

/* Color picker */
.color-picker-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.color-input {
  width: 48px;
  height: 42px;
  padding: 2px;
  border: 1.5px solid var(--color-border-light);
  border-radius: var(--radius-sm);
  cursor: pointer;
  background: none;
}

.color-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 38px;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(255,255,255,0.9);
  text-shadow: 0 1px 2px rgba(0,0,0,0.3);
  border: 1.5px solid rgba(0,0,0,0.08);
  flex-shrink: 0;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 28, 51, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 24px;
}

.modal-card {
  background: #fff;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  width: 100%;
  max-width: 480px;
  overflow: hidden;
  border: 1px solid var(--color-border-light);
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border-light);
  background: linear-gradient(to bottom, rgba(248,250,252,0.9), white);
}

.modal-header-icon {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  background: rgba(212,177,84,.12);
  color: var(--color-accent-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.modal-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-primary);
  flex: 1;
  margin: 0;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border-light);
  background: none;
  cursor: pointer;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}
.modal-close:hover {
  background: var(--color-bg-light);
  color: var(--color-text);
}

.modal-body { padding: 24px; }

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-md);
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: 1px solid var(--color-border-light);
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
