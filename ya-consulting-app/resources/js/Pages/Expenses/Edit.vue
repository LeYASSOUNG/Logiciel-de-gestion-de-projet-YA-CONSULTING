<template>
  <AppLayout title="Modifier le décaissement">
    <div class="animate-fade-up" style="max-width: 680px; margin: 0 auto;">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Modifier la dépense</h1>
          <p>Mettez à jour les détails de la transaction financière</p>
        </div>
        <Link :href="route('projects.show', expense.project_id)" class="btn btn-outline">
          ← Retour
        </Link>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit">
            <!-- Projet -->
            <div class="form-group">
              <label class="form-label">Projet rattaché <span class="required">*</span></label>
              <select v-model="form.project_id" class="form-control" :class="{ error: errors.project_id }" required>
                <option value="">Sélectionner un projet...</option>
                <option v-for="project in projects" :key="project.id" :value="project.id">
                  {{ project.name }} (Budget: {{ fmt(project.budget) }})
                </option>
              </select>
              <div v-if="errors.project_id" class="form-error">{{ errors.project_id }}</div>
            </div>

            <div class="form-grid">
              <!-- Catégorie -->
              <div class="form-group">
                <label class="form-label">Catégorie de coût <span class="required">*</span></label>
                <select v-model="form.category_id" class="form-control" :class="{ error: errors.category_id }" required>
                  <option value="">Sélectionner une catégorie...</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
                <div v-if="errors.category_id" class="form-error">{{ errors.category_id }}</div>
              </div>

              <!-- Date -->
              <div class="form-group">
                <label class="form-label">Date du paiement <span class="required">*</span></label>
                <input v-model="form.date" type="date" class="form-control" :class="{ error: errors.date }" required />
                <div v-if="errors.date" class="form-error">{{ errors.date }}</div>
              </div>
            </div>

            <div class="form-grid">
              <!-- Montant -->
              <div class="form-group">
                <label class="form-label">Montant (FCFA) <span class="required">*</span></label>
                <input v-model="form.amount" type="number" class="form-control" :class="{ error: errors.amount }"
                  placeholder="Ex: 250000" min="1" step="5" required />
                <div v-if="errors.amount" class="form-error">{{ errors.amount }}</div>
                <div v-if="form.amount" style="font-size:.8rem; color:var(--color-text-muted); margin-top:4px;">
                  = {{ fmt(form.amount) }}
                </div>
              </div>

              <!-- Fichier justificatif -->
              <div class="form-group">
                <label class="form-label">Justificatif (remplacer le fichier existant)</label>
                <input type="file" @input="form.receipt = $event.target.files[0]" class="form-control" :class="{ error: errors.receipt }" accept="image/*,application/pdf" />
                <div v-if="errors.receipt" class="form-error">{{ errors.receipt }}</div>
                <div v-if="expense.receipt_path" style="margin-top: 6px; font-size: .8rem;">
                  Fichier actuel :
                  <a :href="`/storage/${expense.receipt_path}`" target="_blank" style="color:var(--color-accent-dark); font-weight:600; text-decoration:none;">
                    {{ expense.receipt_original_name || 'Voir le fichier' }}
                  </a>
                </div>
              </div>
            </div>

            <!-- Description -->
            <div class="form-group">
              <label class="form-label">Description / Libellé de la dépense</label>
              <textarea v-model="form.description" class="form-control" rows="3" />
            </div>

            <!-- Actions -->
            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <Link :href="route('projects.show', expense.project_id)" class="btn btn-outline">
                Annuler
              </Link>
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else>✓ Mettre à jour</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  expense:    Object,
  projects:   Array,
  categories: Array,
});

// Format input date YYYY-MM-DD
const formatInputDate = (dateStr) => {
  if (!dateStr) return '';
  return dateStr.split('T')[0];
};

const form = useForm({
  project_id:  props.expense.project_id,
  category_id: props.expense.category_id,
  date:        formatInputDate(props.expense.date),
  amount:      Math.round(props.expense.amount),
  description: props.expense.description || '',
  receipt:     null,
});

const errors = form.errors;

const fmt = (v) => v ? new Intl.NumberFormat('fr-FR', {
  style: 'currency', currency: 'XOF', maximumFractionDigits: 0
}).format(v) : '';

const submit = () => {
  // Method spoofing pour Inertia car Laravel ne parse pas multipart/form-data via PUT/PATCH
  form.transform((data) => ({
    ...data,
    _method: 'PUT',
  })).post(route('expenses.update', props.expense.id), {
    forceFormData: true,
  });
};
</script>
