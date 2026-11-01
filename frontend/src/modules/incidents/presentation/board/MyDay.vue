<!-- Vista "Mi día" de "Mis incidentes": la fecha de hoy y cinco bloques (vencidos, vencen hoy, en progreso, en espera y
     resueltos ayer), cada uno con su número y desplazamiento interno. Celular y tablet: uno debajo del otro, con tope de alto;
     portátil y monitor: los cinco en una fila que ocupa el alto de la pantalla. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { daySections } from '../../domain/myDay'
import type { Incident } from '../../domain/incident'
import IncidentRow from './IncidentRow.vue'

const props = defineProps<{ items: Incident[] }>()

const now = new Date()
const today = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(now)
const sections = computed(() => daySections(props.items, now))
</script>

<template>
  <div class="my-day">
    <p class="my-day__date">Hoy · {{ today }}</p>
    <div class="my-day__sections">
      <section
        v-for="section in sections"
        :key="section.key"
        class="my-day__section"
        :aria-labelledby="`dia-${section.key}`"
      >
        <h2 :id="`dia-${section.key}`" class="my-day__heading">
          {{ section.label }}
          <span class="my-day__count">{{ section.items.length }}</span>
        </h2>
        <ul v-if="section.items.length > 0" class="my-day__rows">
          <li v-for="item in section.items" :key="item.id"><IncidentRow :item="item" /></li>
        </ul>
        <p v-else class="my-day__empty">{{ section.empty }}</p>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.my-day {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.my-day__date {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;

  &::first-letter {
    text-transform: uppercase;
  }
}

// minmax(0, 1fr): un título largo se corta con "…" en vez de ensanchar el bloque más allá de la pantalla
.my-day__sections {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: $space-4;
}

// Celular y tablet: cada bloque con tope de alto; sus incidentes se desplazan por dentro y el nombre queda a la vista
.my-day__section {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  max-height: $day-section-max-height;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  min-width: 0;
}

.my-day__heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.my-day__count {
  color: $color-text-secondary;
  font-size: $font-size-sm;
  font-weight: $font-weight-regular;
}

.my-day__rows {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  min-height: 0;
  margin: 0;
  margin-right: -$space-2;
  padding: 0;
  padding-right: $space-2;
  overflow-y: auto;
  list-style: none;
  scrollbar-width: thin;
}

.my-day__empty {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// Portátil y monitor: los cinco bloques en una fila, como el Kanban. Cada uno mide lo que su contenido y llega como
// máximo al final de la pantalla; si no caben de ancho, la fila se desplaza de lado
@include laptop {
  .my-day {
    flex: 1;
    min-height: 0;
  }

  .my-day__sections {
    display: flex;
    flex: 1;
    min-height: 0;
    align-items: flex-start;
    overflow-x: auto;
    padding-bottom: $space-2;
  }

  .my-day__section {
    flex: 1 0 $board-column-width;
    max-height: 100%;
  }
}
</style>
