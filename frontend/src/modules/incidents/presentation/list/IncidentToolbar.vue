<!-- Barra del listado. Portátil y monitor: búsqueda, estado, prioridad, asignado, "Más filtros" y "Exportar" (solo el ícono en portátil).
     Celular y tablet: búsqueda, "Filtros (n)" (abre el panel), "Ordenar por" y ⋯ con "Exportar CSV".
     Exportar solo aparece con el permiso. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AppActionMenu from '@/shared/ui/AppActionMenu.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppMenu from '@/shared/ui/AppMenu.vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import AppTextField from '@/shared/ui/form/AppTextField.vue'
import type { MenuItem } from '@/shared/ui/menu'
import { useViewport } from '@/shared/ui/useViewport'
import type { FilterOption } from '../../application/useListFilterOptions'
import {
  activeFilterCount,
  isSearchable,
  MIN_SEARCH_LENGTH,
  type ListCriteria,
  type SortField,
} from '../../domain/listing'
import IncidentFilterFields from './IncidentFilterFields.vue'
import IncidentFiltersSheet from './IncidentFiltersSheet.vue'
import IncidentMoreFilters from './IncidentMoreFilters.vue'
import { SORT_CHOICES } from './sortOptions'

const SEARCH_DELAY_MS = 400

const props = defineProps<{
  criteria: ListCriteria
  statuses: FilterOption[]
  priorities: FilterOption[]
  assignees: FilterOption[]
  myId: string | null
  canSeeDeleted: boolean
  // Exportar: solo con el permiso; sin resultados queda desactivado
  canExport: boolean
  exporting: boolean
  total: number
  countFor: (draft: ListCriteria) => Promise<number | null>
}>()
const emit = defineEmits<{ change: [changes: Partial<ListCriteria>]; export: [] }>()

const { isLaptopUp, isDesktop } = useViewport()
const sheetOpen = ref(false)
const search = ref(props.criteria.search)
let timer: ReturnType<typeof setTimeout> | undefined

// Se busca al dejar de escribir; con menos de 3 letras se avisa y no se consulta
const searchHint = computed(() =>
  isSearchable(search.value) ? null : `Escribe al menos ${MIN_SEARCH_LENGTH} letras para buscar.`,
)
watch(search, (value) => {
  clearTimeout(timer)
  if (isSearchable(value)) timer = setTimeout(() => emit('change', { search: value.trim() }), SEARCH_DELAY_MS)
})
watch(
  () => props.criteria.search,
  (value) => {
    if (value !== search.value.trim()) search.value = value
  },
)

const filterCount = computed(() => activeFilterCount(props.criteria))
const moreCount = computed(() => {
  const { createdFrom, createdTo, dueFrom, dueTo, creator, overdue, deleted, reopenRequested } = props.criteria
  return [createdFrom ?? createdTo, dueFrom ?? dueTo, creator, overdue, deleted, reopenRequested].filter(Boolean).length
})

const sortModel = computed({
  get: () => `${props.criteria.descending ? '-' : ''}${props.criteria.sort}`,
  set: (value: string | string[] | null) => {
    const raw = String(value ?? '-created_at')
    emit('change', { sort: raw.replace(/^-/, '') as SortField, descending: raw.startsWith('-') })
  },
})

const exportItems = computed<MenuItem[]>(() => [
  {
    key: 'export',
    label: props.exporting ? 'Exportando…' : 'Exportar CSV',
    icon: 'download',
    disabled: props.total === 0 || props.exporting,
  },
])

function applySheet(draft: ListCriteria): void {
  emit('change', draft)
}
</script>

<template>
  <div class="incident-toolbar">
    <div class="incident-toolbar__search">
      <AppTextField
        v-model="search"
        label="Buscar"
        type="search"
        placeholder="Título, descripción o código (26-0142)"
        :maxlength="100"
      />
      <p v-if="searchHint" class="incident-toolbar__hint" role="status">{{ searchHint }}</p>
    </div>

    <template v-if="isLaptopUp">
      <IncidentFilterFields
        :criteria="criteria"
        variant="select"
        :statuses="statuses"
        :priorities="priorities"
        :assignees="assignees"
        @change="emit('change', $event)"
      />
      <AppButton class="incident-toolbar__more" variant="secondary" icon="filter">
        Más filtros{{ moreCount > 0 ? ` (${moreCount})` : '' }}
        <AppMenu align="right">
          <div class="incident-toolbar__menu">
            <IncidentMoreFilters
              :criteria="criteria"
              :my-id="myId"
              :can-see-deleted="canSeeDeleted"
              @change="emit('change', $event)"
            />
          </div>
        </AppMenu>
      </AppButton>
      <!-- En portátil no cabe con texto en la misma fila: queda el ícono, con su nombre para lectores y al pasar el cursor -->
      <AppButton
        v-if="canExport"
        class="incident-toolbar__more"
        variant="secondary"
        icon="download"
        :loading="exporting"
        :disabled="total === 0"
        :aria-label="isDesktop ? undefined : 'Exportar'"
        :title="isDesktop ? undefined : 'Exportar CSV'"
        @click="emit('export')"
      >
        <template v-if="isDesktop">{{ exporting ? 'Exportando…' : 'Exportar' }}</template>
      </AppButton>
    </template>

    <div v-else class="incident-toolbar__compact" :class="{ 'incident-toolbar__compact--menu': canExport }">
      <AppButton variant="secondary" icon="filter" @click="sheetOpen = true">
        Filtros{{ filterCount > 0 ? ` (${filterCount})` : '' }}
      </AppButton>
      <AppSelect v-model="sortModel" class="incident-toolbar__sort" label="Ordenar por" :options="SORT_CHOICES" />
      <AppButton v-if="canExport" variant="secondary" :loading="exporting" aria-label="Más opciones del listado">
        <AppIcon v-if="!exporting" name="more" />
        <AppActionMenu :items="exportItems" label="Más opciones del listado" align="right" @select="emit('export')" />
      </AppButton>
      <IncidentFiltersSheet
        v-model:open="sheetOpen"
        :criteria="criteria"
        :statuses="statuses"
        :priorities="priorities"
        :assignees="assignees"
        :my-id="myId"
        :can-see-deleted="canSeeDeleted"
        :count-for="countFor"
        @apply="applySheet"
      />
    </div>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-toolbar {
  display: flex;
  flex-direction: column;
  gap: $space-3;
}

.incident-toolbar__hint {
  margin: $space-1 0 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

.incident-toolbar__compact {
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: end;
  gap: $space-2;
}

// Con el menú ⋯ (exportar) al final de la fila
.incident-toolbar__compact--menu {
  grid-template-columns: auto 1fr auto;
}

// La etiqueta "Ordenar por" queda para lectores de pantalla; el texto del selector ya lo explica
.incident-toolbar__sort :deep(.app-field__label) {
  @include visually-hidden;
}

.incident-toolbar__menu {
  width: $preview-width;
  padding: $space-4;
}

// En portátil todo va en una fila: la búsqueda toma el espacio libre
@include laptop {
  .incident-toolbar {
    flex-flow: row wrap;
    align-items: flex-start;
  }

  .incident-toolbar__search {
    flex: 1;
    min-width: $filter-field-width;
  }

  // Alinea el botón con los campos, que tienen etiqueta arriba, y le da su mismo alto
  .incident-toolbar__more {
    min-height: $field-height;
    margin-top: calc(#{$font-size-sm} * #{$line-height-base} + #{$space-1});
  }
}
</style>
