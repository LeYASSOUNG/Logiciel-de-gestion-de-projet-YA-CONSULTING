<template>
  <div
    class="stat-card"
    :style="{
      '--stat-color': themeStyles.color,
      '--stat-bg': themeStyles.bg,
      '--stat-shadow-color': themeStyles.shadow
    }"
  >
    <div class="stat-card-header">
      <div class="stat-card-main">
        <div :class="['stat-card-value', colorValueClass]">{{ value }}</div>
        <div class="stat-card-label">{{ label }}</div>
      </div>
      <div v-if="icon" class="stat-card-icon">
        <Icon :name="icon" :size="24" />
      </div>
    </div>

    <!-- Trend indicator (optional) -->
    <div v-if="trend" class="stat-card-trend">
      <span :class="['trend', trendClass]">
        <span v-if="trend > 0">↑</span>
        <span v-else-if="trend < 0">↓</span>
        <span v-else>→</span>
        {{ Math.abs(trend) }}%
      </span>
      <span v-if="trendLabel" style="font-size:.72rem; color:var(--color-text-muted); margin-left:4px;">{{ trendLabel }}</span>
    </div>

    <div v-if="footer || $slots.footer" class="stat-card-footer">
      <slot name="footer">
        {{ footer }}
      </slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  label:           { type: String, required: true },
  value:           { type: [String, Number], required: true },
  icon:            { type: String, default: '' },
  color:           { type: String, default: 'accent' }, // success, danger, warning, info, accent, primary
  colorValueClass: { type: String, default: '' },
  footer:          { type: String, default: '' },
  trend:           { type: Number, default: null },      // positive = up, negative = down, 0 = flat
  trendLabel:      { type: String, default: '' },
});

// Theme style mappings
const themes = {
  primary: { color: 'var(--color-primary)', bg: 'rgba(26,43,74,.1)',   shadow: 'rgba(26,43,74,.2)' },
  accent:  { color: 'var(--color-accent)',  bg: 'rgba(201,168,76,.1)', shadow: 'rgba(201,168,76,.2)' },
  success: { color: 'var(--color-success)', bg: 'rgba(16,185,129,.1)', shadow: 'rgba(16,185,129,.2)' },
  danger:  { color: 'var(--color-danger)',  bg: 'rgba(239,68,68,.1)',  shadow: 'rgba(239,68,68,.2)' },
  warning: { color: 'var(--color-warning)', bg: 'rgba(245,158,11,.1)', shadow: 'rgba(245,158,11,.2)' },
  info:    { color: 'var(--color-info)',    bg: 'rgba(59,130,246,.1)', shadow: 'rgba(59,130,246,.2)' },
};

const themeStyles = computed(() => themes[props.color] || themes.accent);

const trendClass = computed(() => {
  if (props.trend === null) return '';
  if (props.trend > 0) return 'trend-up';
  if (props.trend < 0) return 'trend-down';
  return 'trend-flat';
});
</script>
