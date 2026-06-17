<template>
  <div class="badge" :class="badgeClass">
    {{ label }}
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, required: true },
});

const config = {
  en_cours:  { class: 'badge-info',    label: 'En cours' },
  termine:   { class: 'badge-success', label: 'Terminé' },
  en_pause:  { class: 'badge-warning', label: 'En pause' },
};

const badgeClass = computed(() => config[props.status]?.class || 'badge-neutral');
const label      = computed(() => config[props.status]?.label || props.status);
</script>
