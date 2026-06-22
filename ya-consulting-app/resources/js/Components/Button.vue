<template>
  <Link
    v-if="as === 'Link'"
    :href="href"
    :class="btnClasses"
    :method="method"
    :data="data"
    :as="linkAs"
  >
    <slot name="icon-left" />
    <slot />
    <slot name="icon-right" />
  </Link>
  <a
    v-else-if="as === 'a'"
    :href="href"
    :class="btnClasses"
    :target="target"
  >
    <slot name="icon-left" />
    <slot />
    <slot name="icon-right" />
  </a>
  <button
    v-else
    :type="type"
    :class="btnClasses"
    :disabled="disabled"
  >
    <slot name="icon-left" />
    <slot />
    <slot name="icon-right" />
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  variant: { type: String, default: 'primary' }, // primary, accent, outline, danger
  size: { type: String, default: 'md' }, // sm, md, lg
  as: { type: String, default: 'button' }, // button, Link, a
  href: { type: String, default: '' },
  target: { type: String, default: '_self' },
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  method: { type: String, default: 'get' },
  data: { type: Object, default: () => ({}) },
  linkAs: { type: String, default: 'a' },
});

const btnClasses = computed(() => {
  return [
    'btn',
    `btn-${props.variant}`,
    props.size !== 'md' ? `btn-${props.size}` : '',
  ].filter(Boolean).join(' ');
});
</script>
