<template>
  <AppLayout title="Nouveau décaissement">
    <div class="animate-fade-up" style="max-width: 680px; margin: 0 auto;">
      <!-- En-tête -->
      <PageHeader title="Enregistrer une dépense" description="Saisissez les détails de la transaction financière">
        <template v-slot:actions>
          <Button as="Link" :href="selected_project ? route('projects.show', selected_project.id) : route('expenses.index')" variant="outline">
            Retour
          </Button>
        </template>
      </PageHeader>

      <Card>
        <form @submit.prevent="submit">
          <!-- Projet -->
          <FormField label="Projet rattaché" :error="errors.project_id" required>
            <select v-model="form.project_id" class="form-control" :class="{ error: errors.project_id }" :disabled="!!selected_project" required>
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
                placeholder="Ex: 250000" min="1" step="1" required />
              <div v-if="form.amount" style="font-size:.8rem; color:var(--color-text-muted); margin-top:4px;">
                = {{ fmt(form.amount) }}
              </div>
            </FormField>

            <!-- Fichier justificatif -->
            <FormField label="Justificatif (PDF, Image - max 5Mo)" :error="errors.receipt">
              <input type="file" @input="form.receipt = $event.target.files[0]" class="form-control" :class="{ error: errors.receipt }" accept="image/*,application/pdf" />
            </FormField>
          </div>

          <!-- Description -->
          <FormField label="Description / Libellé de la dépense">
            <textarea v-model="form.description" class="form-control" rows="3"
              placeholder="Détails complémentaires de la transaction (ex: Facture #45, Achat de ciment...)" />
          </FormField>

          <!-- Actions -->
          <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
            <Button as="Link" :href="selected_project ? route('projects.show', selected_project.id) : route('expenses.index')" variant="outline">
              Annuler
            </Button>
            <Button type="submit" variant="accent" :disabled="form.processing">
              <span v-if="form.processing">Enregistrement...</span>
              <span v-else style="display: flex; align-items: center; gap: 8px;">
                <Icon name="check" :size="16" />
                Enregistrer la dépense
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

// ─── Propriétés Injectées par le Contrôleur ────────────────────────
// Ces variables sont passées depuis Laravel (ExpenseController@create)
const props = defineProps({
  projects:         Array,   // Liste déroulante : tous les projets accessibles
  categories:       Array,   // Liste déroulante : toutes les catégories de dépenses
  selected_project: Object,  // Projet sélectionné par défaut si l'utilisateur vient depuis une vue Projet
});

// ─── État du Formulaire (Inertia useForm) ──────────────────────────
// useForm gère l'état, les erreurs de validation et la soumission AJAX
const form = useForm({
  project_id:  props.selected_project?.id || '', // On auto-sélectionne le projet s'il est fourni
  category_id: '',
  date:        new Date().toISOString().split('T')[0], // Pré-remplit la date du jour (format YYYY-MM-DD)
  amount:      '',
  description: '',
  receipt:     null, // Champ fichier : accueillera l'image/PDF du justificatif
});

// Alias pratique pour accéder aux erreurs de validation renvoyées par le backend
const errors = form.errors;

// ─── Méthodes Utilitaires ──────────────────────────────────────────
/**
 * Formate un nombre pour l'affichage en devise (Franc CFA).
 * Permet de montrer un aperçu du montant en dessous de l'input pendant la saisie.
 * 
 * @param {Number|String} v Valeur numérique à formater
 * @returns {String} La valeur monétaire formatée
 */
const fmt = (v) => v ? new Intl.NumberFormat('fr-FR', {
  style: 'currency', currency: 'XOF', maximumFractionDigits: 0
}).format(v) : '';

// ─── Soumission des Données ────────────────────────────────────────
/**
 * Soumet les données du formulaire à la route 'expenses.store' (POST).
 * L'option 'forceFormData: true' est indispensable car le formulaire
 * contient un champ de téléchargement de fichier (receipt).
 */
const submit = () => {
  form.post(route('expenses.store'), {
    forceFormData: true,
  });
};
</script>
