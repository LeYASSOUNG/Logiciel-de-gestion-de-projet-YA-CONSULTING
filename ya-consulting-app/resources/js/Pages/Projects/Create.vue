<template>
  <AppLayout title="Nouveau projet">
    <div class="animate-fade-up" style="max-width: 720px; margin: 0 auto;">
      <PageHeader title="Créer un projet" description="Remplissez les informations pour créer un nouveau projet">
        <template v-slot:actions>
          <Button as="Link" :href="route('projects.index')" variant="outline">
            <template v-slot:icon-left>
              <Icon name="arrow-left" :size="16" />
            </template>
            Retour
          </Button>
        </template>
      </PageHeader>

      <Card>
        <form @submit.prevent="submit">
          <!-- Informations générales -->
          <div style="margin-bottom:var(--space-xl);">
            <div class="form-section">
              <div class="form-section-icon"><Icon name="information-circle" :size="18" /></div>
              <span class="form-section-title">Informations générales</span>
            </div>

            <FormField label="Nom du projet" :error="errors.name" required>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }"
                placeholder="Ex: Construction Immeuble A - Dakar" required />
            </FormField>

            <div class="form-grid">
              <FormField label="Client" :error="errors.client_id" required>
                <select v-model="form.client_id" class="form-control" :class="{ error: errors.client_id }" required>
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
              <textarea v-model="form.description" class="form-control" rows="3"
                placeholder="Décrivez le projet, ses objectifs, le contexte..." />
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
                <input v-model="form.start_date" type="date" class="form-control" :class="{ error: errors.start_date }" required />
              </FormField>

              <FormField label="Date de fin prévisionnelle" :error="errors.planned_end_date" required>
                <input v-model="form.planned_end_date" type="date" class="form-control" :class="{ error: errors.planned_end_date }" required />
              </FormField>
            </div>

            <div class="form-grid" style="margin-bottom:var(--space-md);">
              <FormField label="Budget Main d'œuvre (FCFA)" :error="errors.budget_labor" required>
                <input v-model="form.budget_labor" type="number" class="form-control" :class="{ error: errors.budget_labor }"
                  placeholder="0" min="0" required />
              </FormField>

              <FormField label="Budget Matériel (FCFA)" :error="errors.budget_material" required>
                <input v-model="form.budget_material" type="number" class="form-control" :class="{ error: errors.budget_material }"
                  placeholder="0" min="0" required />
              </FormField>
            </div>

            <div class="form-grid" style="margin-bottom:var(--space-md);">
              <FormField label="Budget Transport (FCFA)" :error="errors.budget_transport" required>
                <input v-model="form.budget_transport" type="number" class="form-control" :class="{ error: errors.budget_transport }"
                  placeholder="0" min="0" required />
              </FormField>

              <FormField label="Autres coûts (FCFA)" :error="errors.budget_other" required>
                <input v-model="form.budget_other" type="number" class="form-control" :class="{ error: errors.budget_other }"
                  placeholder="0" min="0" required />
              </FormField>
            </div>

            <div class="budget-summary">
              <div>
                <div class="budget-summary-label">Budget total calculé</div>
                <div v-if="form.budget" style="font-size:.78rem; color:var(--color-text-muted); margin-top:2px;">= {{ fmt(form.budget) }}</div>
              </div>
              <div class="budget-summary-value">{{ form.budget ? fmt(form.budget) : '0 FCFA' }}</div>
            </div>
          </div>

          <!-- Contact fournisseur -->
          <div>
            <div class="form-section">
              <div class="form-section-icon"><Icon name="user" :size="18" /></div>
              <span class="form-section-title">Informations complémentaires</span>
            </div>

            <FormField label="Contact fournisseur">
              <input v-model="form.supplier_contact" type="text" class="form-control"
                placeholder="Nom, téléphone ou email du fournisseur principal" />
            </FormField>
          </div>

          <!-- Actions -->
          <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
            <Button as="Link" :href="route('projects.index')" variant="outline">Annuler</Button>
            <Button type="submit" variant="accent" :disabled="form.processing">
              <span v-if="form.processing">Création...</span>
              <span v-else style="display: flex; align-items: center; gap: 8px;">
                <Icon name="check" :size="16" />
                Créer le projet
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
  clients: Array,
});

const form = useForm({
  name:             '',
  client_id:        '',
  description:      '',
  start_date:       '',
  planned_end_date: '',
  budget:           0,
  budget_labor:     0,
  budget_material:  0,
  budget_transport: 0,
  budget_other:     0,
  status:           'en_cours',
  supplier_contact: '',
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

const submit = () => form.post(route('projects.store'));
</script>
