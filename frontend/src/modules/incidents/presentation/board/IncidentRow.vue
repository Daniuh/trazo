<!-- Fila compacta de un incidente para la agenda del calendario, el detalle de un día y "Mi día": código, título (lleva al
     detalle), estado, prioridad y vencimiento. Con "time-only", el vencimiento muestra solo la hora (el día ya se sabe).
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatShortDateTime } from '@/shared/format/dates'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import type { Incident } from '../../domain/incident'
import { OVERDUE_TONE, priorityTone, statusTone } from '../incidentTones'

const props = defineProps<{ item: Incident; timeOnly?: boolean }>()

const time = new Intl.DateTimeFormat('es-CO', { hour: 'numeric', minute: '2-digit' })
const due = computed(() =>
  props.timeOnly ? time.format(new Date(props.item.dueDate)) : formatShortDateTime(props.item.dueDate),
)
</script>

<template>
  <article class="incident-row">
    <div class="incident-row__main">
      <span class="incident-row__code">{{ item.code }}</span>
      <RouterLink class="incident-row__title" :to="{ name: 'incident-detail', params: { id: item.id } }">
        {{ item.title }}
      </RouterLink>
    </div>
    <div class="incident-row__meta">
      <AppBadge :tone="statusTone(item.status.code)">{{ item.status.name }}</AppBadge>
      <AppBadge v-if="item.isOverdue" :tone="OVERDUE_TONE">Vencido</AppBadge>
      <AppDot :tone="priorityTone(item.priority.code)">{{ item.priority.name }}</AppDot>
      <span class="incident-row__due">
        <span class="incident-row__hidden">Vence</span>
        <time :datetime="item.dueDate">{{ due }}</time>
      </span>
    </div>
  </article>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Relativa para que el texto oculto quede dentro de la fila (puede ir en una zona con desplazamiento)
.incident-row {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-3;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.incident-row__main {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  min-width: 0;
}

.incident-row__code {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.incident-row__title {
  overflow: hidden;
  color: $color-text;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  text-decoration: none;
  text-overflow: ellipsis;
  white-space: nowrap;

  &:hover {
    text-decoration: underline;
  }
}

.incident-row__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
}

.incident-row__due {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.incident-row__hidden {
  @include visually-hidden;
}
</style>
