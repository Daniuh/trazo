<!-- Tabla de datos de los listados (portátil y monitor): encabezados que ordenan, filas que se abren con clic o Enter y
     una fila elegida resaltada. Cada celda se puede dibujar con el slot "cell-<columna>".
     Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts" generic="T, F extends string">
import AppIcon from './AppIcon.vue'
import type { DataColumn } from './dataTable'

const props = defineProps<{
  columns: DataColumn[]
  rows: T[]
  rowKey: (row: T) => string
  rowLabel?: (row: T) => string
  sort?: F
  descending?: boolean
  selectedKey?: string | null
}>()
const emit = defineEmits<{ open: [key: string]; sort: [field: F, descending: boolean] }>()

const sortState = (key: string) => (props.sort === key ? (props.descending ? 'descending' : 'ascending') : 'none')

// La primera vez que se toca una columna se ordena de mayor a menor (lo más reciente o lo más grave primero)
function toggle(key: F): void {
  emit('sort', key, props.sort === key ? !props.descending : true)
}

function cellValue(row: T, key: string): unknown {
  return (row as Record<string, unknown>)[key]
}
</script>

<template>
  <div class="app-data-table">
    <table class="app-data-table__table">
      <thead>
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            class="app-data-table__head"
            scope="col"
            :aria-sort="column.sortable ? sortState(column.key) : undefined"
          >
            <button v-if="column.sortable" class="app-data-table__sort" type="button" @click="toggle(column.key as F)">
              {{ column.label }}
              <AppIcon v-if="sort === column.key" :name="descending ? 'chevron-down' : 'chevron-up'" size="sm" />
            </button>
            <span v-else :class="{ 'app-data-table__hidden': column.hiddenLabel }">{{ column.label }}</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in rows"
          :key="rowKey(row)"
          class="app-data-table__row"
          :class="{ 'app-data-table__row--selected': rowKey(row) === selectedKey }"
          tabindex="0"
          :aria-label="rowLabel?.(row)"
          :aria-selected="selectedKey === undefined ? undefined : rowKey(row) === selectedKey"
          @click="emit('open', rowKey(row))"
          @keydown.enter="emit('open', rowKey(row))"
        >
          <td
            v-for="column in columns"
            :key="column.key"
            class="app-data-table__cell"
            :class="column.emphasis ? `app-data-table__cell--${column.emphasis}` : null"
          >
            <slot :name="`cell-${column.key}`" :row="row">{{ cellValue(row, column.key) }}</slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.app-data-table {
  overflow-x: auto;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.app-data-table__table {
  width: 100%;
  border-collapse: collapse;
  font-size: $font-size-sm;
}

.app-data-table__head {
  padding: $space-3;
  border-bottom: 1px solid $color-border;
  color: $color-text-secondary;
  font-weight: $font-weight-medium;
  text-align: left;
  white-space: nowrap;
}

.app-data-table__sort {
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

.app-data-table__row {
  cursor: pointer;

  &:hover {
    background: $color-page;
  }
}

.app-data-table__row--selected {
  background: $color-page;
  box-shadow: inset 3px 0 0 $color-primary;
}

.app-data-table__cell {
  padding: $space-3;
  border-bottom: 1px solid $color-border;
  white-space: nowrap;
}

.app-data-table__cell--secondary {
  color: $color-text-secondary;
  font-weight: $font-weight-medium;
}

// El texto largo se corta con "…" para que la tabla no crezca de más
.app-data-table__cell--grow {
  max-width: 0;
  width: 40%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.app-data-table__hidden {
  @include visually-hidden;
}
</style>
