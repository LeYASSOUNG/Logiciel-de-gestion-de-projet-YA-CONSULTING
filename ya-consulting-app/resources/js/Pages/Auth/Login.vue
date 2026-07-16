<template>
  <div class="login-wrapper">
    <!-- Animated background elements -->
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>

    <div class="login-container">
      <!-- Left side: Branding & Value prop -->
      <div class="login-info-panel">
        <div class="brand-header animate-fade-up">
          <div class="logo-wrapper">
             <img src="/images/logo.svg" alt="YA Consulting Logo" />
          </div>
        </div>
        
        <div class="brand-content animate-fade-up" style="animation-delay: 0.1s;">
          <h1 class="brand-title">L'excellence dans la gestion de projets.</h1>
          <p class="brand-subtitle">
            Pilotez votre rentabilité, maîtrisez vos dépenses et prenez des décisions éclairées avec notre solution GEST-PRO.
          </p>
          
          <div class="features-list">
            <div class="feature-item" v-for="(feat, index) in features" :key="feat.label" :style="`animation-delay: ${0.2 + index * 0.1}s`">
              <div class="feature-icon">
                <Icon :name="feat.icon" :size="20" />
              </div>
              <div class="feature-text">
                <h3>{{ feat.label }}</h3>
                <p>{{ feat.desc }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side: Form -->
      <div class="login-form-panel animate-slide-in">
        <div class="glass-card">
          <!-- Mobile logo -->
          <div class="mobile-logo">
             <img src="/images/logo.svg" alt="YA Consulting Logo" />
          </div>
          
          <div class="form-header">
            <h2>Bienvenue 👋</h2>
            <p>Connectez-vous pour accéder à votre espace de gestion.</p>
          </div>

          <div v-if="status" class="alert alert-success">
            <Icon name="check-circle" :size="16" />
            {{ status }}
          </div>

          <form @submit.prevent="submit" class="login-form">
            <div class="input-group">
              <label for="email">Adresse email</label>
              <div class="input-wrapper">
                <Icon name="envelope" class="input-icon" :size="18" />
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  :class="{ 'has-error': form.errors.email }"
                  placeholder="votre@email.com"
                  autocomplete="username"
                  required
                  autofocus
                />
              </div>
              <span v-if="form.errors.email" class="error-msg">{{ form.errors.email }}</span>
            </div>

            <div class="input-group">
              <label for="password">Mot de passe</label>
              <div class="input-wrapper">
                <Icon name="lock-closed" class="input-icon" :size="18" />
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  :class="{ 'has-error': form.errors.password }"
                  placeholder="••••••••"
                  autocomplete="current-password"
                  required
                />
                <button 
                  type="button" 
                  class="password-toggle"
                  @click="showPassword = !showPassword"
                >
                  <Icon :name="showPassword ? 'eye-slash' : 'eye'" :size="18" />
                </button>
              </div>
              <span v-if="form.errors.password" class="error-msg">{{ form.errors.password }}</span>
            </div>

            <div class="form-options">
              <label class="checkbox-label">
                <input v-model="form.remember" type="checkbox" />
                <span class="checkmark"></span>
                Se souvenir de moi
              </label>
            </div>

            <button
              type="submit"
              class="btn-submit"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="loader"></span>
              <span v-else class="btn-content">
                Se connecter
                <Icon name="arrow-right" :size="16" />
              </span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  email:    '',
  password: '',
  remember: false,
});

const showPassword = ref(false);

const features = [
  { icon: 'chart-bar', label: 'Suivi en temps réel',  desc: 'Dépenses et rentabilité mise à jour instantanément' },
  { icon: 'arrow-trending-up', label: 'Statistiques avancées', desc: 'Analyses et KPIs pour piloter votre activité' },
  { icon: 'document-text', label: 'Rapports PDF & Excel',  desc: 'Exports mensuels automatisés en un clic' },
  { icon: 'shield-check', label: 'Sécurisé & traçable',   desc: 'Journal d\'audit complet de toutes les actions' },
];

const submit = () => form.post(route('login'));
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* Base Layout */
.login-wrapper {
  min-height: 100vh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0F1C33;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

/* Background animated shapes */
.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  z-index: 0;
  animation: float 20s infinite ease-in-out alternate;
}

.bg-shape-1 {
  width: 500px;
  height: 500px;
  background: rgba(43, 88, 175, 0.4);
  top: -150px;
  left: -150px;
  animation-delay: 0s;
}

.bg-shape-2 {
  width: 400px;
  height: 400px;
  background: rgba(201, 168, 76, 0.25);
  bottom: -100px;
  right: -50px;
  animation-delay: -5s;
}

.bg-shape-3 {
  width: 350px;
  height: 350px;
  background: rgba(36, 171, 230, 0.3);
  top: 25%;
  left: 35%;
  animation-delay: -10s;
}

@keyframes float {
  0% { transform: translate(0, 0) scale(1); }
  100% { transform: translate(40px, 30px) scale(1.1); }
}

/* Container */
.login-container {
  width: 100%;
  max-width: 1100px;
  min-height: 600px;
  display: flex;
  position: relative;
  z-index: 1;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  background: rgba(255, 255, 255, 1);
  margin: 20px;
}

