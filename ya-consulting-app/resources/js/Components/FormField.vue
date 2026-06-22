<template>
  <div class="form-group">
    <label v-if="label" class="form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <slot />
    <div v-if="error" class="form-error">
      <Icon name="exclamation-triangle" :size="14" style="color: var(--color-danger);" />
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import Icon from '@/Components/Icon.vue';

defineProps({
  label: { type: String, default: '' },
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
});
</script>
