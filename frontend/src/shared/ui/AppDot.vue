<!-- Punto de color con texto (p. ej. la prioridad). Se distingue a propósito de la etiqueta sólida del estado. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import type { Tone } from './tones'

defineProps<{ tone: Tone }>()
</script>

<template>
  <span class="app-dot">
    <span class="app-dot__mark" :class="`app-dot__mark--${tone}`" aria-hidden="true" />
    <slot />
  </span>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-dot {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
  font-size: $font-size-sm;
  white-space: nowrap;
}

.app-dot__mark {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
}

// El punto usa el color base del tono, el mismo de los gráficos
@each $name, $fill in $chart-fills {
  .app-dot__mark--#{$name} {
    background: $fill;
  }
}
</style>
