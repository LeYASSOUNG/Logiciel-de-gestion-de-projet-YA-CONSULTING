<template>
  <AppLayout title="Mon Profil">
    <div class="animate-fade-up" style="max-width: 600px; margin: 0 auto;">
      <!-- En-tête -->
      <div class="page-header">
        <div class="page-header-info">
          <h1>Mon Profil</h1>
          <p>Gérez vos informations de compte et vos paramètres de sécurité</p>
        </div>
      </div>

      <!-- Formulaire -->
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit">
            <h3 style="font-size:.9rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.8px; margin:0 0 var(--space-lg);">
              Informations du compte
            </h3>

            <div class="form-group">
              <label class="form-label">Nom complet <span class="required">*</span></label>
              <input v-model="form.name" type="text" class="form-control" :class="{ error: errors.name }" required />
              <div v-if="errors.name" class="form-error">{{ errors.name }}</div>
            </div>

            <div class="form-group">
              <label class="form-label">Adresse email <span class="required">*</span></label>
              <input v-model="form.email" type="email" class="form-control" :class="{ error: errors.email }" required />
              <div v-if="errors.email" class="form-error">{{ errors.email }}</div>
            </div>

            <h3 style="font-size:.9rem; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.8px; margin:var(--space-xl) 0 var(--space-lg); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              Changer de mot de passe (optionnel)
            </h3>

            <div class="form-group">
              <label class="form-label">Mot de passe actuel</label>
              <input v-model="form.current_password" type="password" class="form-control" :class="{ error: errors.current_password }" placeholder="Saisir pour modifier votre mot de passe" />
              <div v-if="errors.current_password" class="form-error">{{ errors.current_password }}</div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Nouveau mot de passe</label>
                <input v-model="form.new_password" type="password" class="form-control" :class="{ error: errors.new_password }" placeholder="Minimum 8 caractères" />
                <div v-if="errors.new_password" class="form-error">{{ errors.new_password }}</div>
              </div>

              <div class="form-group">
                <label class="form-label">Confirmer le nouveau mot de passe</label>
                <input v-model="form.new_password_confirmation" type="password" class="form-control" placeholder="Ressaisir le mot de passe" />
              </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:var(--space-md); margin-top:var(--space-xl); padding-top:var(--space-lg); border-top:1px solid var(--color-border);">
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else>✓ Mettre à jour le profil</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
