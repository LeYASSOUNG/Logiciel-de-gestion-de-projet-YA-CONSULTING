<template>
  <div class="toast-wrapper">
    <TransitionGroup name="toast">
      <div 
        v-for="toast in toasts" 
        :key="toast.id"
        class="toast-item"
        :class="`toast-${toast.type}`"
      >
        <div class="toast-icon-wrap">
          <Icon :name="getIcon(toast.type)" :size="20" class="toast-icon" />
        </div>
        <div class="toast-content">
          <span class="toast-title">{{ getTitle(toast.type) }}</span>
          <span class="toast-message">{{ toast.message }}</span>
        </div>
        <button class="toast-close" @click="remove(toast.id)">
          <Icon name="x-mark" :size="16" />
        </button>
        <!-- Progress bar -->
        <div class="toast-progress" :class="`bg-${toast.type}`"></div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const page = usePage();
const toasts = ref([]);
let toastId = 0;

const addToast = (type, message) => {
  if (!message) return;
  const id = ++toastId;
  toasts.value.push({ id, type, message });
  setTimeout(() => remove(id), 5000);
};

const remove = (id) => {
  toasts.value = toasts.value.filter(t => t.id !== id);
};

const getIcon = (type) => {
  if (type === 'success') return 'check-circle';
  if (type === 'error') return 'exclamation-circle';
  if (type === 'warning') return 'exclamation-triangle';
  return 'info-circle';
};

const getTitle = (type) => {
  if (type === 'success') return 'Succès';
  if (type === 'error') return 'Erreur';
  if (type === 'warning') return 'Attention';
  return 'Information';
};

// Observers pour intercepter les nouveaux flash
watch(() => page.props.flash, (flash) => {
  if (flash?.success) addToast('success', flash.success);
  if (flash?.error) addToast('error', flash.error);
  if (flash?.warning) addToast('warning', flash.warning);
}, { deep: true });

onMounted(() => {
  if (page.props.flash?.success) addToast('success', page.props.flash.success);
  if (page.props.flash?.error) addToast('error', page.props.flash.error);
  if (page.props.flash?.warning) addToast('warning', page.props.flash.warning);
});
</script>

<style scoped>
.toast-wrapper {
  position: fixed;
  bottom: 32px;
  right: 32px;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  gap: 16px;
  pointer-events: none;
}

.toast-item {
  pointer-events: auto;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15), 0 10px 20px -10px rgba(0, 0, 0, 0.1);
  min-width: 320px;
  max-width: 420px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  transform-origin: center right;
  position: relative;
  overflow: hidden;
}

.toast-icon-wrap {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
}

.toast-success .toast-icon-wrap { background: rgba(16, 185, 129, 0.1); }
.toast-success .toast-icon { color: var(--color-emerald); }

.toast-error .toast-icon-wrap { background: rgba(239, 68, 68, 0.1); }
.toast-error .toast-icon { color: var(--color-red); }

.toast-warning .toast-icon-wrap { background: rgba(245, 158, 11, 0.1); }
.toast-warning .toast-icon { color: var(--color-warning); }

.toast-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.toast-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-text);
  line-height: 1.2;
}

.toast-message {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  line-height: 1.4;
}

.toast-close {
  flex-shrink: 0;
  background: transparent;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s ease;
  margin-top: -4px;
  margin-right: -4px;
}
.toast-close:hover {
  background: rgba(15,28,51,0.06);
  color: var(--color-primary);
}

/* Barre de progression */
.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 4px;
  background: var(--color-primary);
  animation: progress 5s linear forwards;
  opacity: 0.2;
}

.bg-success { background: var(--color-emerald); }
.bg-error { background: var(--color-red); }
.bg-warning { background: var(--color-warning); }

@keyframes progress {
  from { width: 100%; }
  to { width: 0%; }
}

/* Transitions pour le groupe */
.toast-enter-active,
.toast-leave-active,
.toast-move {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100px) scale(0.95);
}
.toast-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
.toast-leave-active {
  position: absolute;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@media (max-width: 480px) {
  .toast-wrapper {
    bottom: 16px;
    right: 16px;
    left: 16px;
  }
  .toast-item {
    min-width: unset;
    width: 100%;
  }
}
</style>
