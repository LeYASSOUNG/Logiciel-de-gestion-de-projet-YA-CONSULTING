<template>
  <div class="empty-state-wrapper">
    <!-- Illustration concentrique animée -->
    <div class="empty-state-illustration">
      <div class="ring ring-outer" />
      <div class="ring ring-inner" />
      <div class="empty-state-icon-core">
        <Icon :name="icon" :size="28" />
      </div>
    </div>

    <h3 class="empty-state-title">{{ title }}</h3>
    <p v-if="description" class="empty-state-desc">{{ description }}</p>
    <div v-if="$slots.action" class="empty-state-action">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
import Icon from '@/Components/Icon.vue';

defineProps({
  icon:        { type: String, default: 'inbox' },
  title:       { type: String, required: true },
  description: { type: String, default: '' },
});
</script>

<style scoped>
.empty-state-wrapper {
  text-align: center;
  padding: var(--space-2xl) var(--space-xl);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--space-md);
  animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* Anneaux concentriques */
.empty-state-illustration {
  position: relative;
  width: 96px;
  height: 96px;
  margin-bottom: var(--space-sm);
  display: flex;
  align-items: center;
  justify-content: center;
}

.ring {
  position: absolute;
  border-radius: 50%;
  border: 2px dashed rgba(212, 177, 84, 0.2);
  animation: spinSlow 20s linear infinite;
}

.ring-outer {
  inset: 0;
}

.ring-inner {
  inset: 14px;
  border-color: rgba(212, 177, 84, 0.35);
  animation-direction: reverse;
  animation-duration: 15s;
}

.empty-state-icon-core {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(212,177,84,0.12), rgba(212,177,84,0.06));
  border: 1.5px solid rgba(212,177,84,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-accent-dark);
  position: relative;
  z-index: 1;
  box-shadow: 0 4px 16px rgba(212,177,84,0.1);
}

.empty-state-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-primary);
  margin: 0;
  font-family: var(--font-heading);
}

.empty-state-desc {
  color: var(--color-text-muted);
  font-size: .875rem;
  margin: 0;
  max-width: 320px;
  line-height: 1.65;
}

.empty-state-action {
  margin-top: var(--space-sm);
}

@keyframes spinSlow {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
