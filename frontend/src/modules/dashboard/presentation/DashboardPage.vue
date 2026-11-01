<!-- Inicio: el dashboard de la persona. "Resumen general" para quien ve todo y "Mi resumen" para quien ve lo suyo.
     Muestra cuándo se calcularon las cifras y permite actualizarlas. En portátil y monitor se puede personalizar;
     en tablet y celular los widgets siguen el orden elegido allí. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed, defineAsyncComponent, h, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { formatRelative } from '@/shared/format/dates'
import AppButton from '@/shared/ui/AppButton.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppAlert from '@/shared/ui/feedback/AppAlert.vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { useLeaveGuard } from '@/shared/ui/feedback/useLeaveGuard'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { messageFor } from '@/shared/errors'
import { useDashboard } from '../application/useDashboard'
import { useDashboardEditor } from '../application/useDashboardEditor'
import type { WidgetCode } from '../domain/dashboard'
import DashboardGrid from './DashboardGrid.vue'
import EditToolbar from './edit/EditToolbar.vue'

// La grilla editable (y su librería de arrastre) se descarga solo al pulsar "Personalizar"
const DashboardEditor = defineAsyncComponent({
  loader: () => import('./edit/DashboardEditor.vue'),
  loadingComponent: AppSkeleton,
  delay: 300,
  errorComponent: () =>
    h(AppAlert, () => 'No pudimos abrir el editor. Revisa tu conexión, cancela y vuelve a pulsar “Personalizar”.'),
})

const dashboard = useDashboard()
const { layout, widgets, metrics, generatedAt, trendDays, loading, refreshing, error, metricsError } = dashboard
const editor = useDashboardEditor(layout, dashboard.applyLayout)
const { editing, dirty } = editor
const { isLaptopUp } = useViewport()
const { confirm, notify } = useFeedback()

// Hasta saber el alcance, el título neutro de la sección
const title = computed(() => {
  if (!layout.value) return 'Inicio'
  return layout.value.scope === 'general' ? 'Resumen general' : 'Mi resumen'
})

// "Actualizado hace 2 minutos" se mantiene al día sin volver a pedir datos
const now = ref(new Date())
let clock: ReturnType<typeof setInterval> | undefined
onMounted(() => (clock = setInterval(() => (now.value = new Date()), 30_000)))
onBeforeUnmount(() => clearInterval(clock))
const updated = computed(() => (generatedAt.value ? `Actualizado ${formatRelative(generatedAt.value, now.value)}` : ''))

// Las cifras siguen al borrador: un widget recién agregado trae sus datos
watch(
  () => (editing.value ? editor.visible.value.map((widget) => widget.code) : null),
  (codes) => dashboard.followDraft(codes),
)

useLeaveGuard(dirty, 'Si sales ahora, los cambios a tu inicio se pierden.')

async function cancel(): Promise<void> {
  const discard =
    !dirty.value ||
    (await confirm({
      title: 'Descartar los cambios',
      message: 'Tu inicio vuelve a quedar como estaba antes de personalizarlo.',
      confirmLabel: 'Descartar',
      danger: true,
    }))
  if (discard) editor.cancel()
}

async function restore(): Promise<void> {
  const accepted = await confirm({
    title: 'Restaurar el diseño original',
    message:
      'Tu diseño se reemplazará por el original, con todos los widgets disponibles en su lugar y tamaño iniciales.',
    confirmLabel: 'Restaurar',
    danger: true,
  })
  if (accepted && (await editor.restore())) notify('Tu inicio volvió al diseño original.')
}

function add(code: WidgetCode): void {
  editor.add(code)
  const name = editor.visible.value.find((widget) => widget.code === code)?.name
  if (name) notify(`“${name}” quedó al final de tu inicio.`)
}

async function save(): Promise<void> {
  if (await editor.save()) notify('Los cambios de tu inicio quedaron guardados.')
}
</script>

<template>
  <div class="dashboard">
    <template v-if="editing">
      <EditToolbar
        :hidden="editor.hidden.value"
        :dirty="dirty"
        :saving="editor.saving.value"
        :restoring="editor.restoring.value"
        @add="add"
        @restore="restore"
        @cancel="cancel"
        @save="save"
      />
      <AppAlert v-if="editor.error.value">{{ messageFor(editor.error.value) }}</AppAlert>
      <AppAlert v-if="!isLaptopUp" tone="info">
        Agranda la ventana para seguir personalizando. Tus cambios se conservan mientras tanto.
      </AppAlert>
      <template v-else>
        <p v-if="editor.visible.value.length === 0" class="dashboard__hint">
          Tu inicio está vacío. Usa "Agregar widget" para elegir qué ver.
        </p>
        <DashboardEditor v-model:trend-days="trendDays" :editor="editor" :metrics="metrics" />
      </template>
    </template>

    <template v-else>
      <header class="dashboard__header">
        <div class="dashboard__heading">
          <h1 class="dashboard__title">
            {{ title }}
            <AppInfoTip
              v-if="layout && !isLaptopUp"
              text="Puedes personalizar tu inicio desde un computador. Aquí los widgets siguen el orden que elijas allí."
              label="Cómo personalizar el inicio"
            />
          </h1>
          <p v-if="updated" class="dashboard__updated">
            {{ updated }}
            <AppInfoTip
              text="Las cifras se recalculan cada vez que alguien cambia un incidente. Los vencimientos dependen de la hora, así que pueden tardar hasta un minuto en reflejarse."
              label="Cómo se actualizan las cifras"
            />
          </p>
        </div>
        <div v-if="layout" class="dashboard__actions">
          <AppButton v-if="isLaptopUp" variant="secondary" icon="edit" @click="editor.start()">Personalizar</AppButton>
          <AppButton variant="secondary" icon="refresh" :loading="refreshing" @click="dashboard.loadMetrics()">
            Actualizar
          </AppButton>
        </div>
      </header>

      <AppErrorState v-if="error" :error="error" @retry="dashboard.load()" />
      <AppSkeleton v-else-if="loading" :lines="8" />
      <AppEmptyState
        v-else-if="widgets.length === 0"
        title="Tu dashboard no tiene widgets visibles"
        :description="
          isLaptopUp
            ? 'Usa “Personalizar” para agregar los que necesites.'
            : 'Desde un computador puedes personalizarlo y agregar los que necesites.'
        "
      />
      <template v-else>
        <AppErrorState
          v-if="metricsError"
          :error="metricsError"
          class="dashboard__metrics-error"
          @retry="dashboard.loadMetrics()"
        />
        <DashboardGrid v-model:trend-days="trendDays" :widgets="widgets" :metrics="metrics" />
      </template>
    </template>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.dashboard {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.dashboard__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-3;
}

.dashboard__title {
  display: flex;
  align-items: center;
  gap: $space-2;
  margin: 0;
  font-size: $font-size-2xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.dashboard__updated {
  display: flex;
  align-items: center;
  gap: $space-1;
  margin: $space-1 0 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

.dashboard__actions {
  display: flex;
  flex-wrap: wrap;
  gap: $space-2;
}

.dashboard__metrics-error {
  margin-bottom: $space-2;
}

.dashboard__hint {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-sm;
}

// Desde tablet, "Actualizado hace…" va junto al título, en la misma fila que los botones, para ahorrar alto
@include tablet {
  .dashboard__header {
    align-items: center;
  }

  .dashboard__heading {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    column-gap: $space-4;
  }

  .dashboard__updated {
    margin: 0;
  }
}

@include laptop {
  .dashboard {
    gap: $space-5;
  }
}
</style>
