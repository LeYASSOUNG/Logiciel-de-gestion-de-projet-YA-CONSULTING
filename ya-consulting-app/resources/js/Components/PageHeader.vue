<template>
  <div class="page-header">
    <div class="page-header-info">
      <div style="display: flex; align-items: center; gap: var(--space-sm); flex-wrap: wrap;">
        <h1>{{ title }}</h1>
        <slot name="badge" />
      </div>
      <p v-if="description">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="page-header-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
