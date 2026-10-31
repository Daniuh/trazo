<!-- Detalle de un incidente. Portátil y monitor: descripción e historial a la izquierda y datos a la derecha.
     Celular y tablet: una sola columna. Los botones salen de lo que permite la API. Documentado en docs/frontend/incidents.md -->
<script setup lang="ts">
import { useQuasar } from 'quasar'
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { messageFor } from '@/shared/errors'
import { ApiError } from '@/shared/http'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCard from '@/shared/ui/AppCard.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useCatalogStore } from '../application/useCatalogStore'
import { useIncidentDetail } from '../application/useIncidentDetail'
import AssignDialog from './components/AssignDialog.vue'
import IncidentActions from './components/IncidentActions.vue'
import IncidentBadges from './components/IncidentBadges.vue'
import IncidentFacts from './components/IncidentFacts.vue'
import IncidentHistory from './components/IncidentHistory.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const { confirm, notify } = useFeedback()
const session = useSessionStore()
const catalogs = useCatalogStore()

const id = String(route.params.id)
const detail = useIncidentDetail(id)
const { incident, activities, loadError, actionError, conflict, loading, busy } = detail
const conflictMessage = messageFor(new ApiError('http', 'VERSION_CONFLICT'))

onMounted(() => Promise.all([detail.load(), catalogs.load()]))

// Cerrar o cancelar no tiene vuelta atrás: se confirma antes
async function changeStatus(code: string): Promise<void> {
  const target = incident.value?.nextStatuses.find((status) => status.code === code)
  if (!target) return
  if (catalogs.isFinalStatus(code)) {
    const ok = await confirm({
      title: `¿Pasar a "${target.name}"?`,
      message: 'Después de este cambio nadie podrá editar el incidente ni cambiar su estado.',
      confirmLabel: `Pasar a ${target.name}`,
      danger: code === 'cancelled',
    })
    if (!ok) return
  }
  if (await detail.changeStatus(code)) notify(`El incidente pasó a "${target.name}".`)
}

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
    await router.replace({ name: 'home' })
  }
}
</script>

<template>
  <div class="incident-detail">
    <AppSkeleton v-if="loading && !incident" :lines="6" />
    <AppErrorState v-else-if="loadError" :error="loadError" @retry="detail.load" />

    <template v-else-if="incident">
      <header class="incident-detail__header">
        <div class="incident-detail__heading">
          <p class="incident-detail__code">{{ incident.code }}</p>
          <h1 class="incident-detail__title">{{ incident.title }}</h1>
          <IncidentBadges :incident="incident" />
        </div>
        <IncidentActions
          :incident="incident"
          :busy="busy"
          @status="changeStatus"
          @take="take"
          @assign="assign"
          @edit="router.push({ name: 'incident-edit', params: { id } })"
          @remove="remove"
        />
      </header>

      <AppAlert v-if="conflict">
        {{ conflictMessage }}
        <AppButton variant="link" icon="refresh" @click="detail.load">Recargar</AppButton>
      </AppAlert>
      <AppAlert v-else-if="actionError">{{ messageFor(actionError) }}</AppAlert>

      <div class="incident-detail__body">
        <div class="incident-detail__main">
          <AppCard title="Descripción">
            <p class="incident-detail__description">{{ incident.description }}</p>
          </AppCard>
          <IncidentHistory :activities="activities" />
        </div>
        <IncidentFacts class="incident-detail__side" :incident="incident" />
      </div>
    </template>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.incident-detail,
.incident-detail__main {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.incident-detail__header {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.incident-detail__heading {
  display: flex;
  flex-direction: column;
  gap: $space-2;
}

.incident-detail__code {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.incident-detail__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
  overflow-wrap: anywhere;
}

// La descripción respeta los saltos de línea tal como se escribieron
.incident-detail__description {
  margin: 0;
  white-space: pre-line;
  overflow-wrap: anywhere;
}

// En celular los datos van antes de la descripción, como en el diseño aprobado
.incident-detail__body {
  display: flex;
  flex-direction: column-reverse;
  gap: $space-4;
}

@include laptop {
  .incident-detail__header {
    flex-direction: row;
    align-items: flex-start;
    justify-content: space-between;
  }

  .incident-detail__title {
    font-size: $font-size-2xl;
  }

  .incident-detail__body {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    align-items: start;
  }
}
</style>
