<!-- Tarjeta del Kanban: código, título, prioridad, "Vencido" si aplica y vencimiento. Tocarla abre el detalle. En portátil
     y monitor se arrastra a otra columna; en todas las vistas, "Mover a…" lleva a los estados permitidos (también con
     teclado). Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatShortDateTime } from '@/shared/format/dates'
import AppActionMenu from '@/shared/ui/AppActionMenu.vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { MenuItem } from '@/shared/ui/menu'
import type { Incident } from '../../domain/incident'
import { OVERDUE_TONE, priorityTone } from '../incidentTones'

const props = defineProps<{ item: Incident; draggable: boolean; busy: boolean }>()
const emit = defineEmits<{ move: [code: string]; dragstart: []; dragend: [] }>()

const targets = computed<MenuItem[]>(() =>
  props.item.nextStatuses.map((status) => ({
    key: status.code,
    label: status.name,
    danger: status.code === 'cancelled',
  })),
)

function start(event: DragEvent): void {
  // Solo el id: lo demás lo sabe el tablero
  event.dataTransfer?.setData('text/plain', props.item.id)
  if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'
  emit('dragstart')
}
</script>

<template>
  <article
    class="kanban-card"
    :class="{ 'kanban-card--busy': busy, 'kanban-card--draggable': draggable }"
    :draggable="draggable && targets.length > 0"
    :aria-busy="busy"
    @dragstart="start"
    @dragend="emit('dragend')"
  >
    <div class="kanban-card__top">
      <span class="kanban-card__code">{{ item.code }}</span>
      <button
        v-if="targets.length > 0"
        class="kanban-card__menu"
        type="button"
        :aria-label="`Mover ${item.code} a otro estado`"
        :disabled="busy"
      >
        <AppIcon name="more" size="sm" />
        <AppActionMenu :items="targets" label="Mover a" align="right" @select="emit('move', $event)" />
      </button>
    </div>
    <RouterLink class="kanban-card__title" :to="{ name: 'incident-detail', params: { id: item.id } }" draggable="false">
      {{ item.title }}
    </RouterLink>
    <div class="kanban-card__badges">
      <AppDot :tone="priorityTone(item.priority.code)">{{ item.priority.name }}</AppDot>
      <AppBadge v-if="item.isOverdue" :tone="OVERDUE_TONE">Vencido</AppBadge>
    </div>
    <p class="kanban-card__due">
      <AppIcon name="calendar" size="sm" />
      <span class="kanban-card__hidden">Vence</span>
      <time :datetime="item.dueDate">{{ formatShortDateTime(item.dueDate) }}</time>
    </p>
  </article>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Relativa para que el texto oculto ("Vence") quede dentro de la tarjeta: si no, se escapa del desplazamiento de la
// columna y alarga la página
.kanban-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.kanban-card--draggable[draggable='true'] {
  cursor: grab;
}

.kanban-card--busy {
  opacity: 0.6;
}

.kanban-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
}

.kanban-card__code {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.kanban-card__menu {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: $touch-target;
  height: $touch-target;
  margin: (-$space-2) (-$space-2) (-$space-2) 0;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text-secondary;
  cursor: pointer;

  &:hover {
    background: $color-page;
    color: $color-text;
  }
}

// El título lleva al detalle; dos líneas como máximo para que las tarjetas tengan un alto parejo
.kanban-card__title {
  display: -webkit-box;
  overflow: hidden;
  color: $color-text;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  text-decoration: none;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;

  &:hover {
    text-decoration: underline;
  }
}

.kanban-card__hidden {
  @include visually-hidden;
}

.kanban-card__badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
}

.kanban-card__due {
  display: flex;
  align-items: center;
  gap: $space-1;
  margin: 0;
  white-space: nowrap;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

@include laptop {
  .kanban-card__menu {
    width: $control-height-compact;
    height: $control-height-compact;
    margin: (-$space-1) (-$space-1) (-$space-1) 0;
  }
}
</style>
