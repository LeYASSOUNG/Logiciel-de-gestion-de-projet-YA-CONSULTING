<template>
  <AppLayout title="Nouveau paiement">
    <div class="animate-fade-up" style="max-width: 680px; margin: 0 auto;">
      <!-- En-tête -->
      <PageHeader title="Enregistrer un paiement" description="Saisissez les détails du montant encaissé auprès du client">
        <template v-slot:actions>
          <Button as="Link" :href="route('projects.show', project.id)" variant="outline">
            Retour au projet
          </Button>
        </template>
      </PageHeader>

      <Card>
        <!-- Info Projet -->
        <div style="margin-bottom: var(--space-xl); padding: var(--space-md); background: rgba(33, 150, 243, 0.05); border-radius: var(--radius-lg); border: 1px solid rgba(33, 150, 243, 0.1);">
          <h3 style="font-size: 1.1rem; color: var(--color-text-strong); margin-bottom: 8px;">{{ project.name }}</h3>
          <div style="display: flex; gap: 24px; font-size: 0.9rem; color: var(--color-text-muted);">
            <div>Budget (Prix) : <strong style="color: var(--color-blue)">{{ fmt(project.budget) }}</strong></div>
            <div>Déjà encaissé : <strong style="color: var(--color-emerald)">{{ fmt(project.total_paid) }}</strong></div>
            <div>Reste à payer : <strong style="color: var(--color-red)">{{ fmt(project.balance_due) }}</strong></div>
          </div>
        </div>

        <form @submit.prevent="submit">
          <div class="form-grid">
            <!-- Montant -->
            <FormField label="Montant Encaissé (FCFA)" :error="errors.amount" required>
              <input v-model="form.amount" type="number" class="form-control" :class="{ error: errors.amount }"
                placeholder="Ex: 500000" min="1" step="1" required />
              <div v-if="form.amount" style="font-size:.8rem; color:var(--color-text-muted); margin-top:4px;">
                = {{ fmt(form.amount) }}
              </div>
            </FormField>

            <!-- Date -->
            <FormField label="Date du paiement" :error="errors.payment_date" required>
              <input v-model="form.payment_date" type="date" class="form-control" :class="{ error: errors.payment_date }" required />
            </FormField>
          </div>

          <div class="form-grid">
            <!-- Méthode de paiement -->
            <FormField label="Mode de paiement" :error="errors.payment_method">
              <select v-model="form.payment_method" class="form-control" :class="{ error: errors.payment_method }">
                <option value="">Sélectionner...</option>
                <option value="Virement bancaire">Virement bancaire</option>
                <option value="Chèque">Chèque</option>
                <option value="Espèces">Espèces</option>
                <option value="Mobile Money">Mobile Money</option>
                <option value="Autre">Autre</option>
              </select>
            </FormField>

            <!-- Référence -->
            <FormField label="Référence (N° Chèque, Transaction...)" :error="errors.reference">
              <input v-model="form.reference" type="text" class="form-control" :class="{ error: errors.reference }"
                placeholder="Ex: CHQ-20485" />
            </FormField>
          </div>

          <!-- Actions -->
          <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
            <Button as="Link" :href="route('projects.show', project.id)" variant="outline">
              Annuler
            </Button>
            <Button type="submit" variant="accent" :disabled="form.processing">
              <span v-if="form.processing">Enregistrement...</span>
              <span v-else style="display: flex; align-items: center; gap: 8px;">
                <Icon name="check" :size="16" />
                Valider l'encaissement
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
  project: Object,
});

const form = useForm({
  project_id: props.project.id,
  amount: '',
  payment_date: new Date().toISOString().slice(0,10),
  payment_method: '',
  reference: '',
});

const errors = form.errors;

function submit() {
  form.post(route('payments.store'));
}

function fmt(value) {
  if (!value) return '0 FCFA';
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0
  }).format(value);
}
</script>
