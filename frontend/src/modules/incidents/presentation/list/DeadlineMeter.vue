<!-- Avance del plazo de un incidente: barra (solo pendientes) y texto ("Quedan 5 h", "Venció hace 3 h", "Resuelto a tiempo").
     La barra es decorativa: el texto dice lo mismo. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { deadlineView } from '../../domain/deadline'
import type { Incident } from '../../domain/incident'

const props = defineProps<{ incident: Pick<Incident, 'deadline' | 'dueDate' | 'resolvedAt'> }>()

const view = computed(() => deadlineView(props.incident.deadline, props.incident.dueDate, props.incident.resolvedAt))
</script>

<template>
  <span
    v-if="view.text"
    class="deadline-meter"
    :class="[view.tone && `deadline-meter--${view.tone}`, { 'deadline-meter--finished': view.percent === null }]"
  >
    <span v-if="view.percent !== null" class="deadline-meter__track" aria-hidden="true">
      <span class="deadline-meter__fill" :style="{ width: `${view.percent}%` }" />
    </span>
    <span class="deadline-meter__text">{{ view.text }}</span>
  </span>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.deadline-meter {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  white-space: nowrap;
}

.deadline-meter__track {
  overflow: hidden;
  width: $deadline-meter-width;
  height: $deadline-meter-height;
  border-radius: $radius-pill;
  background: $color-border;
}

.deadline-meter__fill {
  display: block;
  height: 100%;
  border-radius: $radius-pill;
}

@each $tone, $fill in $deadline-fills {
  .deadline-meter--#{$tone} .deadline-meter__fill {
    background: $fill;
  }
}

// Vencido o resuelto tarde: el texto en rojo; resuelto a tiempo, en verde oscuro (ambos con contraste AA)
.deadline-meter--danger .deadline-meter__text {
  color: $color-danger;
  font-weight: $font-weight-medium;
}

.deadline-meter--finished.deadline-meter--ok .deadline-meter__text {
  color: $color-success;
  font-weight: $font-weight-medium;
}

// Tablet en adelante: el texto con ancho fijo deja todas las barras alineadas en columna
@include tablet {
  .deadline-meter__text {
    min-width: $deadline-text-width;
  }
}
</style>
