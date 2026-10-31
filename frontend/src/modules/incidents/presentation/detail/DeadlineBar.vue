<!-- Plazo del incidente en grande: el texto ("Venció hace 2 días", "Quedan 5 h", "Resuelto a tiempo"), la fecha exacta y
     la barra a lo ancho (solo en los pendientes). El cálculo es el mismo de la lista (deadlineView).
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatDateTime } from '@/shared/format/dates'
import { deadlineView } from '../../domain/deadline'
import type { Incident } from '../../domain/incident'

const props = defineProps<{ incident: Pick<Incident, 'deadline' | 'dueDate' | 'resolvedAt'> }>()

const view = computed(() => deadlineView(props.incident.deadline, props.incident.dueDate, props.incident.resolvedAt))
</script>

<template>
  <div class="deadline-bar" :class="view.tone && `deadline-bar--${view.tone}`">
    <p class="deadline-bar__line">
      <strong class="deadline-bar__text">{{ view.text || 'Terminado' }}</strong>
      <span class="deadline-bar__date">Vence el {{ formatDateTime(incident.dueDate) }}</span>
    </p>
    <span v-if="view.percent !== null" class="deadline-bar__track" aria-hidden="true">
      <span class="deadline-bar__fill" :style="{ width: `${view.percent}%` }" />
    </span>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.deadline-bar {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  min-width: 0;
}

.deadline-bar__line {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: $space-1 $space-3;
  margin: 0;
  font-size: $font-size-sm;
}

.deadline-bar__text {
  font-weight: $font-weight-semibold;
}

.deadline-bar__date {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.deadline-bar__track {
  overflow: hidden;
  height: $deadline-bar-height;
  border-radius: $radius-pill;
  background: $color-border;
}

.deadline-bar__fill {
  display: block;
  height: 100%;
  border-radius: $radius-pill;
}

@each $tone, $fill in $deadline-fills {
  .deadline-bar--#{$tone} .deadline-bar__fill {
    background: $fill;
  }
}

// El texto con color de aviso solo cuando es grave (rojo, contraste AA)
.deadline-bar--danger .deadline-bar__text {
  color: $color-danger;
}
</style>
