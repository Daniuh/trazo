<!-- Kanban de "Mis incidentes": una columna por estado. Portátil y monitor: todas las columnas lado a lado (se desplaza
     de lado si no caben) y las tarjetas se arrastran; al tomar una, las columnas a las que puede ir se marcan y las demás
     se apagan. Celular y tablet: una columna a la vez, elegida en pestañas con su número, y "Mover a…" en cada tarjeta.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { BoardColumn } from '../../domain/board'
import { canMoveTo } from '../../domain/board'
import type { Incident } from '../../domain/incident'
import KanbanCard from './KanbanCard.vue'

const props = defineProps<{ columns: BoardColumn[]; movingId: string | null; dragEnabled: boolean }>()
const emit = defineEmits<{ move: [item: Incident, code: string] }>()

// Arrastre: qué tarjeta va en camino y sobre qué columna está
const dragging = ref<Incident | null>(null)
const over = ref<string | null>(null)

function columnState(code: string): 'source' | 'target' | 'blocked' | null {
  if (!dragging.value) return null
  if (dragging.value.status.code === code) return 'source'
  return canMoveTo(dragging.value, code) ? 'target' : 'blocked'
}

function allowDrop(event: DragEvent, code: string): void {
  if (columnState(code) !== 'target') return
  event.preventDefault()
  over.value = code
}

function drop(code: string): void {
  const item = dragging.value
  dragging.value = null
  over.value = null
  if (item && canMoveTo(item, code)) emit('move', item, code)
}

function endDrag(): void {
  dragging.value = null
  over.value = null
}

// Celular y tablet: la columna que se ve. Empieza en la primera con tarjetas
const selected = ref<string | null>(null)
watch(
  () => props.columns,
  (columns) => {
    if (selected.value && columns.some((column) => column.status.code === selected.value)) return
    selected.value = (columns.find((column) => column.items.length > 0) ?? columns[0])?.status.code ?? null
  },
  { immediate: true },
)
const visible = computed(() =>
  props.dragEnabled ? props.columns : props.columns.filter((column) => column.status.code === selected.value),
)
</script>

<template>
  <div class="kanban" :class="{ 'kanban--tabs': !dragEnabled }">
    <div v-if="!dragEnabled" class="kanban__tabs" role="tablist" aria-label="Estado">
      <button
        v-for="column in columns"
        :key="column.status.code"
        class="kanban__tab"
        :class="{ 'kanban__tab--active': selected === column.status.code }"
        type="button"
        role="tab"
        :aria-selected="selected === column.status.code"
        @click="selected = column.status.code"
      >
        {{ column.status.name }} ({{ column.items.length }})
      </button>
    </div>

    <div class="kanban__columns">
      <section
        v-for="column in visible"
        :key="column.status.code"
        class="kanban__column"
        :class="[
          columnState(column.status.code) && `kanban__column--${columnState(column.status.code)}`,
          { 'kanban__column--over': over === column.status.code },
        ]"
        :aria-labelledby="`kanban-${column.status.code}`"
        @dragover="allowDrop($event, column.status.code)"
        @dragleave="over = over === column.status.code ? null : over"
        @drop.prevent="drop(column.status.code)"
      >
        <h2 :id="`kanban-${column.status.code}`" class="kanban__heading">
          {{ column.status.name }}
          <span class="kanban__count">{{ column.items.length }}</span>
        </h2>
        <ul v-if="column.items.length > 0" class="kanban__cards">
          <li v-for="item in column.items" :key="item.id">
            <KanbanCard
              :item="item"
              :draggable="dragEnabled"
              :busy="movingId === item.id"
              @move="emit('move', item, $event)"
              @dragstart="dragging = item"
              @dragend="endDrag"
            />
          </li>
        </ul>
        <p v-else class="kanban__empty">Sin incidentes</p>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.kanban {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

// Pestañas de estado (celular y tablet): una fila que se desplaza de lado si no caben
.kanban__tabs {
  display: flex;
  gap: $space-2;
  overflow-x: auto;
  padding-bottom: $space-1;
  scrollbar-width: thin;
}

// Con pestañas, el nombre de la columna ya está en la pestaña elegida: queda solo para lectores de pantalla
.kanban--tabs .kanban__heading {
  @include visually-hidden;
}

.kanban__tab {
  flex: none;
  min-height: $touch-target;
  padding: 0 $space-3;
  border: 1px solid $color-border-strong;
  border-radius: $radius-control;
  background: $color-surface;
  color: $color-text;
  font: inherit;
  font-size: $font-size-sm;
  white-space: nowrap;
  cursor: pointer;
}

.kanban__tab--active {
  border-color: $color-primary;
  background: $color-primary;
  color: $color-on-dark;
  font-weight: $font-weight-medium;
}

.kanban__columns {
  display: flex;
  gap: $space-3;
}

.kanban__column {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: $space-3;
  min-width: 0;
  padding: $space-3;

  // Carril con borde suave sobre el fondo de la página; al arrastrar, el borde marca a dónde puede ir
  border: 1px solid $color-border;
  border-radius: $radius-card;
  transition: opacity $duration-fast;
}

.kanban__heading {
  display: flex;
  line-height: $line-height-tight;
  align-items: center;
  justify-content: space-between;
  margin: 0;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
}

.kanban__count {
  color: $color-text-secondary;
  font-weight: $font-weight-regular;
}

.kanban__cards {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  margin: 0;
  padding: 0;
  list-style: none;
}

.kanban__empty {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// Mientras se arrastra: a dónde puede ir (borde azul punteado), dónde está encima (fondo) y a dónde no (apagada)
.kanban__column--target {
  border-style: dashed;
  border-color: $color-primary;
}

.kanban__column--over {
  background: $color-surface;
}

.kanban__column--blocked {
  opacity: 0.45;
}

// Portátil y monitor: todas las columnas lado a lado, cada una con un ancho mínimo; si no caben, se desplaza de lado
@include laptop {
  // Cada columna mide lo que su contenido: una vacía o con pocas tarjetas no se estira al alto de la más llena
  .kanban {
    flex: 1;
    min-height: 0;
  }

  .kanban__columns {
    flex: 1;
    min-height: 0;
    align-items: flex-start;
    overflow-x: auto;
    padding-bottom: $space-2;
  }

  // Tope de alto: cada columna llega como máximo al final del tablero (que termina con la pantalla) y sus tarjetas se desplazan por dentro, con el
  // nombre y el número siempre a la vista
  .kanban__column {
    flex: 1 0 $board-column-width;
    max-height: 100%;
  }

  .kanban__cards {
    overflow-y: auto;
    margin-right: -$space-2;
    padding-right: $space-2;
    scrollbar-width: thin;
  }
}
</style>
