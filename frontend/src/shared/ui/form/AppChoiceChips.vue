<!-- Opciones en forma de chips que se activan y desactivan (selección múltiple). Pensado para filtros en pantallas táctiles.
     Activo: fondo azul y texto blanco. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import type { SelectOption } from './types'

defineProps<{ label: string; options: SelectOption<string>[] }>()
const model = defineModel<string[]>({ default: () => [] })

function toggle(value: string): void {
  model.value = model.value.includes(value) ? model.value.filter((item) => item !== value) : [...model.value, value]
}
</script>

<template>
  <fieldset class="app-chips">
    <legend class="app-chips__label">{{ label }}</legend>
    <div class="app-chips__list">
      <button
        v-for="option in options"
        :key="option.value"
        class="app-chips__chip"
        :class="{ 'app-chips__chip--active': model.includes(option.value) }"
        type="button"
        :aria-pressed="model.includes(option.value)"
        @click="toggle(option.value)"
      >
        {{ option.label }}
      </button>
    </div>
  </fieldset>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-chips {
  margin: 0;
  padding: 0;
  border: 0;
}

.app-chips__label {
  margin-bottom: $space-2;
  padding: 0;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.app-chips__list {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}

.app-chips__chip {
  min-height: $touch-target;
  padding: 0 $space-3;
  border: 1px solid $color-border-strong;
  border-radius: $radius-pill;
  background: $color-surface;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  cursor: pointer;
}

.app-chips__chip--active {
  border-color: $color-primary;
  background: $color-primary;
  color: $color-on-dark;
}
</style>
