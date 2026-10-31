<!-- Filtros principales: estado, prioridad y asignado. En la barra de portátil van como selectores;
     en el panel de celular y tablet, como chips fáciles de tocar. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppChoiceChips from '@/shared/ui/form/AppChoiceChips.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import type { FilterOption } from '../../application/useListFilterOptions'
import type { ListCriteria } from '../../domain/listing'

const props = defineProps<{
  criteria: ListCriteria
  variant: 'select' | 'chips'
  statuses: FilterOption[]
  priorities: FilterOption[]
  assignees: FilterOption[]
}>()
const emit = defineEmits<{ change: [changes: Partial<ListCriteria>] }>()

// Un modelo por filtro: leer del criterio y avisar el cambio
const field = <K extends 'statuses' | 'priorities' | 'assignees'>(key: K) =>
  computed({
    get: () => props.criteria[key],
    set: (value: string | string[] | null) => emit('change', { [key]: Array.isArray(value) ? value : [] }),
  })

const statusModel = field('statuses')
const priorityModel = field('priorities')
const assigneeModel = field('assignees')
</script>

<template>
  <template v-if="variant === 'chips'">
    <AppChoiceChips v-model="statusModel" label="Estado" :options="statuses" />
    <AppChoiceChips v-model="priorityModel" label="Prioridad" :options="priorities" />
    <AppChoiceChips v-if="assignees.length > 0" v-model="assigneeModel" label="Asignado" :options="assignees" />
  </template>
  <template v-else>
    <AppSelect
      v-model="statusModel"
      class="filter-field"
      label="Estado"
      :options="statuses"
      placeholder="Todos"
      multiple
    />
    <AppSelect
      v-model="priorityModel"
      class="filter-field"
      label="Prioridad"
      :options="priorities"
      placeholder="Todas"
      multiple
    />
    <AppSelect
      v-if="assignees.length > 0"
      v-model="assigneeModel"
      class="filter-field"
      label="Asignado"
      :options="assignees"
      placeholder="Cualquiera"
      multiple
    />
  </template>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.filter-field {
  width: $filter-field-width;
}
</style>
