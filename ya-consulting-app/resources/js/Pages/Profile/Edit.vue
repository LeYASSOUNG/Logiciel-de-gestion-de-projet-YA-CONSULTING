<template>
  <AppLayout title="Mon Profil">
    <div class="animate-fade-up" style="max-width: 600px; margin: 0 auto;">
      <!-- En-tête -->
      <PageHeader title="Mon Profil" description="Gérez vos informations de compte et vos paramètres de sécurité" />

      <!-- Formulaire -->
      <Card>
        <form @submit.prevent="submit">
          <h3 class="section-title">Informations du compte</h3>

          <FormField label="Nom complet" :error="errors.name" required>
            <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" required />
          </FormField>

          <FormField label="Adresse email" :error="errors.email" required>
            <input v-model="form.email" type="email" class="form-control" :class="{ error: errors.email }" required />
          </FormField>

          <h3 class="section-title" style="margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
            Changer de mot de passe (optionnel)
          </h3>

          <FormField label="Mot de passe actuel" :error="errors.current_password">
            <input v-model="form.current_password" type="password" class="form-control" :class="{ error: errors.current_password }" placeholder="Saisir pour modifier votre mot de passe" />
          </FormField>

          <div class="form-grid">
            <FormField label="Nouveau mot de passe" :error="errors.new_password">
              <input v-model="form.new_password" type="password" class="form-control" :class="{ error: errors.new_password }" placeholder="Minimum 8 caractères" />
            </FormField>

            <FormField label="Confirmer le nouveau mot de passe">
              <input v-model="form.new_password_confirmation" type="password" class="form-control" placeholder="Ressaisir le mot de passe" />
            </FormField>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
            <Button type="submit" variant="primary" :disabled="form.processing">
              <span v-if="form.processing">Enregistrement...</span>
              <span v-else style="display: flex; align-items: center; gap: 8px;">
                <Icon name="check" :size="16" />
                Mettre à jour le profil
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
  user: Object,
});

const form = useForm({
  name:                    props.user.name,
  email:                   props.user.email,
  current_password:        '',
  new_password:            '',
  new_password_confirmation: '',
});

const errors = form.errors;

const submit = () => {
  form.patch(route('profile.update'), {
    onSuccess: () => {
      form.current_password = '';
      form.new_password = '';
      form.new_password_confirmation = '';
    },
  });
};
</script>
