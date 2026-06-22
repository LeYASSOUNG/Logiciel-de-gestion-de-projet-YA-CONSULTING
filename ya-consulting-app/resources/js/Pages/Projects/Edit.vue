<template>
  <AppLayout title="Modifier le projet">
    <div class="animate-fade-up" style="max-width: 720px; margin: 0 auto;">
      <!-- En-tête -->
      <PageHeader title="Modifier le projet" description="Mettez à jour les informations du projet">
        <template v-slot:actions>
          <Button as="Link" :href="route('projects.show', project.id)" variant="outline">
            <template v-slot:icon-left>
              <Icon name="arrow-left" :size="16" />
            </template>
            Annuler
          </Button>
        </template>
      </PageHeader>

      <Card>
        <form @submit.prevent="submit">
          <!-- Warning banner for locked fields -->
          <div v-if="project.has_expenses" class="alert alert-warning mb-lg">
            <Icon name="exclamation-triangle" :size="20" style="color: var(--color-warning);" />
            <span>Ce projet contient des dépenses. Le client, la date de début et les budgets sont verrouillés.</span>
          </div>

          <!-- Informations générales -->
          <div style="margin-bottom:var(--space-xl);">
            <div class="form-section">
              <div class="form-section-icon"><Icon name="information-circle" :size="18" /></div>
              <span class="form-section-title">Informations générales</span>
            </div>

            <FormField label="Nom du projet" :error="errors.name" required>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" required />
            </FormField>

            <div class="form-grid">
              <FormField label="Client" :error="errors.client_id" required>
                <select v-model="form.client_id" class="form-control" :class="{ error: errors.client_id }" :disabled="project.has_expenses" required>
                  <option value="">Sélectionner un client...</option>
                  <option v-for="client in clients" :key="client.id" :value="client.id">
                    {{ client.name }}
                  </option>
                </select>
              </FormField>

              <FormField label="Statut" required>
                <select v-model="form.status" class="form-control">
                  <option value="en_cours">En cours</option>
                  <option value="en_pause">En pause</option>
                  <option value="termine">Terminé</option>
                </select>
              </FormField>
            </div>

            <FormField label="Description">
              <textarea v-model="form.description" class="form-control" rows="3" />
            </FormField>
          </div>

          <!-- Dates & Budget -->
          <div style="margin-bottom:var(--space-xl);">
            <div class="form-section">
              <div class="form-section-icon"><Icon name="calendar-days" :size="18" /></div>
              <span class="form-section-title">Dates &amp; Budget</span>
            </div>

            <div class="form-grid">
              <FormField label="Date de début" :error="errors.start_date" required>
                <input v-model="form.start_date" type="date" class="form-control" :class="{ error: errors.start_date }" :disabled="project.has_expenses" required />
              </FormField>

              <FormField label="Date de fin prévisionnelle" :error="errors.planned_end_date" required>
                <input v-model="form.planned_end_date" type="date" class="form-control" :class="{ error: errors.planned_end_date }" required />
              </FormField>
            </div>

            <div class="form-grid" style="margin-top:var(--space-md);">
              <FormField label="Budget Main d'œuvre (FCFA)" :error="errors.budget_labor" required>
                <input v-model="form.budget_labor" type="number" class="form-control" :class="{ error: errors.budget_labor }" :disabled="project.has_expenses" required />
              </FormField>

              <FormField label="Budget Matériel (FCFA)" :error="errors.budget_material" required>
                <input v-model="form.budget_material" type="number" class="form-control" :class="{ error: errors.budget_material }" :disabled="project.has_expenses" required />
              </FormField>
            </div>

            <div class="form-grid" style="margin-top:var(--space-md);">
              <FormField label="Budget Transport (FCFA)" :error="errors.budget_transport" required>
                <input v-model="form.budget_transport" type="number" class="form-control" :class="{ error: errors.budget_transport }" :disabled="project.has_expenses" required />
              </FormField>

              <FormField label="Autres coûts (FCFA)" :error="errors.budget_other" required>
                <input v-model="form.budget_other" type="number" class="form-control" :class="{ error: errors.budget_other }" :disabled="project.has_expenses" required />
              </FormField>
            </div>

            <div class="form-grid" style="margin-top:var(--space-md);">
              <div class="budget-summary">
                <div>
                  <div class="budget-summary-label">Budget total calculé</div>
                  <div v-if="form.budget" style="font-size:.78rem; color:var(--color-text-muted); margin-top:2px;">= {{ fmt(form.budget) }}</div>
                </div>
                <div class="budget-summary-value">{{ form.budget ? fmt(form.budget) : '0 FCFA' }}</div>
              </div>

              <FormField label="Date de fin réelle" :error="errors.actual_end_date">
                <input v-model="form.actual_end_date" type="date" class="form-control" :class="{ error: errors.actual_end_date }" :required="form.status === 'termine'" />
              </FormField>
            </div>
          </div>

          <!-- Contact fournisseur -->
          <div>
            <div class="form-section">
              <div class="form-section-icon"><Icon name="user" :size="18" /></div>
              <span class="form-section-title">Informations complémentaires</span>
            </div>

            <FormField label="Contact fournisseur">
              <input v-model="form.supplier_contact" type="text" class="form-control" />
            </FormField>
          </div>

          <!-- Actions -->
          <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
            <Button as="Link" :href="route('projects.show', project.id)" variant="outline">Annuler</Button>
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
import { watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
  project: Object,
  clients: Array,
});

// Extraire la partie 'YYYY-MM-DD' de la date
const formatInputDate = (dateStr) => {
  if (!dateStr) return '';
  return dateStr.split('T')[0];
};

const form = useForm({
  name:             props.project.name,
  client_id:        props.project.client_id,
  description:      props.project.description || '',
  start_date:       formatInputDate(props.project.start_date),
  planned_end_date: formatInputDate(props.project.planned_end_date),
  actual_end_date:  formatInputDate(props.project.actual_end_date),
  budget:           props.project.budget,
  budget_labor:     props.project.budget_labor,
  budget_material:  props.project.budget_material,
  budget_transport: props.project.budget_transport,
  budget_other:     props.project.budget_other,
  status:           props.project.status,
  supplier_contact: props.project.supplier_contact || '',
});

const errors = form.errors;

watch(
  () => [form.budget_labor, form.budget_material, form.budget_transport, form.budget_other],
  () => {
    form.budget = (Number(form.budget_labor) || 0) +
                  (Number(form.budget_material) || 0) +
                  (Number(form.budget_transport) || 0) +
                  (Number(form.budget_other) || 0);
  }
);

const fmt = (v) => v ? new Intl.NumberFormat('fr-FR', {
  style: 'currency', currency: 'XOF', maximumFractionDigits: 0
}).format(v) : '';

const submit = () => form.put(route('projects.update', props.project.id));
</script>
