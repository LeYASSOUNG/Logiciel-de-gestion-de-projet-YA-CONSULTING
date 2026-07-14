<template>
  <div
    class="stat-card"
    :style="{
      '--stat-color': themeStyles.color,
      '--stat-bg': themeStyles.bg,
    }"
  >
    <!-- Barre de couleur supérieure via ::before dans app.css -->

    <div class="stat-card-header">
      <div class="stat-card-main">
        <div class="stat-card-label">{{ label }}</div>
        <div :class="['stat-card-value', colorValueClass]">{{ value }}</div>
      </div>
      <div v-if="icon" class="stat-card-icon">
        <Icon :name="icon" :size="22" />
      </div>
    </div>

    <!-- Trend indicator -->
    <div v-if="trend !== null" class="stat-card-trend">
      <span :class="['trend', trendClass]">
        <svg v-if="trend > 0" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="18 15 12 9 6 15"/>
        </svg>
        <svg v-else-if="trend < 0" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
        <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        {{ Math.abs(trend) }}%
      </span>
      <span v-if="trendLabel" class="trend-label">{{ trendLabel }}</span>
    </div>

    <div v-if="footer || $slots.footer" class="stat-card-footer">
      <slot name="footer">{{ footer }}</slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  label:           { type: String,           required: true },
  value:           { type: [String, Number], required: true },
  icon:            { type: String,           default: '' },
  color:           { type: String,           default: 'accent' },
  colorValueClass: { type: String,           default: '' },
  footer:          { type: String,           default: '' },
  trend:           { type: Number,           default: null },
  trendLabel:      { type: String,           default: '' },
});

const themes = {
  primary: { color: 'var(--color-primary)', bg: 'rgba(15,28,51,.1)' },
  accent:  { color: 'var(--color-accent)',  bg: 'rgba(212,177,84,.12)' },
  success: { color: 'var(--color-success)', bg: 'rgba(5,150,105,.1)' },
  danger:  { color: 'var(--color-danger)',  bg: 'rgba(220,38,38,.1)' },
  warning: { color: 'var(--color-warning)', bg: 'rgba(217,119,6,.1)' },
  info:    { color: 'var(--color-info)',    bg: 'rgba(2,132,199,.1)' },
};

const themeStyles = computed(() => themes[props.color] || themes.accent);

const trendClass = computed(() => {
  if (props.trend === null) return '';
  if (props.trend > 0)  return 'trend-up';
  if (props.trend < 0)  return 'trend-down';
  return 'trend-flat';
});
</script>

<style scoped>
.stat-card-trend {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 10px;
}

.trend-label {
  font-size: .72rem;
  color: var(--color-text-muted);
}
</style>
