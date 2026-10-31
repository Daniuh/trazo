<!-- Etiqueta sólida (p. ej. el estado de un incidente). El texto es negro o blanco según el contraste del tono. -->
<!-- Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import type { Tone } from './tones'

defineProps<{ tone: Tone }>()
</script>

<template>
  <span class="app-badge" :class="`app-badge--${tone}`"><slot /></span>
</template>

<style scoped lang="scss">
@use 'sass:list';
@use '@/design/tokens' as *;

.app-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.125rem $space-3;
  border-radius: $radius-pill;
  font-size: $font-size-xs;
  font-weight: $font-weight-medium;
  line-height: $line-height-base;
  white-space: nowrap;
}

@each $name, $pair in $tones {
  .app-badge--#{$name} {
    background: list.nth($pair, 1);
    color: list.nth($pair, 2);
  }
}
</style>
