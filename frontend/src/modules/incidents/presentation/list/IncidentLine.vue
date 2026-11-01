<!-- Un incidente en la lista agrupada, en dos líneas: código, título y prioridad; estado, asignado y avance del plazo.
     En celular la prioridad queda como punto y el asignado como sus iniciales (los nombres siguen para lectores de
     pantalla). Elegirlo emite "open". Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import AppAvatar from '@/shared/ui/AppAvatar.vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import { useViewport } from '@/shared/ui/useViewport'
import type { IncidentSummary } from '../../domain/listing'
import { priorityTone, statusTone } from '../incidentTones'
import DeadlineMeter from './DeadlineMeter.vue'

defineProps<{ incident: IncidentSummary; selected: boolean; deleted: boolean }>()
const emit = defineEmits<{ open: [id: string]; restore: [id: string] }>()

const { isMobile } = useViewport()
</script>

<template>
  <article
    class="incident-line"
    :class="{ 'incident-line--selected': selected }"
    tabindex="0"
    :aria-label="`${incident.code}: ${incident.title}`"
    :aria-current="selected ? 'true' : undefined"
    @click="emit('open', incident.id)"
    @keydown.enter="emit('open', incident.id)"
  >
    <div class="incident-line__top">
      <span class="incident-line__code">{{ incident.code }}</span>
      <span class="incident-line__title">{{ incident.title }}</span>
      <AppDot class="incident-line__priority" :tone="priorityTone(incident.priority.code)">
        <span :class="{ 'incident-line__hidden': isMobile }">{{ incident.priority.name }}</span>
      </AppDot>
    </div>

    <div class="incident-line__meta">
      <AppBadge :tone="statusTone(incident.status.code)">{{ incident.status.name }}</AppBadge>
      <span v-if="incident.reopenRequested" class="incident-line__reopen">Reapertura pendiente</span>
      <span class="incident-line__assignee">
        <template v-if="incident.assignee">
          <AppAvatar :name="incident.assignee.name" />
          <span :class="{ 'incident-line__hidden': isMobile }">{{ incident.assignee.name }}</span>
        </template>
        <template v-else>Sin asignar</template>
      </span>
      <DeadlineMeter class="incident-line__deadline" :incident="incident" />
    </div>

    <AppButton
      v-if="deleted && incident.actions.restore"
      class="incident-line__restore"
      variant="secondary"
      icon="refresh"
      @click.stop="emit('restore', incident.id)"
    >
      Restaurar
    </AppButton>
  </article>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

// Relativa para que los textos ocultos queden dentro de la fila
.incident-line {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-3;
  background: $color-surface;
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

// Elegida (vista previa en monitor): una franja azul a la izquierda, sin cambiar el texto
.incident-line--selected {
  background: $color-page;
  box-shadow: inset $space-1 0 0 $color-primary;
}

.incident-line__top {
  display: flex;
  align-items: baseline;
  gap: $space-2;
  min-width: 0;
}

.incident-line__code {
  flex: none;
  color: $color-text-secondary;
  font-size: $font-size-xs;
  font-variant-numeric: tabular-nums;
  font-weight: $font-weight-medium;
}

.incident-line__title {
  flex: 1;
  overflow: hidden;
  min-width: 0;
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.incident-line__priority {
  flex: none;
}

.incident-line__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2 $space-3;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.incident-line__reopen {
  color: $color-text;
  font-weight: $font-weight-medium;
}

.incident-line__assignee {
  display: inline-flex;
  align-items: center;
  gap: $space-2;
  min-width: 0;
}

// El plazo se va a la derecha y, si no cabe, baja a su propia línea
.incident-line__deadline {
  margin-left: auto;
}

.incident-line__restore {
  align-self: flex-start;
}

.incident-line__hidden {
  @include visually-hidden;
}

@include tablet {
  .incident-line {
    padding: $space-3 $space-4;
  }
}
</style>
