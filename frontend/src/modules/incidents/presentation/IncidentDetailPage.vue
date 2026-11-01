<!-- Detalle de un incidente (ADR-0083 y ADR-0084): encabezado con la descripción, el camino, el plazo y el siguiente
     paso; abajo el historial reciente y la ficha, mitad y mitad en portátil y monitor (en celular y tablet, la ficha
     primero). El chat y toda la actividad viven en un panel aparte. Los botones salen de lo que permite la API.
     Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { useQuasar } from 'quasar'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { useNotificationsStore } from '@/modules/notifications/application/useNotificationsStore'
import { useRealtime } from '@/modules/realtime/application/realtimeBus'
import { messageFor } from '@/shared/errors'
import { ApiError } from '@/shared/http'
import AppBackLink from '@/shared/ui/AppBackLink.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useCatalogStore } from '../application/useCatalogStore'
import { useConversation } from '../application/useConversation'
import { useIncidentDetail } from '../application/useIncidentDetail'
import { listReturnLocation } from '../application/listReturn'
import AssignDialog from './components/AssignDialog.vue'
import IncidentNoteDialog from './components/IncidentNoteDialog.vue'
import ReopenRequestNotice from './components/ReopenRequestNotice.vue'
import type { ActivityPanelTab } from '../domain/activityFeed'
import IncidentActivityPanel from './detail/IncidentActivityPanel.vue'
import IncidentHeader from './detail/IncidentHeader.vue'
import IncidentHistory from './detail/IncidentHistory.vue'
import IncidentProfile from './detail/IncidentProfile.vue'
import { useNoteDialog } from './useNoteDialog'
import { useReopenDecision } from './useReopenDecision'
import { useStatusChange } from './useStatusChange'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const { confirm, notify } = useFeedback()
const session = useSessionStore()
const catalogs = useCatalogStore()
const notifications = useNotificationsStore()

const id = String(route.params.id)
const detail = useIncidentDetail(id)
const { incident, activities, activityTotal, loadError, actionError, conflict, loading, busy } = detail
const conversation = useConversation(id)
const conflictMessage = messageFor(new ApiError('http', 'VERSION_CONFLICT'))

// Panel de chat y actividad: se abre en una pestaña y, si se tocó algo del historial, en esa entrada
const panel = ref<InstanceType<typeof IncidentActivityPanel> | null>(null)
const panelOpen = ref(false)
const panelTab = ref<ActivityPanelTab>('chat')
const panelTarget = ref<string | null>(null)

function openPanel(tab: ActivityPanelTab, target: string | null = null): void {
  panelTab.value = tab
  panelTarget.value = target
  panelOpen.value = true
}
onMounted(() => Promise.all([detail.load(), catalogs.load(), conversation.refresh()]))

// Con el detalle abierto, sus mensajes cuentan como leídos en el centro de notificaciones
notifications.watchConversation(id)
onBeforeUnmount(() => notifications.watchConversation(null))

// Si otra persona lo cambia, se vuelve a leer con su historial; al reconectarse, por si se perdió un aviso
useRealtime('incident', (change) => {
  if (change.id === id && !busy.value) void detail.load()
})
useRealtime('comment', (change) => {
  if (change.id === id) void conversation.refresh()
})
useRealtime('reconnected', () => {
  void detail.load()
  void conversation.refresh()
})

// Desde un aviso de mensajes (?conversacion=1): se quita el parámetro antes de abrir (el panel se cierra si cambia la
// ruta) y se abre el chat, listo para escribir
watch(
  () => [route.query.conversacion, incident.value !== null] as const,
  async ([value, ready]) => {
    if (value !== '1' || !ready) return
    await router.replace({ query: { ...route.query, conversacion: undefined } })
    openPanel('chat')
    await nextTick()
    panel.value?.focusComposer()
  },
  { immediate: true },
)

// "Ver actividad anterior": lo que falte del historial y de los mensajes
async function loadOlder(): Promise<void> {
  await Promise.all([detail.loadAllActivities(), conversation.loadOlder()])
}

const noteDialog = useNoteDialog()
const changeStatus = useStatusChange(incident, detail.changeStatus, noteDialog.ask)

function onNoteDone(ok: boolean): void {
  noteDialog.finish(ok)
  if (!ok) detail.clearError()
}

