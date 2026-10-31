<!-- Tabla del listado (portátil y monitor), sobre AppDataTable. Se ordena tocando el encabezado; elegir una fila la abre
     (o la muestra en la vista previa, en monitor). Con la vista previa abierta se oculta la columna "Creado".
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatShortDateTime } from '@/shared/format/dates'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppDataTable from '@/shared/ui/AppDataTable.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import type { DataColumn } from '@/shared/ui/dataTable'
import type { IncidentSummary, ListCriteria, SortField } from '../../domain/listing'
import { priorityTone, statusTone } from '../incidentTones'
import { COLUMN_LABELS } from './sortOptions'

const props = defineProps<{
  items: IncidentSummary[]
  criteria: ListCriteria
  selectedId: string | null
  compact: boolean
}>()
const emit = defineEmits<{ open: [id: string]; sort: [field: SortField, descending: boolean]; restore: [id: string] }>()

const columns = computed<DataColumn[]>(() => [
  { key: 'code', label: COLUMN_LABELS.code, sortable: true, emphasis: 'secondary' },
  { key: 'title', label: 'Título', emphasis: 'grow' },
  { key: 'status', label: COLUMN_LABELS.status, sortable: true },
  { key: 'priority', label: COLUMN_LABELS.priority, sortable: true },
  { key: 'due_date', label: COLUMN_LABELS.due_date, sortable: true },
  ...(props.compact ? [] : [{ key: 'created_at', label: COLUMN_LABELS.created_at, sortable: true }]),
  { key: 'assignee', label: 'Asignado' },
  ...(props.criteria.deleted ? [{ key: 'actions', label: 'Acciones', hiddenLabel: true }] : []),
])
</script>

<template>
  <AppDataTable
    :columns="columns"
    :rows="items"
    :row-key="(incident) => incident.id"
    :sort="criteria.sort"
    :descending="criteria.descending"
    :selected-key="selectedId"
    @open="emit('open', $event)"
    @sort="(field, descending) => emit('sort', field as SortField, descending)"
  >
    <template #cell-status="{ row }">
      <AppBadge :tone="statusTone(row.status.code)">{{ row.status.name }}</AppBadge>
    </template>
    <template #cell-priority="{ row }">
      <AppDot :tone="priorityTone(row.priority.code)">{{ row.priority.name }}</AppDot>
    </template>
    <template #cell-due_date="{ row }">
      <span v-if="row.isOverdue" class="incident-table__overdue">Vencido · </span>
      {{ formatShortDateTime(row.dueDate) }}
    </template>
    <template #cell-created_at="{ row }">{{ formatShortDateTime(row.createdAt) }}</template>
    <template #cell-assignee="{ row }">
      <span :class="{ 'incident-table__muted': !row.assignee }">{{ row.assignee?.name ?? 'Sin asignar' }}</span>
    </template>
    <template #cell-actions="{ row }">
      <AppButton v-if="row.actions.restore" variant="secondary" icon="refresh" @click.stop="emit('restore', row.id)">
        Restaurar
      </AppButton>
    </template>
  </AppDataTable>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-table__muted {
  color: $color-text-secondary;
}

.incident-table__overdue {
  color: $color-danger;
  font-weight: $font-weight-medium;
}
</style>
