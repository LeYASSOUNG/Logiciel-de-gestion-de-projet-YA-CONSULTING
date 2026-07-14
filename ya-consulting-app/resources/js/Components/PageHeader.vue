<template>
  <div class="page-header">
    <div class="page-header-info">
      <div class="page-header-title-row">
        <h1 class="page-header-title">{{ title }}</h1>
        <slot name="badge" />
      </div>
      <p v-if="description" class="page-header-desc">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="page-header-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:       { type: String, required: true },
  description: { type: String, default: '' },
});
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: var(--space-xl);
  gap: var(--space-lg);
  flex-wrap: wrap;
}

.page-header-info {
  flex: 1;
  min-width: 0;
}

.page-header-title-row {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex-wrap: wrap;
}

.page-header-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-primary);
  letter-spacing: -0.5px;
  margin: 0;
  font-family: var(--font-heading);
  line-height: 1.2;
}

.page-header-desc {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin: 6px 0 0;
  line-height: 1.5;
}

.page-header-actions {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex-wrap: wrap;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
    gap: var(--space-md);
  }
  .page-header-title { font-size: 1.35rem; }
  .page-header-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