async function requestReopen(): Promise<void> {
  const sent = await noteDialog.ask({
    title: 'Solicitar reapertura',
    message: 'Quien supervisa revisará tu solicitud. Mientras tanto, el incidente sigue como está.',
    submitLabel: 'Enviar solicitud',
    label: '¿Por qué hay que reabrirlo?',
    required: true,
    run: (reason) => detail.requestReopen(reason ?? ''),
  })
  if (sent) notify('Enviamos tu solicitud de reapertura.')
}

async function reopen(): Promise<void> {
  const done = await noteDialog.ask({
    title: 'Reabrir el incidente',
    message: 'Volverá a trabajarse y recibirá un vencimiento nuevo según su prioridad.',
    submitLabel: 'Reabrir',
    label: '¿Por qué se reabre?',
    required: true,
    run: (reason) => detail.reopen(reason ?? ''),
  })
  if (done) notify('El incidente se reabrió.')
}

const reopenDecision = useReopenDecision(detail, noteDialog)

async function take(): Promise<void> {
  if (session.user && (await detail.assign(session.user.id))) notify('Tomaste el incidente.')
}

function assign(): void {
  $q.dialog({ component: AssignDialog, componentProps: { current: incident.value?.assignee ?? null } }).onOk(
    async (personId: string | null) => {
      if (await detail.assign(personId)) notify(personId ? 'Incidente asignado.' : 'El incidente quedó sin asignar.')
    },
  )
}

async function remove(): Promise<void> {
  const ok = await confirm({
    title: '¿Eliminar este incidente?',
    message: 'Dejará de aparecer en los listados. Un supervisor puede restaurarlo después.',
    confirmLabel: 'Eliminar',
    danger: true,
  })
  if (ok && (await detail.remove())) {
    notify('Incidente eliminado.')
    await router.replace(listReturnLocation())
  }
}
</script>

<template>
  <div class="incident-detail">
    <AppBackLink :to="listReturnLocation()" label="Incidentes" />

    <AppSkeleton v-if="loading && !incident" :lines="6" />
    <AppErrorState v-else-if="loadError" :error="loadError" @retry="detail.load" />

    <template v-else-if="incident">
      <IncidentHeader
        :incident="incident"
        :catalog="catalogs.catalogs?.statuses ?? []"
        :busy="busy"
        @status="changeStatus"
        @reopen="reopen"
        @request-reopen="requestReopen"
      />

      <AppAlert v-if="conflict">
        {{ conflictMessage }}
        <AppButton variant="link" icon="refresh" @click="detail.load">Recargar</AppButton>
      </AppAlert>
      <AppAlert v-else-if="actionError && !noteDialog.open.value">{{ messageFor(actionError) }}</AppAlert>

      <ReopenRequestNotice
        v-if="incident.reopenRequest"
        :request="incident.reopenRequest"
        :can-decide="incident.actions.decideReopen"
        :busy="busy"
        @approve="reopenDecision.approve"
        @reject="reopenDecision.reject"
      />

      <div class="incident-detail__body">
        <IncidentHistory
          :activities="activities"
          :comments="conversation.comments.value"
          :total="activityTotal + conversation.total.value"
          @message="openPanel('chat', $event)"
          @note="openPanel('activity', $event)"
          @all="openPanel('activity')"
        />
        <IncidentProfile
          :incident="incident"
          :message-count="conversation.total.value"
          :busy="busy"
          @assign="assign"
          @take="take"
          @chat="openPanel('chat')"
          @edit="router.push({ name: 'incident-edit', params: { id } })"
          @remove="remove"
        />
      </div>

      <IncidentActivityPanel
        ref="panel"
        v-model:open="panelOpen"
        v-model:tab="panelTab"
        :incident="incident"
        :activities="activities"
        :activity-total="activityTotal"
        :conversation="conversation"
        :target="panelTarget"
        @load-older="loadOlder"
      />
    </template>

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

.incident-detail {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

// En celular y tablet, una columna: la ficha va antes del historial
.incident-detail__body {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-4;
}

// En portátil y monitor, historial y ficha mitad y mitad
@include laptop {
  .incident-detail__body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    align-items: start;
  }
}
</style>
