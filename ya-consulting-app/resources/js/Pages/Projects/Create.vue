<template>
  <AppLayout title="Nouveau projet">
    <div class="animate-fade-up" style="max-width: 720px; margin: 0 auto;">
      <div class="page-header">
        <div class="page-header-info">
          <h1>Créer un projet</h1>
          <p>Remplissez les informations pour créer un nouveau projet</p>
        </div>
        <Link :href="route('projects.index')" class="btn btn-outline">← Retour</Link>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit">
            <!-- Informations générales -->
            <div style="margin-bottom:var(--space-xl);">
              <h3 style="font-size:.9rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.8px; margin:0 0 var(--space-lg);">
                Informations générales
              </h3>

              <div class="form-group">
                <label class="form-label">Nom du projet <span class="required">*</span></label>
                <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }"
                  placeholder="Ex: Construction Immeuble A - Dakar" required />
                <div v-if="errors.name" class="form-error">{{ errors.name }}</div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Client <span class="required">*</span></label>
                  <select v-model="form.client_id" class="form-control" :class="{ error: errors.client_id }" required>
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
                <textarea v-model="form.description" class="form-control" rows="3"
                  placeholder="Décrivez le projet, ses objectifs, le contexte..." />
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
                  <input v-model="form.start_date" type="date" class="form-control" :class="{ error: errors.start_date }" required />
                  <div v-if="errors.start_date" class="form-error">{{ errors.start_date }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label">Date de fin prévisionnelle <span class="required">*</span></label>
                  <input v-model="form.planned_end_date" type="date" class="form-control" :class="{ error: errors.planned_end_date }" required />
                  <div v-if="errors.planned_end_date" class="form-error">{{ errors.planned_end_date }}</div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Budget fixé (FCFA) <span class="required">*</span></label>
                <input v-model="form.budget" type="number" class="form-control" :class="{ error: errors.budget }"
                  placeholder="Ex: 5000000" min="0" step="1000" required />
                <div v-if="errors.budget" class="form-error">{{ errors.budget }}</div>
                <div v-if="form.budget" style="font-size:.8rem; color:var(--color-text-muted); margin-top:4px;">
                  = {{ fmt(form.budget) }}
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
                <input v-model="form.supplier_contact" type="text" class="form-control"
                  placeholder="Nom, téléphone ou email du fournisseur principal" />
              </div>
            </div>

            <!-- Actions -->
            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <Link :href="route('projects.index')" class="btn btn-outline">Annuler</Link>
              <button type="submit" class="btn btn-accent" :disabled="form.processing">
                <span v-if="form.processing">Création...</span>
                <span v-else>✓ Créer le projet</span>
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
  clients: Array,
});

const form = useForm({
  name:             '',
  client_id:        '',
  description:      '',
  start_date:       '',
  planned_end_date: '',
  budget:           '',
  status:           'en_cours',
  supplier_contact: '',
});

const errors = form.errors;

const fmt = (v) => v ? new Intl.NumberFormat('fr-FR', {
  style: 'currency', currency: 'XOF', maximumFractionDigits: 0
}).format(v) : '';

const submit = () => form.post(route('projects.store'));
</script>
