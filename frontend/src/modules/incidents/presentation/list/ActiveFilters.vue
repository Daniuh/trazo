<!-- Filtros activos como etiquetas que se quitan con ✕, más "Limpiar filtros". Así siempre se ve qué se está mirando.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import { rangeLabel } from '@/shared/ui/form/dateRange'
import type { FilterOption } from '../../application/useListFilterOptions'
import { DEFAULT_CRITERIA, type ListCriteria } from '../../domain/listing'

const props = defineProps<{
  criteria: ListCriteria
  statuses: FilterOption[]
  priorities: FilterOption[]
  assignees: FilterOption[]
}>()
const emit = defineEmits<{ change: [changes: Partial<ListCriteria>] }>()

interface Chip {
  key: string
  label: string
  remove: Partial<ListCriteria>
}

// Solo los valores con nombre conocido: mientras cargan los catálogos no se muestran códigos internos
function listChips(key: 'statuses' | 'priorities' | 'assignees', options: FilterOption[]): Chip[] {
  return props.criteria[key].flatMap((value) => {
    const label = options.find((option) => option.value === value)?.label
    if (!label) return []
    return [{ key: `${key}:${value}`, label, remove: { [key]: props.criteria[key].filter((item) => item !== value) } }]
  })
}

const chips = computed<Chip[]>(() => {
  const { createdFrom, createdTo, dueFrom, dueTo, creator, overdue, deleted } = props.criteria
  const extra: (Chip | false)[] = [
    Boolean(createdFrom ?? createdTo) && {
      key: 'created',
      label: `Creado: ${rangeLabel(createdFrom, createdTo)}`,
      remove: { createdFrom: null, createdTo: null },
    },
    Boolean(dueFrom ?? dueTo) && {
      key: 'due',
      label: `Vence: ${rangeLabel(dueFrom, dueTo)}`,
      remove: { dueFrom: null, dueTo: null },
    },
    Boolean(creator) && { key: 'creator', label: 'Creados por mí', remove: { creator: null } },
    overdue && { key: 'overdue', label: 'Vencidos', remove: { overdue: false } },
    deleted && { key: 'deleted', label: 'Eliminados', remove: { deleted: false } },
  ]
  return [
    ...listChips('statuses', props.statuses),
    ...listChips('priorities', props.priorities),
    ...listChips('assignees', props.assignees),
    ...extra.filter((chip): chip is Chip => chip !== false),
  ]
})

function clearAll(): void {
  const { search, sort, descending, perPage } = props.criteria
  emit('change', { ...DEFAULT_CRITERIA, search, sort, descending, perPage })
}
</script>

<template>
  <div v-if="chips.length > 0" class="active-filters">
    <ul class="active-filters__list" aria-label="Filtros activos">
      <li v-for="chip in chips" :key="chip.key">
        <button
          class="active-filters__chip"
          type="button"
          :aria-label="`Quitar filtro ${chip.label}`"
          @click="emit('change', chip.remove)"
        >
          {{ chip.label }}
          <AppIcon name="close" size="sm" />
        </button>
      </li>
    </ul>
    <AppButton variant="link" @click="clearAll">Limpiar filtros</AppButton>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.active-filters,
.active-filters__list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: $space-2;
}

.active-filters__list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.active-filters__chip {
  display: inline-flex;
  align-items: center;
  gap: $space-1;
  min-height: $space-6;
  padding: 0 $space-2 0 $space-3;
  border: 1px solid $color-border-strong;
  border-radius: $radius-pill;
  background: $color-surface;
  color: $color-text;
  font: inherit;
  font-size: $font-size-xs;
  cursor: pointer;

  &:hover {
    background: $color-border;
  }
}
</style>
