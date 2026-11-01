<!-- Eventos en tabla (portátil y monitor): severidad, evento y mensaje, veces, última vez y estado. Elegir una fila abre
     su detalle; con el detalle al lado (monitor) se oculta la columna de veces. Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatShortDateTime } from '@/shared/format/dates'
import { formatNumber } from '@/shared/format/numbers'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppDataTable from '@/shared/ui/AppDataTable.vue'
import type { DataColumn } from '@/shared/ui/dataTable'
import type { SystemEvent } from '../../domain/system'
import { EVENT_STATUS_LABEL, EVENT_STATUS_TONE, SEVERITY_LABEL, SEVERITY_TONE } from '../systemTexts'

const props = defineProps<{ items: SystemEvent[]; selectedId: number | null; compact: boolean }>()
const emit = defineEmits<{ open: [id: number] }>()

const columns = computed<DataColumn[]>(() => [
  { key: 'severity', label: 'Severidad' },
  { key: 'event', label: 'Evento', emphasis: 'grow' },
  ...(props.compact ? [] : [{ key: 'occurrences', label: 'Veces' }]),
  { key: 'last', label: 'Última vez', emphasis: 'secondary' },
  { key: 'status', label: 'Estado' },
])
</script>

<template>
  <AppDataTable
    :columns="columns"
    :rows="items"
    :row-key="(event) => String(event.id)"
    :row-label="(event) => `${event.type.name}: ${event.message}`"
    :selected-key="selectedId === null ? null : String(selectedId)"
    @open="emit('open', Number($event))"
  >
    <template #cell-severity="{ row }">
      <AppBadge :tone="SEVERITY_TONE[row.type.severity]">{{ SEVERITY_LABEL[row.type.severity] }}</AppBadge>
    </template>
    <template #cell-event="{ row }">
      <span class="event-table__name">{{ row.type.name }}</span>
      <span class="event-table__message">{{ row.message }}</span>
    </template>
    <template #cell-occurrences="{ row }">{{ formatNumber(row.occurrences) }}</template>
    <template #cell-last="{ row }">{{ formatShortDateTime(row.lastSeenAt) }}</template>
    <template #cell-status="{ row }">
      <AppBadge :tone="EVENT_STATUS_TONE[row.status]">{{ EVENT_STATUS_LABEL[row.status] }}</AppBadge>
    </template>
  </AppDataTable>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.event-table__name,
.event-table__message {
  display: block;
}

.event-table__name {
  font-weight: $font-weight-medium;
}

.event-table__message {
  color: $color-text-secondary;
  font-size: $font-size-xs;
}
</style>
