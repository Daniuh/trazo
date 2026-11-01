<!-- Estado (etiqueta sólida), "Vencido" si aplica y prioridad (punto de color). Lo usan las tarjetas del listado y la vista previa. -->
<!-- Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import type { Incident } from '../../domain/incident'
import { OVERDUE_TONE, priorityTone, statusTone } from '../incidentTones'

defineProps<{ incident: Pick<Incident, 'status' | 'priority' | 'isOverdue'> }>()
</script>

<template>
  <div class="incident-badges">
    <AppBadge :tone="statusTone(incident.status.code)">{{ incident.status.name }}</AppBadge>
    <AppBadge v-if="incident.isOverdue" :tone="OVERDUE_TONE">Vencido</AppBadge>
    <AppDot :tone="priorityTone(incident.priority.code)">{{ incident.priority.name }}</AppDot>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
}
</style>
