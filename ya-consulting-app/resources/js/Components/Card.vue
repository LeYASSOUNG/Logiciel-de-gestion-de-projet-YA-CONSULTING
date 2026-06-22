<template>
  <div class="card">
    <div v-if="title || $slots.header" class="card-header">
      <slot name="header">
        <h2 class="card-title">{{ title }}</h2>
      </slot>
      <div v-if="$slots.actions" class="card-header-actions">
        <slot name="actions" />
      </div>
    </div>
    <div class="card-body">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
});
</script>
