<!-- Tabla del listado (portátil y monitor). Se ordena tocando el encabezado; elegir una fila la abre (o la muestra en la
     vista previa, en monitor). Con la vista previa abierta se oculta la columna "Creado". Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { formatShortDateTime } from '@/shared/format/dates'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppDot from '@/shared/ui/AppDot.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
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

const SORTABLE: SortField[] = ['code', 'status', 'priority', 'due_date', 'created_at']

const sortState = (field: SortField) =>
  props.criteria.sort === field ? (props.criteria.descending ? 'descending' : 'ascending') : 'none'

// La primera vez que se toca una columna se ordena de mayor a menor (lo más reciente o lo más grave primero)
function toggle(field: SortField): void {
  emit('sort', field, props.criteria.sort === field ? !props.criteria.descending : true)
}
</script>

<template>
  <div class="incident-table">
    <table class="incident-table__table">
      <thead>
        <tr>
          <th class="incident-table__head" scope="col" :aria-sort="sortState('code')">
            <button class="incident-table__sort" type="button" @click="toggle('code')">
              {{ COLUMN_LABELS.code }}
              <AppIcon
                v-if="criteria.sort === 'code'"
                :name="criteria.descending ? 'chevron-down' : 'chevron-up'"
                size="sm"
              />
            </button>
          </th>
          <th class="incident-table__head" scope="col">Título</th>
          <th
            v-for="field in SORTABLE.slice(1).filter((f) => !(compact && f === 'created_at'))"
            :key="field"
            class="incident-table__head"
            scope="col"
            :aria-sort="sortState(field)"
          >
            <button class="incident-table__sort" type="button" @click="toggle(field)">
              {{ COLUMN_LABELS[field] }}
              <AppIcon
                v-if="criteria.sort === field"
                :name="criteria.descending ? 'chevron-down' : 'chevron-up'"
                size="sm"
              />
            </button>
          </th>
          <th class="incident-table__head" scope="col">Asignado</th>
          <th v-if="criteria.deleted" class="incident-table__head" scope="col">
            <span class="incident-table__hidden">Acciones</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="incident in items"
          :key="incident.id"
          class="incident-table__row"
          :class="{ 'incident-table__row--selected': incident.id === selectedId }"
          tabindex="0"
          :aria-selected="incident.id === selectedId"
          @click="emit('open', incident.id)"
          @keydown.enter="emit('open', incident.id)"
        >
          <td class="incident-table__cell incident-table__cell--code">{{ incident.code }}</td>
          <td class="incident-table__cell incident-table__cell--title">{{ incident.title }}</td>
          <td class="incident-table__cell">
            <AppBadge :tone="statusTone(incident.status.code)">{{ incident.status.name }}</AppBadge>
          </td>
          <td class="incident-table__cell">
            <AppDot :tone="priorityTone(incident.priority.code)">{{ incident.priority.name }}</AppDot>
          </td>
          <td class="incident-table__cell">
            <span v-if="incident.isOverdue" class="incident-table__overdue">Vencido · </span>
            {{ formatShortDateTime(incident.dueDate) }}
          </td>
          <td v-if="!compact" class="incident-table__cell">{{ formatShortDateTime(incident.createdAt) }}</td>
          <td class="incident-table__cell" :class="{ 'incident-table__cell--muted': !incident.assignee }">
            {{ incident.assignee?.name ?? 'Sin asignar' }}
          </td>
          <td v-if="criteria.deleted" class="incident-table__cell">
            <AppButton
              v-if="incident.actions.restore"
              variant="secondary"
              icon="refresh"
              @click.stop="emit('restore', incident.id)"
            >
              Restaurar
            </AppButton>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-table {
  overflow-x: auto;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.incident-table__table {
  width: 100%;
  border-collapse: collapse;
  font-size: $font-size-sm;
}

.incident-table__head {
  padding: $space-3;
  border-bottom: 1px solid $color-border;
  color: $color-text-secondary;
  font-weight: $font-weight-medium;
  text-align: left;
  white-space: nowrap;
}

.incident-table__sort {
  display: inline-flex;
  align-items: center;
  gap: $space-1;
  padding: 0;
  border: 0;
  background: none;
  color: inherit;
  font: inherit;
  cursor: pointer;

  &:hover {
    color: $color-text;
  }
}

.incident-table__row {
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

.incident-table__row--selected {
  background: $color-page;
  box-shadow: inset 3px 0 0 $color-primary;
}

.incident-table__cell {
  padding: $space-3;
  border-bottom: 1px solid $color-border;
  white-space: nowrap;
}

.incident-table__cell--code {
  color: $color-text-secondary;
  font-weight: $font-weight-medium;
}

// El título se corta con "…" para que la tabla no crezca de más
.incident-table__cell--title {
  max-width: 0;
  width: 40%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.incident-table__cell--muted {
  color: $color-text-secondary;
}

.incident-table__overdue {
  color: $color-danger;
  font-weight: $font-weight-medium;
}

.incident-table__hidden {
  @include visually-hidden;
}
</style>
