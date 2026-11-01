<!-- "Mis incidentes": lo asignado a la persona, en Kanban, Lista, Calendario o Mi día. Mover una tarjeta usa el mismo
     diálogo del detalle (nota obligatoria en Resuelto y Cancelado, aviso en los estados finales). Se actualiza solo con
     los cambios de otras personas. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRealtime } from '@/modules/realtime/application/realtimeBus'
import { messageFor } from '@/shared/errors'
import { ApiError } from '@/shared/http'
import AppBackLink from '@/shared/ui/AppBackLink.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import AppSegmented from '@/shared/ui/form/AppSegmented.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { listReturnLocation } from '../application/listReturn'
import { useBoardView } from '../application/useBoardView'
import { useCatalogStore } from '../application/useCatalogStore'
import { useMyIncidents } from '../application/useMyIncidents'
import { BOARD_VIEWS, boardColumns } from '../domain/board'
import type { Incident } from '../domain/incident'
import IncidentNoteDialog from './components/IncidentNoteDialog.vue'
import KanbanBoard from './board/KanbanBoard.vue'
import MyCalendar from './board/MyCalendar.vue'
import MyDay from './board/MyDay.vue'
import MyIncidentsList from './board/MyIncidentsList.vue'
import { useNoteDialog } from './useNoteDialog'
import { useStatusChange } from './useStatusChange'

const catalogs = useCatalogStore()
const mine = useMyIncidents()
const { board, loading, loadError, actionError, conflict, moving } = mine
const view = useBoardView()
const { isLaptopUp } = useViewport()

onMounted(() => Promise.all([mine.load(), catalogs.load()]))

const statuses = computed(() => catalogs.catalogs?.statuses ?? [])
const items = computed(() => board.value?.items ?? [])
const columns = computed(() => boardColumns(statuses.value, items.value))
const statusOrder = computed(() => statuses.value.map((status) => status.code))
const help = computed(
  () =>
    `Los incidentes que tienes asignados y siguen en curso, más los cerrados o cancelados de los últimos ${board.value?.finishedDays ?? 7} días.`,
)
const conflictMessage = messageFor(new ApiError('http', 'VERSION_CONFLICT'))

// Mover una tarjeta: el mismo cambio de estado del detalle, sobre la tarjeta elegida
const current = ref<Incident | null>(null)
const noteDialog = useNoteDialog()
const changeStatus = useStatusChange(
  current,
  (code, note) => (current.value ? mine.move(current.value, code, note) : Promise.resolve(false)),
  noteDialog.ask,
)

async function move(item: Incident, code: string): Promise<void> {
  current.value = item
  await changeStatus(code)
}

function onNoteDone(ok: boolean): void {
  noteDialog.finish(ok)
  // Si otra persona lo cambió antes, el aviso queda arriba y el tablero se vuelve a leer
  if (!ok && !conflict.value) mine.clearError()
  if (conflict.value) void mine.load()
}

// Cambios de otras personas: el tablero completo es una sola consulta; se espera un momento para juntar varios avisos
const REFRESH_DELAY_MS = 1500
let pending: ReturnType<typeof setTimeout> | undefined
function refreshSoon(): void {
  clearTimeout(pending)
  pending = setTimeout(() => void mine.load(), REFRESH_DELAY_MS)
}
useRealtime('incident', refreshSoon)
useRealtime('reconnected', () => void mine.load())
</script>

<template>
  <div class="my-incidents" :class="{ 'my-incidents--fill': view === 'kanban' || view === 'dia' }">
    <AppBackLink :to="listReturnLocation()" label="Incidentes" />

    <header class="my-incidents__header">
      <div class="my-incidents__title">
        <h1 class="my-incidents__heading">Mis incidentes</h1>
        <AppInfoTip :text="help" label="Qué incidentes aparecen aquí" />
      </div>
      <AppSegmented v-model="view" label="Vista" :options="BOARD_VIEWS" />
    </header>

    <AppAlert v-if="board?.truncated" tone="info">
      Tienes más de {{ board.limit }} incidentes asignados; aquí se ven los {{ board.limit }} que vencen primero.
    </AppAlert>
    <AppAlert v-if="conflict">
      {{ conflictMessage }}
      <AppButton variant="link" icon="refresh" @click="mine.load">Recargar</AppButton>
    </AppAlert>

    <section class="my-incidents__body" :aria-busy="loading" aria-label="Mis incidentes">
      <AppErrorState v-if="loadError && !board" :error="loadError" @retry="mine.load" />
      <AppSkeleton v-else-if="!board || statuses.length === 0" :lines="8" />
      <AppEmptyState
        v-else-if="items.length === 0"
        title="No tienes incidentes asignados"
        description="Cuando te asignen uno o tomes uno sin asignar, aparecerá aquí."
      >
        <AppButton variant="secondary" :to="{ name: 'incidents' }">Ver incidentes</AppButton>
      </AppEmptyState>
      <KanbanBoard
        v-else-if="view === 'kanban'"
        :columns="columns"
        :moving-id="moving"
        :drag-enabled="isLaptopUp"
        @move="move"
      />
      <MyIncidentsList v-else-if="view === 'lista'" :items="items" :status-order="statusOrder" />
      <MyCalendar v-else-if="view === 'calendario'" :items="items" />
      <MyDay v-else :items="items" />
    </section>

    <IncidentNoteDialog
      v-model="noteDialog.open.value"
      :request="noteDialog.request.value"
      :failure="actionError"
      :conflict="conflict"
      @done="onNoteDone"
    />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.my-incidents,
.my-incidents__body {
  display: flex;
  flex-direction: column;
  gap: $space-4;
  min-width: 0;
}

.my-incidents__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.my-incidents__title {
  display: flex;
  align-items: center;
  gap: $space-2;
}

.my-incidents__heading {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

@include tablet {
  .my-incidents__heading {
    font-size: $font-size-2xl;
  }
}

// Kanban y Mi día en portátil y monitor: la página ocupa justo el alto disponible (pantalla menos el margen de arriba y
// el espacio del dock), así no aparece la barra de la página; solo se desplazan las columnas
@include laptop {
  .my-incidents--fill {
    height: calc(100dvh - #{$space-6} - #{$dock-clearance});
  }

  .my-incidents--fill .my-incidents__body {
    flex: 1;
    min-height: 0;
  }
}
</style>
