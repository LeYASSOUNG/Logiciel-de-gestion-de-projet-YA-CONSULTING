<template>
  <AppLayout title="Modifier le décaissement">
    <div class="animate-fade-up" style="max-width: 680px; margin: 0 auto;">
      <!-- En-tête -->
      <PageHeader title="Modifier la dépense" description="Mettez à jour les détails de la transaction financière">
        <template v-slot:actions>
          <Button as="Link" :href="route('projects.show', expense.project_id)" variant="outline">
            Retour
          </Button>
        </template>
      </PageHeader>

      <Card>
        <form @submit.prevent="submit">
          <!-- Projet -->
          <FormField label="Projet rattaché" :error="errors.project_id" required>
            <select v-model="form.project_id" class="form-control" :class="{ error: errors.project_id }" required>
              <option value="">Sélectionner un projet...</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.name }} (Budget: {{ fmt(project.budget) }})
              </option>
            </select>
          </FormField>

          <div class="form-grid">
            <!-- Catégorie -->
            <FormField label="Catégorie de coût" :error="errors.category_id" required>
              <select v-model="form.category_id" class="form-control" :class="{ error: errors.category_id }" required>
                <option value="">Sélectionner une catégorie...</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </FormField>

            <!-- Date -->
            <FormField label="Date du paiement" :error="errors.date" required>
              <input v-model="form.date" type="date" class="form-control" :class="{ error: errors.date }" required />
            </FormField>
          </div>

          <div class="form-grid">
            <!-- Montant -->
            <FormField label="Montant (FCFA)" :error="errors.amount" required>
              <input v-model="form.amount" type="number" class="form-control" :class="{ error: errors.amount }"
                placeholder="Ex: 250000" min="1" step="5" required />
              <div v-if="form.amount" style="font-size:.8rem; color:var(--color-text-muted); margin-top:4px;">
                = {{ fmt(form.amount) }}
              </div>
            </FormField>

            <!-- Fichier justificatif -->
            <FormField label="Justificatif (remplacer le fichier existant)" :error="errors.receipt">
              <input type="file" @input="form.receipt = $event.target.files[0]" class="form-control" :class="{ error: errors.receipt }" accept="image/*,application/pdf" />
              <div v-if="expense.receipt_path" style="margin-top: 6px; font-size: .8rem; display: flex; align-items: center; gap: 4px;">
                <span class="text-muted">Fichier actuel :</span>
                <a :href="`/storage/${expense.receipt_path}`" target="_blank" style="color:var(--color-accent-dark); font-weight:600; text-decoration:none; display: inline-flex; align-items: center; gap: 4px;">
                  <Icon name="document-arrow-down" :size="14" />
                  {{ expense.receipt_original_name || 'Voir le fichier' }}
                </a>
              </div>
            </FormField>
          </div>

          <!-- Description -->
          <FormField label="Description / Libellé de la dépense">
            <textarea v-model="form.description" class="form-control" rows="3" />
          </FormField>

          <!-- Actions -->
          <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
            <Button as="Link" :href="route('projects.show', expense.project_id)" variant="outline">
              Annuler
            </Button>
            <Button type="submit" variant="primary" :disabled="form.processing">
              <span v-if="form.processing">Enregistrement...</span>
              <span v-else style="display: flex; align-items: center; gap: 8px;">
                <Icon name="check" :size="16" />
                Mettre à jour
              </span>
            </Button>
          </div>
        </form>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import Icon from '@/Components/Icon.vue';

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
