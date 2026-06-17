<template>
  <AppLayout title="Nouveau décaissement">
    <div class="animate-fade-up" style="max-width: 680px; margin: 0 auto;">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Enregistrer une dépense</h1>
          <p>Saisissez les détails de la transaction financière</p>
        </div>
        <Link :href="selected_project ? route('projects.show', selected_project.id) : route('expenses.index')" class="btn btn-outline">
          ← Retour
        </Link>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit">
            <!-- Projet -->
            <div class="form-group">
              <label class="form-label">Projet rattaché <span class="required">*</span></label>
              <select v-model="form.project_id" class="form-control" :class="{ error: errors.project_id }" :disabled="!!selected_project" required>
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
                <label class="form-label">Justificatif (PDF, Image - max 5Mo)</label>
                <input type="file" @input="form.receipt = $event.target.files[0]" class="form-control" :class="{ error: errors.receipt }" accept="image/*,application/pdf" />
                <div v-if="errors.receipt" class="form-error">{{ errors.receipt }}</div>
              </div>
            </div>

            <!-- Description -->
            <div class="form-group">
              <label class="form-label">Description / Libellé de la dépense</label>
              <textarea v-model="form.description" class="form-control" rows="3"
                placeholder="Détails complémentaires de la transaction (ex: Facture #45, Achat de ciment...)" />
            </div>

            <!-- Actions -->
            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <Link :href="selected_project ? route('projects.show', selected_project.id) : route('expenses.index')" class="btn btn-outline">
                Annuler
              </Link>
              <button type="submit" class="btn btn-accent" :disabled="form.processing">
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else>✓ Enregistrer la dépense</span>
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
  projects:         Array,
  categories:       Array,
  selected_project: Object,
});

const form = useForm({
  project_id:  props.selected_project?.id || '',
  category_id: '',
  date:        new Date().toISOString().split('T')[0],
  amount:      '',
  description: '',
  receipt:     null,
});

const errors = form.errors;

const fmt = (v) => v ? new Intl.NumberFormat('fr-FR', {
  style: 'currency', currency: 'XOF', maximumFractionDigits: 0
}).format(v) : '';

const submit = () => {
  form.post(route('expenses.store'), {
    forceFormData: true,
  });
};
</script>
