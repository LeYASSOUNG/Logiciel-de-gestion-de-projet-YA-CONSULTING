<template>
  <div v-if="links && links.length > 3" class="pagination-container" style="display:flex; justify-content:center; gap:6px; padding:16px; border-top:1px solid var(--color-border);">
    <component
      :is="link.url && !link.active ? Link : 'span'"
      v-for="(link, key) in links"
      :key="key"
      :href="link.url || '#'"
      class="btn btn-outline btn-sm"
      :class="{ 'active': link.active, 'disabled': !link.url }"
      :style="link.active ? 'background:var(--color-primary); color:#fff; border-color:var(--color-primary)' : (!link.url ? 'opacity: 0.5; cursor: not-allowed;' : '')"
      v-html="link.label"
    />
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  links: { type: Array, required: true },
});
</script>
