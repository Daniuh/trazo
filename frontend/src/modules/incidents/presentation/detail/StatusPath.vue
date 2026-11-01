<!-- Camino del incidente: los estados del flujo en orden, con los anteriores hechos y el actual resaltado. Si salió del
     camino (Cancelado), lo dice al final. En celular va en columna; desde tablet, en fila.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import type { StatusPath } from '../../domain/workflow'

defineProps<{ path: StatusPath }>()

const STATE_TEXT = { done: 'hecho', current: 'actual', todo: 'pendiente' } as const
</script>

<template>
  <div class="status-path">
    <ol class="status-path__steps" aria-label="Camino del incidente">
      <li
        v-for="step in path.steps"
        :key="step.code"
        class="status-path__step"
        :class="`status-path__step--${step.state}`"
        :aria-current="step.state === 'current' ? 'step' : undefined"
      >
        <span class="status-path__node" aria-hidden="true" />
        <span class="status-path__name">{{ step.name }}</span>
        <span class="status-path__hidden">, {{ STATE_TEXT[step.state] }}</span>
      </li>
    </ol>
    <p v-if="path.exit" class="status-path__exit">Salió del camino: {{ path.exit.name }}</p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Relativa: los textos ocultos de cada paso quedan dentro
.status-path {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

.status-path__steps {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  margin: 0;
  padding: 0;
  list-style: none;
}

.status-path__step {
  position: relative;
  display: flex;
  align-items: center;
  gap: $space-2;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// La línea que une cada paso con el siguiente
.status-path__step:not(:last-child)::after {
  position: absolute;
  top: calc(50% + #{$space-2});
  left: calc(#{$status-node-size} / 2 - 1px);
  width: 2px;
  height: $space-2;
  background: $color-border-strong;
  content: '';
}

.status-path__step--done:not(:last-child)::after {
  background: $color-primary;
}

.status-path__node {
  flex: none;
  width: $status-node-size;
  height: $status-node-size;
  border: 2px solid $color-border-strong;
  border-radius: 50%;
  background: $color-surface;
}

.status-path__step--done .status-path__node {
  border-color: $color-primary;
  background: $color-primary;
}

.status-path__step--current {
  color: $color-text;
  font-weight: $font-weight-semibold;

  .status-path__node {
    border-width: 4px;
    border-color: $color-primary;
  }
}

.status-path__exit {
  margin: 0;
  color: $color-text;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.status-path__hidden {
  @include visually-hidden;
}

// Desde tablet, en una fila con la línea entre pasos
@include tablet {
  .status-path__steps {
    flex-flow: row wrap;
    gap: $space-2 $space-6;
  }

  .status-path__step:not(:last-child)::after {
    top: 50%;
    left: calc(100% + #{$space-1});
    width: $space-4;
    height: 2px;
  }
}
</style>
