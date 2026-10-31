<!-- Pestaña de salud: una tarjeta por componente con su estado, el modo mantenimiento y la app. Las acciones van en la
     tarjeta a la que corresponden y, como cualquier acción de sistema, piden el código de la aplicación en un diálogo
     que también sirve de confirmación. Se actualiza sola cada 30 s. Documentado en docs/frontend/system.md -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { formatDateTime } from '@/shared/format/dates'
import AppButton from '@/shared/ui/AppButton.vue'
import AppCodeDialog from '@/shared/ui/feedback/AppCodeDialog.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { useReauth, type ReauthTexts } from '@/shared/ui/feedback/useReauth'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useFailedJobs, useHealth } from '../../application/useHealth'
import type { HealthComponent, SystemAction } from '../../domain/system'
import { systemApi } from '../../infrastructure/systemApi'
import { COMPONENTS, componentDetails, HEALTH_LABEL, HEALTH_TONE } from '../systemTexts'
import FailedJobsSheet from './FailedJobsSheet.vue'
import HealthCard from './HealthCard.vue'

const CODE_HINT = 'Escribe el código de tu aplicación de autenticación para confirmar.'

const ACTIONS: Record<SystemAction, ReauthTexts & { done: string }> = {
  'restart-workers': {
    title: 'Reiniciar workers',
    message: `Terminan el trabajo en curso y Docker los vuelve a levantar en unos segundos. Ningún job se pierde. ${CODE_HINT}`,
    confirmLabel: 'Reiniciar',
    done: 'Workers reiniciados. Vuelven a procesar la cola en unos segundos.',
  },
  'restart-realtime': {
    title: 'Reiniciar el tiempo real',
    message: `Las pantallas abiertas se desconectan un momento y se reconectan solas. ${CODE_HINT}`,
    confirmLabel: 'Reiniciar',
    done: 'Tiempo real reiniciado. Las pantallas se reconectan solas.',
  },
  'clear-cache': {
    title: 'Limpiar la caché de datos',
    message: `Se borran las cifras guardadas del dashboard y se calculan de nuevo al pedirlas. Los bloqueos de seguridad y las sesiones no se tocan. ${CODE_HINT}`,
    confirmLabel: 'Limpiar',
    done: 'Caché de datos limpiada.',
  },
}

// Qué acción va en la tarjeta de cada componente
const CARD_ACTION: Partial<Record<HealthComponent['code'], { action: SystemAction; label: string }>> = {
  worker: { action: 'restart-workers', label: 'Reiniciar workers' },
  realtime: { action: 'restart-realtime', label: 'Reiniciar' },
  redis: { action: 'clear-cache', label: 'Limpiar caché de datos' },
}

const session = useSessionStore()
const { notify } = useFeedback()
const { dialog, run } = useReauth()
const { health, loading, error, load } = useHealth()
const failed = useFailedJobs()

const canAct = computed(() => session.can('system.actions.execute'))
const jobsOpen = ref(false)
const busy = ref(false)

const checkedAt = computed(() =>
  health.value ? new Date(health.value.checkedAt).toLocaleTimeString('es-CO', { hour12: false }) : '',
)
const maintenanceDetail = computed(() => {
  const state = health.value?.maintenance
  return state
    ? [
        `Desde ${formatDateTime(state.since)}, lo activó ${state.enabledBy}.`,
        'Las demás personas ven el aviso de mantenimiento.',
      ]
    : ['Todas las personas pueden usar Trazo.']
})

// Ejecuta una acción con su código; si la persona cierra el diálogo, no pasa nada. Los botones se bloquean solo
// mientras va la petición: si quedaran bloqueados con el diálogo abierto, el foco no podría volver al que lo abrió
async function act(attempt: (code?: string) => Promise<unknown>, texts: ReauthTexts, done: string): Promise<void> {
  const tracked = async (code?: string) => {
    busy.value = true
    try {
      await attempt(code)
    } finally {
      busy.value = false
    }
  }
  if (await run(tracked, texts, true)) {
    notify(done)
    void load()
    if (jobsOpen.value) void failed.load()
  }
}

function runAction(action: SystemAction): void {
  void act((code) => systemApi.run(action, code), ACTIONS[action], ACTIONS[action].done)
}

function runCardAction(code: HealthComponent['code']): void {
  const card = CARD_ACTION[code]
  if (card) runAction(card.action)
}

