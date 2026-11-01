<!-- Listado de incidentes. Monitor: tabla con vista previa al elegir una fila. Portátil: tabla; elegir una fila abre el detalle.
     Tablet y celular: tarjetas. Los filtros viven en la URL. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { useRealtime } from '@/modules/realtime/application/realtimeBus'
import AppButton from '@/shared/ui/AppButton.vue'
import AppPagination from '@/shared/ui/AppPagination.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { useIncidentList } from '../application/useIncidentList'
import { useListFilterOptions } from '../application/useListFilterOptions'
import { activeFilterCount, DEFAULT_CRITERIA, PER_PAGE_OPTIONS, type SortField } from '../domain/listing'
import ActiveFilters from './list/ActiveFilters.vue'
import IncidentCards from './list/IncidentCards.vue'
import IncidentPreview from './list/IncidentPreview.vue'
import IncidentTable from './list/IncidentTable.vue'
import IncidentToolbar from './list/IncidentToolbar.vue'

const router = useRouter()
const session = useSessionStore()
const { notify, notifyError } = useFeedback()
const { isLaptopUp, isDesktop } = useViewport()

const list = useIncidentList()
const { criteria, items, total, lastPage, loading, error } = list
const options = useListFilterOptions()

const previewId = ref<string | null>(null)
const filtered = computed(() => activeFilterCount(criteria.value) > 0 || criteria.value.search !== '')
const showPreview = computed(() => isDesktop.value && previewId.value !== null)

// En monitor, elegir una fila la muestra al lado; en las demás vistas, abre el detalle
function open(id: string): void {
  if (isDesktop.value) previewId.value = id
  else void router.push({ name: 'incident-detail', params: { id } })
}

function sort(field: SortField, descending: boolean): void {
  list.update({ sort: field, descending })
}

async function restore(id: string): Promise<void> {
  const failure = await list.restore(id)
  if (failure) notifyError(failure)
  else notify('Incidente restaurado.')
}

// Cambios de otras personas: se anuncian sin mover las filas mientras alguien lee. Se cuentan incidentes, no avisos
const changed = ref<string[]>([])
useRealtime('incident', ({ id }) => {
  if (!changed.value.includes(id)) changed.value = [...changed.value, id]
})
// Al volver la conexión pudo perderse algún aviso: se vuelve a consultar
useRealtime('reconnected', () => void list.load())
watch(criteria, () => (changed.value = []))

function refresh(): void {
  changed.value = []
  void list.load()
}

function clearFilters(): void {
  list.update({ ...DEFAULT_CRITERIA, sort: criteria.value.sort, descending: criteria.value.descending })
}
</script>

<template>
  <div class="incident-list">
    <header class="incident-list__header">
      <h1 class="incident-list__title">{{ criteria.deleted ? 'Incidentes eliminados' : 'Incidentes' }}</h1>
      <AppButton v-if="session.can('incidents.create')" icon="plus" @click="router.push({ name: 'incident-create' })">
        Nuevo incidente
      </AppButton>
    </header>

    <IncidentToolbar
      :criteria="criteria"
      :statuses="options.statuses.value"
      :priorities="options.priorities.value"
      :assignees="options.assigneeOptions.value"
      :my-id="options.myId.value"
      :can-see-deleted="options.canSeeDeleted.value"
      :count-for="list.countFor"
      @change="list.update"
    />
    <ActiveFilters
      :criteria="criteria"
      :statuses="options.statuses.value"
      :priorities="options.priorities.value"
      :assignees="options.assigneeOptions.value"
      @change="list.update"
    />
    <AppAlert v-if="changed.length > 0" tone="info">
      {{ changed.length === 1 ? 'Hay 1 incidente' : `Hay ${changed.length} incidentes` }} con cambios de otras personas.
      <AppButton variant="link" icon="refresh" @click="refresh">Actualizar</AppButton>
    </AppAlert>

    <div class="incident-list__body" :class="{ 'incident-list__body--preview': showPreview }">
      <section class="incident-list__results" :aria-busy="loading" aria-label="Resultados">
        <AppErrorState v-if="error" :error="error" @retry="list.load" />
        <AppSkeleton v-else-if="loading && items.length === 0" :lines="8" />
        <AppEmptyState
          v-else-if="items.length === 0 && filtered"
          title="No hay incidentes con estos filtros"
          description="Prueba con otros filtros o con otra búsqueda."
        >
          <AppButton variant="secondary" @click="clearFilters">Limpiar filtros</AppButton>
        </AppEmptyState>
        <AppEmptyState
          v-else-if="items.length === 0"
          title="Todavía no hay incidentes"
          description="Cuando alguien reporte uno, aparecerá aquí."
        >
          <AppButton v-if="session.can('incidents.create')" @click="router.push({ name: 'incident-create' })">
            Nuevo incidente
          </AppButton>
        </AppEmptyState>

        <template v-else>
          <IncidentTable
            v-if="isLaptopUp"
            :items="items"
            :criteria="criteria"
            :selected-id="showPreview ? previewId : null"
            :compact="showPreview"
            @open="open"
            @sort="sort"
            @restore="restore"
          />
          <IncidentCards v-else :items="items" :deleted="criteria.deleted" @open="open" @restore="restore" />

          <AppPagination
            :page="criteria.page"
            :last-page="lastPage"
            :total="total"
            :per-page="criteria.perPage"
            :per-page-options="PER_PAGE_OPTIONS"
            @update:page="list.update({ page: $event })"
            @update:per-page="list.update({ perPage: $event })"
          />
        </template>
      </section>

      <IncidentPreview
        v-if="showPreview && previewId"
        :id="previewId"
        :key="previewId"
        class="incident-list__preview"
        @changed="list.load"
        @close="previewId = null"
        @open="router.push({ name: 'incident-detail', params: { id: $event } })"
      />
    </div>

    <!-- Aviso para lectores de pantalla mientras llega la página nueva -->
    <p v-if="error === null && loading && items.length > 0" class="incident-list__hidden" role="status">
      Actualizando resultados…
    </p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-list,
.incident-list__results {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.incident-list__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.incident-list__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

// Mientras llega la página nueva, la actual se atenúa en lugar de desaparecer
.incident-list__results[aria-busy='true'] {
  opacity: 0.6;
  transition: opacity $duration-base;
}

.incident-list__hidden {
  @include visually-hidden;
}

@include laptop {
  .incident-list__title {
    font-size: $font-size-2xl;
  }
}

// En monitor, con la vista previa abierta: tabla a la izquierda y vista previa a la derecha
@include desktop {
  .incident-list__body--preview {
    display: grid;
    grid-template-columns: minmax(0, 1fr) $preview-width;
    align-items: start;
    gap: $space-4;
  }
}
</style>
