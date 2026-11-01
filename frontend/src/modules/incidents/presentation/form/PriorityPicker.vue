<!-- Prioridad en tarjetas: cada una con su color, su tiempo de resolución (del catálogo) y cuándo usarla. Son radios
     reales: se eligen con clic o con las flechas. Celular: de a dos; desde tablet, las cuatro en fila.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { useId } from 'vue'
import type { PriorityOption } from '../../domain/incident'
import { formatResolution, PRIORITY_HINTS } from '../../domain/reporting'
import { priorityTone } from '../incidentTones'

defineProps<{ options: PriorityOption[]; error?: string }>()
const model = defineModel<string>({ required: true })
const name = useId()
</script>

<template>
  <fieldset class="priority-picker" :aria-describedby="error ? `${name}-error` : undefined">
    <legend class="priority-picker__legend">
      Prioridad<span class="priority-picker__required" aria-hidden="true">*</span>
    </legend>
    <div class="priority-picker__options">
      <label
        v-for="option in options"
        :key="option.code"
        class="priority-picker__option"
        :class="{ 'priority-picker__option--active': model === option.code }"
      >
        <input v-model="model" class="priority-picker__input" type="radio" :name="name" :value="option.code" />
        <span class="priority-picker__name">
          <span class="priority-picker__dot" :class="`priority-picker__dot--${priorityTone(option.code)}`" />
          {{ option.name }}
        </span>
        <span class="priority-picker__time">
          <strong>{{ formatResolution(option.resolutionMinutes) }}</strong> para resolver
        </span>
        <span v-if="PRIORITY_HINTS[option.code]" class="priority-picker__hint">{{ PRIORITY_HINTS[option.code] }}</span>
      </label>
    </div>
    <p v-if="error" :id="`${name}-error`" class="priority-picker__error" role="alert">{{ error }}</p>
  </fieldset>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.priority-picker {
  margin: 0;
  padding: 0;
  border: 0;
}

.priority-picker__legend {
  margin-bottom: $space-1;
  padding: 0;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.priority-picker__required {
  margin-left: 0.125rem;
  color: $color-danger;
}

.priority-picker__options {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: $space-2;
}

// Relativa: el radio real queda encima de la tarjeta, invisible pero con su foco
.priority-picker__option {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: $space-1;
  padding: $space-3;
  border: 1px solid $color-border-strong;
  border-radius: $radius-card;
  background: $color-surface;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }

  &:has(.priority-picker__input:focus-visible) {
    outline: 2px solid $color-focus;
    outline-offset: 2px;
  }
}

// Elegida: borde azul más grueso (sin cambiar el texto de color)
.priority-picker__option--active {
  border-color: $color-primary;
  background: $color-page;
  box-shadow: inset 0 0 0 1px $color-primary;
}

.priority-picker__input {
  position: absolute;
  inset: 0;
  margin: 0;
  opacity: 0;
  cursor: pointer;
}

.priority-picker__name {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
  font-weight: $font-weight-semibold;
}

.priority-picker__dot {
  width: $space-2;
  height: $space-2;
  border-radius: 50%;
}

@each $name, $fill in $chart-fills {
  .priority-picker__dot--#{$name} {
    background: $fill;
  }
}

.priority-picker__time {
  font-size: $font-size-sm;

  strong {
    font-weight: $font-weight-semibold;
  }
}

.priority-picker__hint {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.priority-picker__error {
  margin: $space-1 0 0;
  color: $color-danger;
  font-size: $font-size-xs;
}

@include tablet {
  .priority-picker__options {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
</style>