function toggleMaintenance(): void {
  const enable = !health.value?.maintenance
  const texts: ReauthTexts = enable
    ? {
        title: 'Activar el modo mantenimiento',
        message: `Las demás personas verán "Trazo está en mantenimiento" hasta que lo desactives; quienes administran el sistema siguen trabajando. ${CODE_HINT}`,
        confirmLabel: 'Activar',
        danger: true,
      }
    : {
        title: 'Desactivar el modo mantenimiento',
        message: `Todas las personas vuelven a usar Trazo. ${CODE_HINT}`,
        confirmLabel: 'Desactivar',
      }
  void act(
    (code) => systemApi.setMaintenance(enable, code),
    texts,
    enable ? 'Modo mantenimiento activado.' : 'Modo mantenimiento desactivado.',
  )
}

function openJobs(): void {
  jobsOpen.value = true
  void failed.load()
}

function retryJobs(ids: string[] | null): void {
  const count = ids === null ? failed.total.value : ids.length
  void act(
    (code) => systemApi.retryJobs(ids, code),
    {
      title: count === 1 ? 'Reintentar el job' : `Reintentar ${count} jobs`,
      message: `Vuelven a la cola y un worker los procesa de nuevo. ${CODE_HINT}`,
      confirmLabel: 'Reintentar',
    },
    count === 1 ? 'Job reintentado.' : 'Jobs reintentados.',
  )
}

function discardJob(id: string): void {
  void act(
    (code) => systemApi.discardJob(id, code),
    {
      title: 'Descartar el job',
      message: `Se borra de la lista sin volver a ejecutarse. ${CODE_HINT}`,
      confirmLabel: 'Descartar',
      danger: true,
    },
    'Job descartado.',
  )
}
</script>

<template>
  <section class="health-view" :aria-busy="loading" aria-label="Salud del sistema">
    <AppErrorState v-if="error && !health" :error="error" @retry="load" />
    <AppSkeleton v-else-if="!health" :lines="8" />

    <div v-else class="health-view__grid">
      <HealthCard
        title="Estado general"
        :status="HEALTH_LABEL[health.status]"
        :tone="HEALTH_TONE[health.status]"
        :details="[
          `Entorno ${health.app.environment} · Laravel ${health.app.laravel} · PHP ${health.app.php}`,
          `Revisado a las ${checkedAt}. Se actualiza solo cada 30 segundos.`,
        ]"
      >
        <AppButton variant="secondary" icon="refresh" :loading="loading" @click="load">Actualizar</AppButton>
      </HealthCard>

      <HealthCard
        title="Modo mantenimiento"
        help="Con el modo activo, solo quienes administran el sistema pueden usar Trazo; las demás personas ven un aviso."
        :status="health.maintenance ? 'Activo' : 'Apagado'"
        :tone="health.maintenance ? 'orange' : 'neutral'"
        :details="maintenanceDetail"
      >
        <AppButton
          v-if="canAct"
          :variant="health.maintenance ? 'secondary' : 'danger'"
          :disabled="busy"
          @click="toggleMaintenance"
        >
          {{ health.maintenance ? 'Desactivar' : 'Activar' }}
        </AppButton>
      </HealthCard>

      <HealthCard
        v-for="component in health.components"
        :key="component.code"
        :title="COMPONENTS[component.code].title"
        :help="COMPONENTS[component.code].help"
        :status="HEALTH_LABEL[component.status]"
        :tone="HEALTH_TONE[component.status]"
        :details="componentDetails(component)"
      >
        <AppButton v-if="component.code === 'queue'" variant="secondary" @click="openJobs">
          Ver jobs fallidos
        </AppButton>
        <AppButton
          v-if="canAct && CARD_ACTION[component.code]"
          variant="secondary"
          :disabled="busy"
          @click="runCardAction(component.code)"
        >
          {{ CARD_ACTION[component.code]?.label }}
        </AppButton>
      </HealthCard>
    </div>

    <FailedJobsSheet
      v-model:open="jobsOpen"
      :jobs="failed.jobs.value"
      :total="failed.total.value"
      :loading="failed.loading.value"
      :error="failed.error.value"
      :can-act="canAct"
      :busy="busy"
      @retry="retryJobs"
      @discard="discardJob"
      @reload="failed.load"
    />

    <AppCodeDialog
      v-model="dialog.open"
      :title="dialog.title"
      :message="dialog.message"
      :confirm-label="dialog.confirmLabel"
      :danger="dialog.danger"
      :action="dialog.action"
    />
  </section>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.health-view__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: $space-3;
}

@include tablet {
  .health-view__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@include laptop {
  .health-view__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: $space-4;
  }
}
</style>
