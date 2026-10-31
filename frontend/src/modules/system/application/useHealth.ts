// Estado de la pestaña de salud: el panel (se actualiza solo cada 30 s mientras la pestaña está visible) y los jobs
// fallidos. Las acciones las ejecuta la pantalla con la reautenticación. Documentado en docs/frontend/system.md
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'
import { toApiError, type ApiError } from '@/shared/http'
import type { FailedJob, Health } from '../domain/system'
import { systemApi } from '../infrastructure/systemApi'

export const HEALTH_REFRESH_MS = 30_000

export function useHealth() {
  const health = shallowRef<Health | null>(null)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)
  let timer: ReturnType<typeof setInterval> | undefined

  async function load(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      health.value = await systemApi.health()
    } catch (raw) {
      error.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  // En segundo plano no se consulta: al volver a la pestaña se pide de una vez
  function refreshIfVisible(): void {
    if (document.visibilityState === 'visible' && !loading.value) void load()
  }

  onMounted(() => {
    void load()
    timer = setInterval(refreshIfVisible, HEALTH_REFRESH_MS)
    document.addEventListener('visibilitychange', refreshIfVisible)
  })
  onBeforeUnmount(() => {
    clearInterval(timer)
    document.removeEventListener('visibilitychange', refreshIfVisible)
  })

  return { health, loading, error, load }
}

export function useFailedJobs() {
  const jobs = shallowRef<FailedJob[]>([])
  const total = ref(0)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)

  async function load(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const page = await systemApi.failedJobs()
      jobs.value = page.items
      total.value = page.total
    } catch (raw) {
      error.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  return { jobs, total, loading, error, load }
}
