<!-- Lista de opciones de una sola elección, con nombre en negrita y una línea de descripción debajo (p. ej. el rol de una
     persona). Cada fila ocupa todo el ancho y se elige tocándola entera; la opción elegida lleva borde azul.
     Envuelve el QRadio de Quasar. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QRadio } from 'quasar'
import { useId } from 'vue'
import type { RadioOption } from './types'

defineProps<{ label: string; options: RadioOption[]; error?: string }>()
const model = defineModel<string | null>({ default: null })

const id = useId()
</script>

<template>
  <fieldset class="app-radio-list" :aria-describedby="error ? `${id}-error` : undefined">
    <legend class="app-radio-list__label">{{ label }}</legend>
    <div class="app-radio-list__options">
      <QRadio
        v-for="option in options"
        :key="option.value"
        v-model="model"
        class="app-radio-list__option"
        :class="{ 'app-radio-list__option--selected': model === option.value }"
        :val="option.value"
        :disable="option.disabled"
        color="primary"
        dense
      >
        <span class="app-radio-list__name">{{ option.label }}</span>
        <span v-if="option.description" class="app-radio-list__description">{{ option.description }}</span>
      </QRadio>
    </div>
    <p v-if="error" :id="`${id}-error`" class="app-radio-list__error" role="alert">{{ error }}</p>
  </fieldset>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-radio-list {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  margin: 0;
  padding: 0;
  border: 0;
}

.app-radio-list__label {
  margin-bottom: $space-2;
  padding: 0;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.app-radio-list__options {
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

// Toda la fila es el control: alto táctil y borde que marca la elegida
.app-radio-list__option {
  display: flex;
  align-items: flex-start;
  gap: $space-3;
  min-height: $touch-target;
  padding: $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-control;
  background: $color-surface;

  &:hover {
    border-color: $color-border-strong;
  }

  :deep(.q-radio__label) {
    display: flex;
    flex-direction: column;
    gap: $space-1;
  }
}

.app-radio-list__option--selected {
  border-color: $color-primary;
  box-shadow: inset 0 0 0 1px $color-primary;
}

.app-radio-list__name {
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
}

.app-radio-list__description {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.app-radio-list__error {
  margin: 0;
  color: $color-danger;
  font-size: $font-size-xs;
}
</style>
