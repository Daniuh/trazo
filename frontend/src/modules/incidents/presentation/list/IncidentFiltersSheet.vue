<!-- Panel de filtros de celular y tablet: sube desde abajo, se edita en borrador y muestra cuántos resultados daría
     antes de aplicarlo. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { QDialog } from 'quasar'
import { computed, ref, watch } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
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
  }
}

function apply(): void {
  emit('apply', draft.value)
  open.value = false
}
</script>

<template>
  <QDialog v-model="open" position="bottom" full-width class="app-popup-sheet">
    <div class="filters-sheet" role="dialog" aria-labelledby="filters-title">
      <h2 id="filters-title" class="filters-sheet__title">Filtros</h2>
      <div class="filters-sheet__body">
        <IncidentFilterFields
          :criteria="draft"
          variant="chips"
          :statuses="statuses"
          :priorities="priorities"
          :assignees="assignees"
          @change="change"
        />
        <IncidentMoreFilters :criteria="draft" :my-id="myId" :can-see-deleted="canSeeDeleted" @change="change" />
      </div>
      <div class="filters-sheet__footer">
        <AppButton variant="secondary" @click="clear">Limpiar</AppButton>
        <AppButton @click="apply">{{ applyLabel }}</AppButton>
      </div>
    </div>
  </QDialog>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.filters-sheet {
  display: flex;
  flex-direction: column;
  width: 100vw;
  max-width: 100vw;
  max-height: 85vh;
  border-radius: $radius-card $radius-card 0 0;
  background: $color-surface;
}

.filters-sheet__title {
  margin: 0;
  padding: $space-4 $space-4 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

// Si los filtros no caben, se desplaza el contenido y el pie con los botones queda siempre a la vista
.filters-sheet__body {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  padding: $space-4;
  overflow-y: auto;
}

.filters-sheet__footer {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: $space-2;
  padding: $space-3 $space-4;
  border-top: 1px solid $color-border;
}
</style>
