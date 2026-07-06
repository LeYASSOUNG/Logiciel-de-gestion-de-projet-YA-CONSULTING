<template>
  <div class="auth-wrapper">
    <div class="auth-card animate-fade-up">
      <div class="auth-header">
        <div class="auth-logo-container">
          <img src="/images/logo.svg" alt="YA Consulting Logo" class="auth-logo" />
        </div>
        <h1 class="auth-title">Créer votre Espace Client</h1>
        <p class="auth-subtitle">
          Bienvenue ! Veuillez configurer vos accès pour le suivi des projets de
          <strong style="color: var(--color-primary)">{{ client.name }}</strong>.
        </p>
      </div>

      <form @submit.prevent="submit" class="auth-form">
        <!-- Message d'erreur global si le token est invalide par exemple -->
        <div v-if="form.errors.general" class="alert-premium slide-down mb-lg">
          <Icon name="exclamation-circle" :size="20" />
          <span>{{ form.errors.general }}</span>
        </div>

        <FormField label="Votre Nom Complet" :error="form.errors.name" required>
          <input
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ error: form.errors.name }"
            placeholder="Ex: Moustapha Diop"
            required
            autofocus
          />
        </FormField>

        <FormField label="Adresse Email" :error="form.errors.email" required>
          <input
            v-model="form.email"
            type="email"
            class="form-control"
            :class="{ error: form.errors.email }"
            placeholder="votre@email.com"
            required
          />
        </FormField>

        <FormField label="Mot de Passe (8 caractères minimum)" :error="form.errors.password" required>
          <input
            v-model="form.password"
            type="password"
            class="form-control"
            :class="{ error: form.errors.password }"
            placeholder="••••••••"
            required
          />
        </FormField>

        <FormField label="Confirmer le Mot de Passe" :error="form.errors.password_confirmation" required>
          <input
            v-model="form.password_confirmation"
            type="password"
            class="form-control"
            :class="{ error: form.errors.password_confirmation }"
            placeholder="••••••••"
            required
          />
        </FormField>

        <div class="auth-actions">
          <Button
            type="submit"
            variant="primary"
            class="w-full"
            :disabled="form.processing"
            style="justify-content: center; padding: 12px;"
          >
            <span v-if="form.processing">Création en cours...</span>
            <span v-else>Accéder à mon espace</span>
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import FormField from '@/Components/FormField.vue';
import Button from '@/Components/Button.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  client: Object,
  defaultEmail: String,
});

const form = useForm({
  name: '',
  email: props.defaultEmail || '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('client.register.store', props.client.id), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<style scoped>
.auth-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-background);
  background-image: radial-gradient(circle at 100% 0%, rgba(212,175,55,0.08) 0%, transparent 25%),
                    radial-gradient(circle at 0% 100%, rgba(15,28,51,0.05) 0%, transparent 25%);
  padding: 20px;
}

.auth-card {
  background: white;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  width: 100%;
  max-width: 480px;
  padding: 40px;
  position: relative;
  overflow: hidden;
}

/* Liseré or au dessus de la carte */
.auth-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
}

.auth-header {
  text-align: center;
  margin-bottom: 30px;
}

.auth-logo-container {
  margin-bottom: 24px;
}

.auth-logo {
  height: 48px;
  width: auto;
}

.auth-title {
  font-size: 1.5rem;
  color: var(--color-text);
  font-weight: 700;
  margin-bottom: 8px;
  letter-spacing: -0.02em;
}

.auth-subtitle {
  color: var(--color-text-muted);
  font-size: 0.95rem;
  line-height: 1.5;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.auth-actions {
  margin-top: 10px;
}

.w-full {
  width: 100%;
}

.mb-lg {
  margin-bottom: 24px;
}

/* Alert styling from the rest of the app */
.alert-premium {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-md);
  background-color: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
  font-size: 0.9rem;
}
</style>
