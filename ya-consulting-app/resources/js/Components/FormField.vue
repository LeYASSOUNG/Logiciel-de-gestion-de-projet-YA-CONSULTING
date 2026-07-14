<template>
  <div class="form-group">
    <label v-if="label" class="form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <slot />
    <transition name="field-error">
      <div v-if="error" class="form-error">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ error }}
      </div>
    </transition>
    <div v-if="hint && !error" class="form-hint">{{ hint }}</div>
  </div>
</template>

<script setup>
defineProps({
  label:    { type: String,  default: '' },
  required: { type: Boolean, default: false },
  error:    { type: String,  default: '' },
  hint:     { type: String,  default: '' },
});
</script>

<style scoped>
.form-group {
  margin-bottom: var(--space-lg);
}

.form-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 6px;
  letter-spacing: 0.1px;
}

.required {
  color: var(--color-danger);
  margin-left: 2px;
}

.form-error {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.78rem;
  color: var(--color-danger);
  margin-top: 5px;
  font-weight: 500;
}

.form-hint {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  margin-top: 5px;
  line-height: 1.5;
}

/* Transition erreur */
.field-error-enter-active {
  animation: slideError 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.field-error-leave-active {
  animation: slideError 0.2s ease reverse;
}
@keyframes slideError {
  from { opacity: 0; transform: translateY(-4px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
