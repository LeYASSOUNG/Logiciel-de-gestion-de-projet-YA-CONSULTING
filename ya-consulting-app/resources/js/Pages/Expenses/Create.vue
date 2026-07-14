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

            <!-- Fichier justificatif amélioré -->
            <FormField label="Justificatif (PDF, Image — max 5 Mo)" :error="errors.receipt">
              <div class="file-drop-zone" :class="{ 'has-file': selectedFileName }">
                <input
                  type="file"
                  @input="handleFile"
                  class="file-input"
                  accept="image/*,application/pdf"
                />
                <div v-if="!selectedFileName" class="file-placeholder">
                  <div class="file-icon">
                    <Icon name="arrow-up-tray" :size="22" />
                  </div>
                  <div class="file-text">
                    <span class="file-cta">Cliquer ou glisser un fichier</span>
                    <span class="file-sub">PDF, PNG, JPG — max 5 Mo</span>
                  </div>
                </div>
                <div v-else class="file-selected">
                  <Icon name="paper-clip" :size="18" />
                  <span>{{ selectedFileName }}</span>
                  <span class="file-remove" @click.prevent="removeFile">
                    <Icon name="x-mark" :size="14" />
                  </span>
                </div>
              </div>
            </FormField>
          </div>

          <!-- Description -->
          <FormField label="Description / Libellé de la dépense">
            <textarea v-model="form.description" class="form-control" rows="3"
              placeholder="Détails complémentaires de la transaction (ex: Facture #45, Achat de ciment...)" />
          </FormField>

          <!-- Actions -->
          <div class="form-actions">
            <Button as="Link" :href="selected_project ? route('projects.show', selected_project.id) : route('expenses.index')" variant="outline">
              <template v-slot:icon-left><Icon name="arrow-left" :size="15" /></template>
              Annuler
            </Button>
            <Button type="submit" variant="accent" :disabled="form.processing">
              <template v-if="form.processing">Enregistrement...</template>
              <template v-else>
                <Icon name="check" :size="16" /> Enregistrer la dépense
              </template>
            </Button>
          </div>
        </form>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
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

const selectedFileName = ref('');

const handleFile = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.receipt = file;
    selectedFileName.value = file.name;
  }
};

const removeFile = () => {
  form.receipt = null;
  selectedFileName.value = '';
};
</script>

<style scoped>
/* Zone de drop fichier */
.file-drop-zone {
  position: relative;
  border: 2px dashed var(--color-border-light);
  border-radius: var(--radius-md);
  padding: 20px;
  cursor: pointer;
  transition: var(--transition);
  background: var(--color-bg-light);
  overflow: hidden;
}

.file-drop-zone:hover {
  border-color: var(--color-accent);
  background: rgba(212,177,84,.04);
}

.file-drop-zone.has-file {
  border-style: solid;
  border-color: var(--color-success);
  background: rgba(5,150,105,.04);
}

.file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.file-placeholder {
  display: flex;
  align-items: center;
  gap: 14px;
  pointer-events: none;
}

.file-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  background: rgba(212,177,84,.1);
  color: var(--color-accent-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.file-text {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.file-cta {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-primary);
}

.file-sub {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.file-selected {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--color-success);
  font-weight: 600;
  font-size: 0.875rem;
  pointer-events: none;
}

.file-selected > :last-child {
  pointer-events: all;
  cursor: pointer;
  margin-left: auto;
  color: var(--color-text-muted);
  padding: 4px;
  border-radius: var(--radius-xs);
  transition: var(--transition);
}

.file-selected > :last-child:hover {
  background: rgba(220,38,38,.1);
  color: var(--color-danger);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: var(--space-md);
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: 1px solid var(--color-border-light);
}
</style>
