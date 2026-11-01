<!-- Panel de filtros de incidentes para celular y tablet (sobre AppFilterSheet): se edita en borrador y muestra cuántos
     resultados daría antes de aplicarlo. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AppFilterSheet from '@/shared/ui/AppFilterSheet.vue'
import type { FilterOption } from '../../application/useListFilterOptions'
import { DEFAULT_CRITERIA, type ListCriteria } from '../../domain/listing'
import IncidentFilterFields from './IncidentFilterFields.vue'
import IncidentMoreFilters from './IncidentMoreFilters.vue'

const COUNT_DELAY_MS = 350

const props = defineProps<{
  criteria: ListCriteria
  statuses: FilterOption[]
  priorities: FilterOption[]
  assignees: FilterOption[]
  myId: string | null
  canSeeDeleted: boolean
  countFor: (draft: ListCriteria) => Promise<number | null>
}>()
const emit = defineEmits<{ apply: [criteria: ListCriteria] }>()
const open = defineModel<boolean>('open', { default: false })

const draft = ref<ListCriteria>({ ...props.criteria })
const count = ref<number | null>(null)
let timer: ReturnType<typeof setTimeout> | undefined

// Cada vez que se abre, parte de los filtros vigentes
watch(open, (isOpen) => {
  if (isOpen) draft.value = { ...props.criteria }
})

// El número de resultados se pide con una pequeña espera, para no consultar en cada toque
watch(
  draft,
  (value) => {
    clearTimeout(timer)
    // Mientras se recalcula no se muestra un número viejo
    count.value = null
    timer = setTimeout(async () => (count.value = await props.countFor(value)), COUNT_DELAY_MS)
  },
  { deep: true },
)

const applyLabel = computed(() =>
  count.value === null ? 'Ver resultados' : `Ver ${count.value} ${count.value === 1 ? 'resultado' : 'resultados'}`,
)

function change(changes: Partial<ListCriteria>): void {
  draft.value = { ...draft.value, ...changes }
}

function clear(): void {
  draft.value = {
    ...DEFAULT_CRITERIA,
    search: draft.value.search,
    sort: draft.value.sort,
    descending: draft.value.descending,
    view: draft.value.view,
    groupBy: draft.value.groupBy,
  }
}

function apply(): void {
  emit('apply', draft.value)
  open.value = false
}
</script>

<template>
  <AppFilterSheet v-model:open="open" :apply-label="applyLabel" @clear="clear" @apply="apply">
    <IncidentFilterFields
      :criteria="draft"
      variant="chips"
      :statuses="statuses"
      :priorities="priorities"
      :assignees="assignees"
      @change="change"
    />
    <IncidentMoreFilters :criteria="draft" :my-id="myId" :can-see-deleted="canSeeDeleted" @change="change" />
  </AppFilterSheet>
</template>
