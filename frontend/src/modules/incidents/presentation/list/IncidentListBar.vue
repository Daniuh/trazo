<!-- Barra sobre los resultados, en una fila de alto fijo: cuántos hay, "Agrupar por" y "Ordenar por" con su nombre al
     lado (solo en la lista; en celular y tablet el orden ya está en la barra de filtros) y el cambio entre Lista y Tabla.
     Al pasar a Tabla los selectores se van sin mover lo de abajo. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import { formatNumber } from '@/shared/format/numbers'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { GROUP_LABELS, GROUP_OPTIONS, type GroupBy } from '../../domain/grouping'
import type { ListCriteria, ListView, SortField } from '../../domain/listing'
import { SORT_CHOICES } from './sortOptions'

const props = defineProps<{ criteria: ListCriteria; total: number }>()
const emit = defineEmits<{ change: [changes: Partial<ListCriteria>] }>()

const { isLaptopUp } = useViewport()

const VIEW_OPTIONS: { label: string; value: ListView }[] = [
  { label: 'Lista', value: 'list' },
  { label: 'Tabla', value: 'table' },
]
const GROUP_CHOICES = GROUP_OPTIONS.map((value) => ({ label: GROUP_LABELS[value], value }))

const view = computed({
  get: () => props.criteria.view,
  set: (value: ListView) => emit('change', { view: value }),
})
const groupBy = computed({
  get: () => props.criteria.groupBy,
  set: (value: GroupBy | GroupBy[] | null) => emit('change', { groupBy: (value as GroupBy | null) ?? 'none' }),
})
const sort = computed({
  get: () => `${props.criteria.descending ? '-' : ''}${props.criteria.sort}`,
  set: (value: string | string[] | null) => {
    const raw = String(value ?? '-created_at')
    emit('change', { sort: raw.replace(/^-/, '') as SortField, descending: raw.startsWith('-') })
  },
})
const count = computed(() => (props.total === 1 ? '1 incidente' : `${formatNumber(props.total)} incidentes`))
</script>

<template>
  <div class="list-bar">
    <p class="list-bar__count">{{ count }}</p>
    <div class="list-bar__controls">
      <template v-if="criteria.view === 'list'">
        <AppSelect v-model="groupBy" class="list-bar__select" inline label="Agrupar por" :options="GROUP_CHOICES" />
        <AppSelect
          v-if="isLaptopUp"
          v-model="sort"
          class="list-bar__select"
          inline
          label="Ordenar por"
          :options="SORT_CHOICES"
        />
      </template>
      <AppSegmented v-model="view" class="list-bar__view" label="Ver como" :options="VIEW_OPTIONS" />
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.list-bar {
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

.list-bar__count {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// Alto fijo: con o sin selectores (Lista o Tabla), lo que sigue no se mueve
.list-bar__controls {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: $space-3;
  min-height: $field-height;
}

.list-bar__select {
  flex: 1;
  min-width: 0;
}

@include tablet {
  .list-bar {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .list-bar__select {
    flex: none;

    :deep(.q-field) {
      width: $filter-field-width;
    }
  }
}
</style>
