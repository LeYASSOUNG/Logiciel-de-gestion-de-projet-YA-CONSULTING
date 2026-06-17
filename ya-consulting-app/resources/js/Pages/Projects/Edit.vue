<template>
  <AppLayout title="Modifier le projet">
    <div class="animate-fade-up" style="max-width: 720px; margin: 0 auto;">
      <div class="page-header">
        <div class="page-header-info">
          <h1>Modifier le projet</h1>
          <p>Mettez à jour les informations du projet</p>
        </div>
        <Link :href="route('projects.show', project.id)" class="btn btn-outline">← Annuler</Link>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit">
            <!-- Warning banner for locked fields -->
            <div v-if="project.has_expenses" class="alert alert-warning" style="margin-bottom:var(--space-lg); display:flex; align-items:center; gap:var(--space-sm);">
              <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
              <span>Ce projet contient des dépenses. Le client, la date de début et les budgets sont verrouillés.</span>
            </div>

            <!-- Informations générales -->
            <div style="margin-bottom:var(--space-xl);">
              <h3 style="font-size:.9rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.8px; margin:0 0 var(--space-lg);">
                Informations générales
              </h3>

              <div class="form-group">
                <label class="form-label">Nom du projet <span class="required">*</span></label>
                <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" required />
                <div v-if="errors.name" class="form-error">{{ errors.name }}</div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Client <span class="required">*</span></label>
                  <select v-model="form.client_id" class="form-control" :class="{ error: errors.client_id }" :disabled="project.has_expenses" required>
                    <option value="">Sélectionner un client...</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">
                      {{ client.name }}
                    </option>
                  </select>
                  <div v-if="errors.client_id" class="form-error">{{ errors.client_id }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label">Statut <span class="required">*</span></label>
                  <select v-model="form.status" class="form-control">
                    <option value="en_cours">En cours</option>
                    <option value="en_pause">En pause</option>
                    <option value="termine">Terminé</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea v-model="form.description" class="form-control" rows="3" />
              </div>
            </div>

            <!-- Dates & Budget -->
            <div style="margin-bottom:var(--space-xl);">
              <h3 style="font-size:.9rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.8px; margin:0 0 var(--space-lg);">
                Dates & Budget
              </h3>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Date de début <span class="required">*</span></label>
                  <input v-model="form.start_date" type="date" class="form-control" :class="{ error: errors.start_date }" :disabled="project.has_expenses" required />
                  <div v-if="errors.start_date" class="form-error">{{ errors.start_date }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label">Date de fin prévisionnelle <span class="required">*</span></label>
                  <input v-model="form.planned_end_date" type="date" class="form-control" :class="{ error: errors.planned_end_date }" required />
                  <div v-if="errors.planned_end_date" class="form-error">{{ errors.planned_end_date }}</div>
                </div>
              </div>

              <div class="form-grid" style="margin-top:var(--space-md);">
                <div class="form-group">
                  <label class="form-label">Budget Main d'œuvre (FCFA) <span class="required">*</span></label>
                  <input v-model="form.budget_labor" type="number" class="form-control" :class="{ error: errors.budget_labor }" :disabled="project.has_expenses" required />
                  <div v-if="errors.budget_labor" class="form-error">{{ errors.budget_labor }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label">Budget Matériel (FCFA) <span class="required">*</span></label>
                  <input v-model="form.budget_material" type="number" class="form-control" :class="{ error: errors.budget_material }" :disabled="project.has_expenses" required />
                  <div v-if="errors.budget_material" class="form-error">{{ errors.budget_material }}</div>
                </div>
              </div>

              <div class="form-grid" style="margin-top:var(--space-md);">
                <div class="form-group">
                  <label class="form-label">Budget Transport (FCFA) <span class="required">*</span></label>
                  <input v-model="form.budget_transport" type="number" class="form-control" :class="{ error: errors.budget_transport }" :disabled="project.has_expenses" required />
                  <div v-if="errors.budget_transport" class="form-error">{{ errors.budget_transport }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label">Autres coûts (FCFA) <span class="required">*</span></label>
                  <input v-model="form.budget_other" type="number" class="form-control" :class="{ error: errors.budget_other }" :disabled="project.has_expenses" required />
                  <div v-if="errors.budget_other" class="form-error">{{ errors.budget_other }}</div>
                </div>
              </div>

              <div class="form-grid" style="margin-top:var(--space-md);">
                <div class="form-group">
                  <label class="form-label">Budget total calculé (FCFA)</label>
                  <input :value="form.budget" type="number" class="form-control" style="background-color: var(--color-bg-light); cursor: not-allowed;" readonly />
                  <div v-if="errors.budget" class="form-error">{{ errors.budget }}</div>
                  <div v-if="form.budget" style="font-size:.8rem; color:var(--color-text-muted); margin-top:4px;">
                    = {{ fmt(form.budget) }}
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Date de fin réelle</label>
                  <input v-model="form.actual_end_date" type="date" class="form-control" :class="{ error: errors.actual_end_date }" :required="form.status === 'termine'" />
                  <div v-if="errors.actual_end_date" class="form-error">{{ errors.actual_end_date }}</div>
                </div>
              </div>
            </div>

            <!-- Contact fournisseur -->
            <div>
              <h3 style="font-size:.9rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.8px; margin:0 0 var(--space-lg);">
                Informations complémentaires
              </h3>

              <div class="form-group">
                <label class="form-label">Contact fournisseur</label>
                <input v-model="form.supplier_contact" type="text" class="form-control" />
              </div>
            </div>

            <!-- Actions -->
            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <Link :href="route('projects.show', project.id)" class="btn btn-outline">Annuler</Link>
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
import { watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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
