<!-- Pestaña de eventos: los pendientes de lo más reciente a lo más antiguo, con filtros en la URL y "Cargar más".
     Monitor: tabla con el detalle al lado. Portátil: tabla y el detalle en un panel a la derecha. Tablet y celular:
     tarjetas y el detalle en un panel inferior. Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import AppSheet from '@/shared/ui/AppSheet.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { useSystemEvents } from '../../application/useSystemEvents'
import { activeEventFilterCount, DEFAULT_EVENT_CRITERIA, type EventStatus } from '../../domain/system'
import { EVENT_STATUS_LABEL } from '../systemTexts'
import EventCards from './EventCards.vue'
import EventDetail from './EventDetail.vue'
import EventTable from './EventTable.vue'
import EventToolbar from './EventToolbar.vue'

const { isLaptopUp, isDesktop } = useViewport()
const { notify, notifyError } = useFeedback()
const list = useSystemEvents()
const { criteria, items, next, loading, loadingMore, error, types } = list

const selectedId = ref<number | null>(null)
const selected = computed(() => items.value.find((event) => event.id === selectedId.value) ?? null)
const besideTable = computed(() => isDesktop.value && selected.value !== null)
const sheetOpen = computed({
  get: () => !isDesktop.value && selected.value !== null,
  set: (open: boolean) => {
    if (!open) selectedId.value = null
  },
})
const filtered = computed(() => activeEventFilterCount(criteria.value) > 0)
const changing = ref(false)

async function changeStatus(status: EventStatus): Promise<void> {
  if (!selected.value) return
  changing.value = true
  const failure = await list.changeStatus(selected.value.id, status)
  changing.value = false
  if (failure) notifyError(failure)
  else notify(`Evento marcado como ${EVENT_STATUS_LABEL[status].toLowerCase()}.`)
}
</script>

<template>
  <div class="events-view">
    <EventToolbar :criteria="criteria" :types="types" @change="list.update" />

    <div class="events-view__body" :class="{ 'events-view__body--detail': besideTable }">
      <section class="events-view__results" :aria-busy="loading" aria-label="Eventos">
        <AppErrorState v-if="error" :error="error" @retry="list.load" />
        <AppSkeleton v-else-if="loading && items.length === 0" :lines="8" />
        <AppEmptyState
          v-else-if="items.length === 0 && filtered"
          title="No hay eventos con estos filtros"
          description="Prueba con otra severidad, otro tipo o con todos los eventos."
        >
          <AppButton variant="secondary" @click="list.update(DEFAULT_EVENT_CRITERIA)">Limpiar filtros</AppButton>
        </AppEmptyState>
        <AppEmptyState
          v-else-if="items.length === 0"
          title="No hay eventos pendientes"
          description="Cuando algo falle o haya una alerta de seguridad, aparecerá aquí."
        />

        <template v-else>
          <EventTable
            v-if="isLaptopUp"
            :items="items"
            :selected-id="besideTable ? selectedId : null"
            :compact="besideTable"
            @open="selectedId = $event"
          />
          <EventCards v-else :items="items" @open="selectedId = $event" />

          <div v-if="next" class="events-view__more">
            <AppButton variant="secondary" :loading="loadingMore" @click="list.loadMore">Cargar más</AppButton>
          </div>
        </template>
      </section>

      <aside v-if="besideTable && selected" class="events-view__detail" aria-label="Detalle del evento">
        <header class="events-view__detail-header">
          <h2 class="events-view__detail-title">{{ selected.type.name }}</h2>
          <button class="events-view__close" type="button" aria-label="Cerrar detalle" @click="selectedId = null">
            <AppIcon name="close" />
          </button>
        </header>
        <EventDetail :event="selected" :busy="changing" @status="changeStatus" />
      </aside>
    </div>

    <AppSheet v-model:open="sheetOpen" :title="selected?.type.name ?? ''">
      <EventDetail v-if="selected" :event="selected" :busy="changing" @status="changeStatus" />
    </AppSheet>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.events-view,
.events-view__results {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.events-view__more {
  display: flex;
  justify-content: center;
}

// Mientras llega la lista nueva, la actual se atenúa en lugar de desaparecer
.events-view__results[aria-busy='true'] {
  opacity: 0.6;
  transition: opacity $duration-base;
}

.events-view__detail {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
}

.events-view__detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-3;
}

.events-view__detail-title {
  margin: 0;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
}

.events-view__close {
  display: inline-flex;
  padding: $space-1;
  border: 0;
  border-radius: $radius-control;
  background: none;
  color: $color-text-secondary;
  cursor: pointer;

  &:hover {
    color: $color-text;
  }
}

// En monitor, con un evento elegido: tabla a la izquierda y detalle a la derecha
@include desktop {
  .events-view__body--detail {
    display: grid;
    grid-template-columns: minmax(0, 1fr) $preview-width;
    align-items: start;
    gap: $space-4;
  }
}
</style>
