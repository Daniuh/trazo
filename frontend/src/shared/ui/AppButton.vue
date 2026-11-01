<!-- Botón base. Variantes: principal, secundario, peligro y enlace; mientras carga queda bloqueado. Con `to` es un
     enlace de la app con el mismo aspecto (para navegar, no para acciones). -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'
import type { IconName } from '@/design/icons/icons'
import AppIcon from './AppIcon.vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'link'
    icon?: IconName
    loading?: boolean
    disabled?: boolean
    block?: boolean
    type?: 'button' | 'submit'
    to?: RouteLocationRaw
  }>(),
  { variant: 'primary', icon: undefined, loading: false, disabled: false, block: false, type: 'button', to: undefined },
)

const classes = computed(() => ['app-button', `app-button--${props.variant}`, { 'app-button--block': props.block }])
</script>

<template>
  <RouterLink v-if="to" :class="classes" :to="to">
    <AppIcon v-if="icon" :name="icon" size="sm" />
    <slot />
  </RouterLink>
  <button v-else :class="classes" :type="type" :disabled="disabled || loading" :aria-busy="loading">
    <span v-if="loading" class="app-button__spinner" aria-hidden="true" />
    <AppIcon v-else-if="icon" :name="icon" size="sm" />
    <slot />
  </button>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $space-2;
  min-height: $touch-target;
  padding: 0 $space-4;
  border: 1px solid transparent;
  border-radius: $radius-control;
  font: inherit;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  cursor: pointer;
  text-decoration: none;
  transition: filter $duration-fast;

  &:hover:not(:disabled) {
    filter: brightness(0.94);
  }

  &:disabled {
    cursor: not-allowed;
    opacity: 0.6;
  }
}

.app-button--primary {
  background: $color-primary;
  color: $color-on-dark;
}

.app-button--secondary {
  background: $color-surface;
  border-color: $color-border-strong;
  color: $color-text;
}

.app-button--danger {
  background: $color-danger;
  color: $color-on-dark;
}

.app-button--link {
  min-height: auto;
  padding: $space-1 $space-2;
  background: transparent;
  color: $color-primary;
}

.app-button--block {
  width: 100%;
}

.app-button__spinner {
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid currentcolor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: button-spin 0.7s linear infinite;
}

@keyframes button-spin {
  to {
    transform: rotate(360deg);
  }
}

// Con mouse el botón puede ser más compacto que el mínimo táctil
@include laptop {
  .app-button {
    min-height: $control-height-compact;
  }
}
</style>
