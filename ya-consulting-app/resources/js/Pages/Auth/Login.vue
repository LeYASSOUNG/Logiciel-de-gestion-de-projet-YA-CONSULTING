<template>
  <div class="login-page">
    <!-- Panneau gauche - Branding -->
    <div class="login-brand">
      <div class="login-brand-content">
        <div class="brand-logo">
          <span>YA</span>
        </div>
        <h1 class="brand-name">YA Consulting</h1>
        <p class="brand-tagline">Logiciel de gestion et suivi<br>de la rentabilité de vos projets</p>

        <div class="brand-features">
          <div class="feature-item" v-for="feat in features" :key="feat.label">
            <div class="feature-icon">{{ feat.icon }}</div>
            <div>
              <div class="feature-title">{{ feat.label }}</div>
              <div class="feature-desc">{{ feat.desc }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Decoration -->
      <div class="brand-deco deco-1"></div>
      <div class="brand-deco deco-2"></div>
    </div>

    <!-- Panneau droit - Formulaire -->
    <div class="login-form-panel">
      <div class="login-form-container">
        <div style="text-align:center; margin-bottom:var(--space-2xl);">
          <h2 style="font-size:1.8rem; font-weight:800; color:var(--color-text); margin:0 0 8px;">
            Connexion
          </h2>
          <p style="color:var(--color-text-muted);">
            Connectez-vous à votre espace de gestion
          </p>
        </div>

        <!-- Message status (reset password) -->
        <div v-if="status" class="alert alert-success" style="margin-bottom:var(--space-lg);">
          {{ status }}
        </div>

        <form @submit.prevent="submit">
          <div class="form-group">
            <label class="form-label">Adresse email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="form-control"
              :class="{ error: form.errors.email }"
              placeholder="votre@email.com"
              autocomplete="username"
              required
              autofocus
            />
            <div v-if="form.errors.email" class="form-error">
              {{ form.errors.email }}
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">
              <span>Mot de passe</span>
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              class="form-control"
              :class="{ error: form.errors.password }"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            />
            <div v-if="form.errors.password" class="form-error">
              {{ form.errors.password }}
            </div>
          </div>

          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--space-xl);">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:.875rem;">
              <input v-model="form.remember" type="checkbox" style="width:16px; height:16px; accent-color:var(--color-primary);" />
              Se souvenir de moi
            </label>
          </div>

          <button
            type="submit"
            class="btn btn-primary"
            style="width:100%; justify-content:center; padding:13px; font-size:1rem;"
            :disabled="form.processing"
          >
            <span v-if="form.processing">Connexion en cours...</span>
            <span v-else>Se connecter →</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  email:    '',
  password: '',
  remember: false,
});

const features = [
  { icon: '📊', label: 'Suivi en temps réel',  desc: 'Dépenses et rentabilité mise à jour instantanément' },
  { icon: '📈', label: 'Statistiques avancées', desc: 'Analyses et KPIs pour piloter votre activité' },
  { icon: '📄', label: 'Rapports PDF & Excel',  desc: 'Exports mensuels automatisés en un clic' },
  { icon: '🔒', label: 'Sécurisé & traçable',   desc: 'Journal d\'audit complet de toutes les actions' },
];

const submit = () => form.post(route('login'));
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
}

.login-brand {
  flex: 1;
  background: var(--color-primary);
  padding: var(--space-2xl);
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.login-brand-content { position: relative; z-index: 2; }

.brand-logo {
  width: 64px;
  height: 64px;
  background: var(--color-accent);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 900;
  color: var(--color-primary-dark);
  margin-bottom: var(--space-lg);
  letter-spacing: -1px;
}

.brand-name {
  font-size: 2.2rem;
  font-weight: 900;
  color: #fff;
  margin: 0 0 var(--space-md);
  letter-spacing: -1px;
}

.brand-tagline {
  font-size: 1.05rem;
  color: rgba(255,255,255,.65);
  line-height: 1.6;
  margin-bottom: var(--space-2xl);
}

.brand-features { display: flex; flex-direction: column; gap: var(--space-lg); }

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: var(--radius-md);
  padding: var(--space-md) var(--space-lg);
}

.feature-icon { font-size: 1.3rem; flex-shrink: 0; }

.feature-title { color: #fff; font-weight: 600; font-size: .9rem; }
.feature-desc  { color: rgba(255,255,255,.5); font-size: .8rem; margin-top: 2px; }

.brand-deco {
  position: absolute;
  border-radius: 50%;
  background: rgba(201,168,76,.08);
  z-index: 1;
}
.deco-1 { width: 400px; height: 400px; top: -100px; right: -150px; }
.deco-2 { width: 250px; height: 250px; bottom: -80px; left: -80px; background: rgba(255,255,255,.04); }

.login-form-panel {
  width: 480px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-2xl);
  background: var(--color-bg);
}

.login-form-container {
  width: 100%;
  max-width: 380px;
}

@media (max-width: 768px) {
  .login-brand { display: none; }
  .login-form-panel { width: 100%; }
}
</style>
