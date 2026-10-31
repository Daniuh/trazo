<!-- Auditoría en tabla (portátil y monitor): fecha, quién, acción, sobre qué y el primer cambio. Elegir una fila abre
     su detalle. Con el detalle al lado (monitor), se ocultan las columnas largas. Documentado en docs/frontend/audit.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatShortDateTime } from '@/shared/format/dates'
import AppDataTable from '@/shared/ui/AppDataTable.vue'
import type { DataColumn } from '@/shared/ui/dataTable'
import type { AuditCatalog, AuditEntry } from '../../domain/audit'
import { changeSummary, targetText } from '../auditTexts'

const props = defineProps<{
  items: AuditEntry[]
  catalog: AuditCatalog | null
  selectedId: number | null
  compact: boolean
}>()
const emit = defineEmits<{ open: [id: number] }>()

const columns = computed<DataColumn[]>(() => [
  { key: 'when', label: 'Fecha', emphasis: 'secondary' },
  { key: 'who', label: 'Hecho por' },
  { key: 'action', label: 'Acción' },
  { key: 'target', label: 'Sobre', emphasis: 'grow' },
  ...(props.compact ? [] : [{ key: 'change', label: 'Cambio', emphasis: 'grow' as const }]),
])
</script>

<template>
  <AppDataTable
    :columns="columns"
    :rows="items"
    :row-key="(entry) => String(entry.id)"
    :row-label="(entry) => `${entry.type.name}: ${targetText(entry)}`"
    :selected-key="selectedId === null ? null : String(selectedId)"
    @open="emit('open', Number($event))"
  >
    <template #cell-when="{ row }">{{ formatShortDateTime(row.createdAt) }}</template>
    <template #cell-who="{ row }">{{ row.user.name }}</template>
    <template #cell-action="{ row }">{{ row.type.name }}</template>
    <template #cell-target="{ row }">{{ targetText(row) }}</template>
    <template #cell-change="{ row }">{{ changeSummary(row, catalog) }}</template>
  </AppDataTable>
</template>
