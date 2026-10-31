<!-- Selector de una opción entre pocas, en forma de botones unidos (p. ej. "7 días · 30 días · 90 días").
     Activo: fondo azul y texto blanco. Se usa como grupo de opciones (radiogroup). Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts" generic="T extends string | number">
import type { SelectOption } from './types'

defineProps<{ label: string; options: SelectOption<T>[] }>()
const model = defineModel<T>({ required: true })
</script>

<template>
  <div class="app-segmented" role="radiogroup" :aria-label="label">
    <button
      v-for="option in options"
      :key="option.value"
      class="app-segmented__option"
      :class="{ 'app-segmented__option--active': model === option.value }"
      type="button"
      role="radio"
      :aria-checked="model === option.value"
      @click="model = option.value"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-segmented {
  display: inline-flex;
  padding: 2px;
  border: 1px solid $color-border-strong;
  border-radius: $radius-control;
  background: $color-surface;
}

.app-segmented__option {
  min-height: $touch-target;
  padding: 0 $space-3;
  border: 0;
  border-radius: calc($radius-control - 2px);
  background: none;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  cursor: pointer;

  &:hover:not(.app-segmented__option--active) {
    background: $color-page;
  }
}

.app-segmented__option--active {
  background: $color-primary;
  color: $color-on-dark;
  font-weight: $font-weight-medium;
}

@include laptop {
  .app-segmented__option {
    min-height: 2rem;
  }
}
</style>
