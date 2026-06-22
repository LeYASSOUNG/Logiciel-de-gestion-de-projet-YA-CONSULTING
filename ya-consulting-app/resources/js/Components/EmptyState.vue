<template>
  <div class="empty-state-wrapper">
    <!-- SVG Illustration -->
    <div class="empty-state-illustration">
      <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="40" r="40" fill="rgba(26,43,74,.06)" />
        <circle cx="40" cy="40" r="28" fill="rgba(26,43,74,.06)" />
        <Icon :name="icon" :size="30" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color: var(--color-text-light);" />
      </svg>
      <!-- Use a simpler icon approach -->
      <div class="empty-state-icon-wrap">
        <Icon :name="icon" :size="32" />
      </div>
    </div>

    <h3 class="empty-state-title">{{ title }}</h3>
    <p v-if="description" class="empty-state-desc">{{ description }}</p>
    <slot name="action" />
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
}

.empty-state-illustration {
  position: relative;
  width: 88px;
  height: 88px;
  margin-bottom: var(--space-lg);
}

.empty-state-illustration::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(26,43,74,.06);
}

.empty-state-illustration::after {
  content: '';
  position: absolute;
  inset: 12px;
  border-radius: 50%;
  background: rgba(26,43,74,.07);
}

.empty-state-icon-wrap {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-light);
  z-index: 1;
}

.empty-state-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 var(--space-sm) 0;
}

.empty-state-desc {
  color: var(--color-text-muted);
  font-size: .875rem;
  margin: 0 0 var(--space-lg) 0;
  max-width: 320px;
  line-height: 1.6;
}
</style>
