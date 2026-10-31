// Indica si un cambio de sección está tardando. Solo se activa pasados ~300 ms, para no parpadear.
// Documentado en docs/frontend/sistema-de-diseno/marca.md
import { onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'

const DELAY_MS = 300

export function useRouteLoading() {
  const router = useRouter()
  const loading = ref(false)
  let timer: ReturnType<typeof setTimeout> | undefined

  const stop = () => {
    clearTimeout(timer)
    loading.value = false
  }

  const removeBefore = router.beforeEach(() => {
    clearTimeout(timer)
    timer = setTimeout(() => (loading.value = true), DELAY_MS)
  })
  const removeAfter = router.afterEach(stop)
  const removeError = router.onError(stop)

  onBeforeUnmount(() => {
    stop()
    removeBefore()
    removeAfter()
    removeError()
  })

  return { loading }
}