/* Left Panel */
.login-info-panel {
  flex: 1.2;
  padding: 60px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  background: linear-gradient(135deg, #1A2B4A 0%, #0F1C33 100%);
  color: #ffffff;
}

.logo-wrapper {
  background: #ffffff;
  border-radius: 16px;
  height: 70px;
  width: fit-content;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 40px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.logo-wrapper:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.logo-wrapper img {
  height: 100%;
  object-fit: contain;
}

.brand-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.15;
  margin-bottom: 20px;
  letter-spacing: -0.5px;
}

.brand-subtitle {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  margin-bottom: 48px;
  max-width: 440px;
}

.features-list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 16px;
  opacity: 0;
  animation: fadeInUp 0.6s ease forwards;
}

.feature-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(201, 168, 76, 0.15);
  color: #C9A84C;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.feature-item:hover .feature-icon {
  background: rgba(201, 168, 76, 0.25);
  transform: scale(1.1) rotate(5deg);
}

.feature-text h3 {
  color: #ffffff;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 4px 0;
}

.feature-text p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.85rem;
  margin: 0;
  line-height: 1.4;
}

/* Right Panel (Form) */
.login-form-panel {
  flex: 1;
  padding: 60px 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
}

.glass-card {
  width: 100%;
  max-width: 400px;
  background: #ffffff;
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.03);
}

.mobile-logo {
  display: none;
}

.form-header {
  margin-bottom: 32px;
}

.form-header h2 {
  font-size: 2rem;
  font-weight: 800;
  color: #1A2B4A;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.form-header p {
  color: #64748B;
  font-size: 0.95rem;
  margin: 0;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.input-group label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1E293B;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 16px;
  color: #94A3B8;
  transition: color 0.3s ease;
}

.input-wrapper input {
  width: 100%;
  padding: 14px 44px 14px 44px;
  border: 1.5px solid #E2E8F0;
  border-radius: 12px;
  font-size: 1rem;
  color: #1E293B;
  background: #ffffff;
  transition: all 0.3s ease;
  outline: none;
  font-family: inherit;
}

/* Fix for Chrome autofill background */
.input-wrapper input:-webkit-autofill,
.input-wrapper input:-webkit-autofill:hover, 
.input-wrapper input:-webkit-autofill:focus, 
.input-wrapper input:-webkit-autofill:active{
    -webkit-box-shadow: 0 0 0 30px white inset !important;
    -webkit-text-fill-color: #1E293B !important;
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
}

.input-wrapper input:focus {
  border-color: #C9A84C;
  box-shadow: 0 0 0 4px rgba(201, 168, 76, 0.1);
}

.password-toggle {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94A3B8;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.password-toggle:hover {
  color: #1A2B4A;
}

.input-wrapper input:focus + .input-icon,
.input-wrapper input:not(:placeholder-shown) + .input-icon {
  color: #1A2B4A;
}

.input-wrapper input.has-error {
  border-color: #EF4444;
}

.error-msg {
  font-size: 0.85rem;
  color: #EF4444;
  margin-top: 4px;
}

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: -4px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  color: #64748B;
  cursor: pointer;
  user-select: none;
  font-weight: 500;
}

.checkbox-label input {
  display: none;
}

.checkmark {
  width: 20px;
  height: 20px;
  border: 2px solid #CBD5E1;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  position: relative;
  background: #ffffff;
}

.checkbox-label input:checked ~ .checkmark {
  background: #1A2B4A;
  border-color: #1A2B4A;
}

.checkbox-label input:checked ~ .checkmark::after {
  content: '';
  position: absolute;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
  top: 2px;
}

.btn-submit {
  background: #1A2B4A;
  color: #ffffff;
  border: none;
  border-radius: 12px;
  padding: 16px;
  font-size: 1.05rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(26, 43, 74, 0.2), 0 2px 4px -1px rgba(26, 43, 74, 0.1);
  font-family: inherit;
}

.btn-submit:hover:not(:disabled) {
  background: #27406a;
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(26, 43, 74, 0.3), 0 4px 6px -2px rgba(26, 43, 74, 0.15);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-content {
  display: flex;
  align-items: center;
  gap: 10px;
  transition: transform 0.3s ease;
}

.btn-submit:hover:not(:disabled) .btn-content {
  transform: translateX(4px);
}

.loader {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Animations */
.animate-fade-up {
  opacity: 0;
  animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-slide-in {
  opacity: 0;
  animation: slideInRight 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInRight {
  from { opacity: 0; transform: translateX(30px); }
  to { opacity: 1; transform: translateX(0); }
}

/* Responsive */
@media (max-width: 900px) {
  .login-info-panel {
    display: none;
  }
  
  .login-container {
    background: transparent;
    border: none;
    box-shadow: none;
    margin: 0;
    min-height: auto;
  }
  
  .login-form-panel {
    padding: 24px;
    background: transparent;
  }
  
  .glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(24px);
    padding: 40px;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.4);
  }
  
  .mobile-logo {
    display: flex;
    background: #ffffff;
    border-radius: 16px;
    height: 60px;
    width: fit-content;
    padding: 10px 20px;
    margin: 0 auto 32px auto;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  }
  
  .mobile-logo img {
    height: 100%;
    object-fit: contain;
  }
  
  .form-header {
    text-align: center;
    margin-bottom: 32px;
  }
}

@media (max-width: 480px) {
  .glass-card {
    padding: 32px 24px;
  }
  .form-header h2 {
    font-size: 1.8rem;
  }
}
</style>
