<template>
  <component
    :is="iconComponent"
    :class="['icon', className]"
    :style="{ width: size + 'px', height: size + 'px' }"
    aria-hidden="true"
  />
</template>

<script setup>
import { computed } from 'vue';
import * as OutlineIcons from '@heroicons/vue/24/outline';

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
  className: { type: String, default: '' },
});

const iconComponent = computed(() => {
  // Convert kebab-case (e.g. arrow-left) to PascalCase (e.g. ArrowLeft)
  let pascalName = props.name
    .split('-')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join('');

  // Make sure it ends with "Icon"
  if (!pascalName.endsWith('Icon')) {
    pascalName += 'Icon';
  }

  // Fallback to QuestionMarkCircleIcon if not found
  return OutlineIcons[pascalName] || OutlineIcons.QuestionMarkCircleIcon;
});
</script>
