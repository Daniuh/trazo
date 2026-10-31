// Estado del dashboard: el diseño de la persona y los datos de sus widgets visibles, en una sola consulta.
// Al volver a la pestaña, si los datos tienen más de un minuto, se actualizan solos. Documentado en docs/frontend/dashboard.md
import { computed, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue'
import { ApiError, toApiError } from '@/shared/http'
import { readingOrder, type DashboardLayout, type Metrics, type TrendDays, type WidgetCode } from '../domain/dashboard'
import { dashboardApi } from '../infrastructure/dashboardApi'

// Coincide con lo que la caché del servidor guarda una cifra
const STALE_AFTER_MS = 60_000

export function useDashboard() {
  const layout = shallowRef<DashboardLayout | null>(null)
  const metrics = shallowRef<Metrics>({})
  const generatedAt = ref<string | null>(null)
  const trendDays = ref<TrendDays>(30)
  const loading = ref(true)
  const refreshing = ref(false)
  const error = ref<ApiError | null>(null)
  const metricsError = ref<ApiError | null>(null)
  // Widgets del borrador mientras se personaliza; null fuera del modo edición
  const draftCodes = shallowRef<WidgetCode[] | null>(null)
  let current: AbortController | null = null

  const widgets = computed(() => (layout.value ? readingOrder(layout.value.widgets) : []))

  async function load(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      layout.value = await dashboardApi.layout()
      await loadMetrics()
    } catch (raw) {
      error.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  async function loadMetrics(): Promise<void> {
    const codes = draftCodes.value ?? widgets.value.map((widget) => widget.code)
    if (codes.length === 0) {
      metrics.value = {}
      return
    }

    current?.abort()
    const controller = new AbortController()
    current = controller
    refreshing.value = true
    metricsError.value = null
    try {
      const page = await dashboardApi.metrics(codes, trendDays.value, controller.signal)
      metrics.value = page.metrics
      generatedAt.value = page.generatedAt
    } catch (raw) {
      const failure = toApiError(raw)
      if (failure.kind !== 'canceled') metricsError.value = failure
    } finally {
      if (current === controller) refreshing.value = false
    }
  }

  // Mientras se personaliza, las cifras siguen al borrador: así un widget recién agregado muestra sus datos
  function followDraft(codes: WidgetCode[] | null): void {
    const missing = codes?.some((code) => metrics.value[code] === undefined) ?? false
    draftCodes.value = codes
    if (missing) void loadMetrics()
  }

  // Tras guardar o restaurar, el diseño nuevo manda y se piden las cifras de lo que quedó visible
  function applyLayout(next: DashboardLayout): void {
    layout.value = next
    draftCodes.value = null
    void loadMetrics()
  }

  function onVisible(): void {
    const age = generatedAt.value ? Date.now() - new Date(generatedAt.value).getTime() : Infinity
    if (document.visibilityState === 'visible' && age > STALE_AFTER_MS && !refreshing.value) void loadMetrics()
  }

  watch(trendDays, () => void loadMetrics())
  onMounted(() => {
    void load()
    document.addEventListener('visibilitychange', onVisible)
  })
  onBeforeUnmount(() => {
    current?.abort()
    document.removeEventListener('visibilitychange', onVisible)
  })

  return {
    layout,
    widgets,
    metrics,
    generatedAt,
    trendDays,
    loading,
    refreshing,
    error,
    metricsError,
    load,
    loadMetrics,
    followDraft,
    applyLayout,
  }
}
