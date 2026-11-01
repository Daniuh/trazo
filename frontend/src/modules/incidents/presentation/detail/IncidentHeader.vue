<!-- Encabezado del detalle: código, estado, "Vencido" y prioridad; el título; el camino del incidente; y abajo el plazo
     con el siguiente paso. Celular: todo en columna; desde portátil, el plazo y las acciones en una fila.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import type { Incident, StatusOption } from '../../domain/incident'
import { statusPath } from '../../domain/workflow'
import { OVERDUE_TONE, priorityTone, statusTone } from '../incidentTones'
import DeadlineBar from './DeadlineBar.vue'
import DetailActions from './DetailActions.vue'
import IncidentDescription from './IncidentDescription.vue'
import StatusPath from './StatusPath.vue'

const props = defineProps<{ incident: Incident; catalog: StatusOption[]; busy?: boolean }>()
const emit = defineEmits<{ status: [code: string]; reopen: []; 'request-reopen': [] }>()

const path = computed(() => statusPath(props.catalog, props.incident.status.code))
</script>

<template>
  <AppCard class="incident-header">
    <div class="incident-header__meta">
      <span class="incident-header__code">{{ incident.code }}</span>
      <AppBadge :tone="statusTone(incident.status.code)">{{ incident.status.name }}</AppBadge>
      <AppBadge v-if="incident.isOverdue" :tone="OVERDUE_TONE">Vencido</AppBadge>
      <AppDot :tone="priorityTone(incident.priority.code)">{{ incident.priority.name }}</AppDot>
    </div>
    <div class="incident-header__heading">
      <h1 class="incident-header__title">{{ incident.title }}</h1>
      <IncidentDescription :text="incident.description" />
    </div>
    <StatusPath v-if="path.steps.length > 0" :path="path" />
    <div class="incident-header__footer">
      <DeadlineBar class="incident-header__deadline" :incident="incident" />
      <DetailActions
        :incident="incident"
        :catalog="catalog"
        :busy="busy"
        @status="emit('status', $event)"
        @reopen="emit('reopen')"
        @request-reopen="emit('request-reopen')"
      />
    </div>
  </AppCard>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-header {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.incident-header__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
}

.incident-header__code {
  color: $color-text-secondary;
  font-size: $font-size-sm;
  font-variant-numeric: tabular-nums;
  font-weight: $font-weight-medium;
}

// Título y descripción juntos, con menos aire que el resto del encabezado
.incident-header__heading {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  min-width: 0;
}

.incident-header__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  overflow-wrap: anywhere;
}

.incident-header__footer {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

@include laptop {
  .incident-header__title {
    font-size: $font-size-2xl;
  }

  .incident-header__footer {
    flex-direction: row;
    align-items: center;
    gap: $space-6;
  }

  .incident-header__deadline {
    flex: 1;
  }
}
</style>
