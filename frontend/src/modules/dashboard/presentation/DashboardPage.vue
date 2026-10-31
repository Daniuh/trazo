<!-- Inicio: el dashboard de la persona. "Resumen general" para quien ve todo y "Mi resumen" para quien ve lo suyo.
     Muestra cuándo se calcularon las cifras y permite actualizarlas. Documentado en docs/frontend/dashboard.md -->
<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { formatRelative } from '@/shared/format/dates'
import AppButton from '@/shared/ui/AppButton.vue'
import AppInfoTip from '@/shared/ui/AppInfoTip.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useDashboard } from '../application/useDashboard'
import DashboardGrid from './DashboardGrid.vue'

const dashboard = useDashboard()
const { layout, widgets, metrics, generatedAt, trendDays, loading, refreshing, error, metricsError } = dashboard

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
</script>

<template>
  <div class="dashboard">
    <header class="dashboard__header">
      <div>
        <h1 class="dashboard__title">{{ title }}</h1>
        <p v-if="updated" class="dashboard__updated">
          {{ updated }}
          <AppInfoTip
            text="Las cifras se recalculan cada vez que alguien cambia un incidente. Los vencimientos dependen de la hora, así que pueden tardar hasta un minuto en reflejarse."
            label="Cómo se actualizan las cifras"
          />
        </p>
      </div>
      <AppButton
        v-if="layout"
        variant="secondary"
        icon="refresh"
        :loading="refreshing"
        @click="dashboard.loadMetrics()"
      >
        Actualizar
      </AppButton>
    </header>

    <AppErrorState v-if="error" :error="error" @retry="dashboard.load()" />
    <AppSkeleton v-else-if="loading" :lines="8" />
    <AppEmptyState
      v-else-if="widgets.length === 0"
      title="Tu dashboard no tiene widgets visibles"
      description="Desde un computador puedes personalizarlo y agregar los que necesites."
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

.dashboard__metrics-error {
  margin-bottom: $space-2;
}

@include laptop {
  .dashboard {
    gap: $space-5;
  }
}
</style>
