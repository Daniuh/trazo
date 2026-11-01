<!-- Listado de incidentes. Por defecto, lista agrupada (por vencimiento, estado, prioridad o asignado) en las cuatro vistas;
     "Tabla" muestra la tabla en portátil y monitor y tarjetas en tablet y celular. En monitor, elegir uno lo muestra en la
     vista previa; en las demás, abre el detalle. Los filtros, la vista y el grupo viven en la URL.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { useRealtime } from '@/modules/realtime/application/realtimeBus'
import { formatNumber } from '@/shared/format/numbers'
import AppButton from '@/shared/ui/AppButton.vue'
import AppPagination from '@/shared/ui/AppPagination.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { useIncidentList } from '../application/useIncidentList'
import { rememberListLocation } from '../application/listReturn'
import { useListFilterOptions } from '../application/useListFilterOptions'
import { activeFilterCount, DEFAULT_CRITERIA, PER_PAGE_OPTIONS, type SortField } from '../domain/listing'
import ActiveFilters from './list/ActiveFilters.vue'
import IncidentCards from './list/IncidentCards.vue'
import IncidentGroupedList from './list/IncidentGroupedList.vue'
import IncidentListBar from './list/IncidentListBar.vue'
import IncidentPreview from './list/IncidentPreview.vue'
import IncidentTable from './list/IncidentTable.vue'
import IncidentToolbar from './list/IncidentToolbar.vue'

const router = useRouter()
const route = useRoute()

// El detalle y el formulario vuelven aquí con los mismos filtros; al salir, la ruta ya es otra y no cuenta
watch(
  () => (route.name === 'incidents' ? route.fullPath : null),
  (path) => path && rememberListLocation(path),
  { immediate: true },
)
const session = useSessionStore()
const { confirm, notify, notifyError } = useFeedback()
const { isLaptopUp, isDesktop } = useViewport()

const list = useIncidentList()
const { criteria, items, groups, total, lastPage, loading, error, exporting } = list
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

// Exporta exactamente lo filtrado, tras confirmar cuántos salen
async function exportCsv(): Promise<void> {
  const count = total.value === 1 ? '1 incidente' : `${formatNumber(total.value)} incidentes`
  const accepted = await confirm({
    title: 'Exportar incidentes',
    message: `Se exportarán ${count} con los filtros y el orden actuales, en un archivo CSV que se abre con Excel.`,
    confirmLabel: 'Exportar',
  })
  if (!accepted) return
  const failure = await list.exportCsv()
  if (failure) notifyError(failure)
  else notify('Exportación lista. Revisa la carpeta de descargas.')
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
  const { sort, descending, view, groupBy } = criteria.value
  list.update({ ...DEFAULT_CRITERIA, sort, descending, view, groupBy })
}
</script>

<template>
  <div class="incident-list">
    <header class="incident-list__header">
      <h1 class="incident-list__title">{{ criteria.deleted ? 'Incidentes eliminados' : 'Incidentes' }}</h1>
      <div class="incident-list__actions">
        <!-- Solo quien puede tener incidentes asignados tiene su tablero -->
        <AppButton
          v-if="session.can('incidents.view.assigned')"
          variant="secondary"
          icon="board"
          :to="{ name: 'my-incidents' }"
        >
          Mis incidentes
        </AppButton>
        <AppButton v-if="session.can('incidents.create')" icon="plus" @click="router.push({ name: 'incident-create' })">
          Nuevo incidente
        </AppButton>
      </div>
    </header>

    <IncidentToolbar
      :criteria="criteria"
      :statuses="options.statuses.value"
      :priorities="options.priorities.value"
      :assignees="options.assigneeOptions.value"
      :my-id="options.myId.value"
      :can-see-deleted="options.canSeeDeleted.value"
      :count-for="list.countFor"
      :can-export="session.can('incidents.export')"
      :exporting="exporting"
      :total="total"
      @change="list.update"
      @export="exportCsv"
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

    <IncidentListBar v-if="!error" :criteria="criteria" :total="total" @change="list.update" />

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
          <IncidentGroupedList
            v-if="criteria.view === 'list'"
            :items="items"
            :group-by="criteria.groupBy"
            :totals="groups"
            :selected-id="showPreview ? previewId : null"
            :deleted="criteria.deleted"
            @open="open"
            @restore="restore"
          />
          <IncidentTable
            v-else-if="isLaptopUp"
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

.incident-list__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
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
