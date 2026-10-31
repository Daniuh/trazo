// Aviso antes de salir con cambios sin guardar: al cambiar de sección dentro de la app pide confirmación con el
// estilo de Trazo; al cerrar o recargar la pestaña, el navegador muestra su propio aviso (no se puede personalizar).
// Documentado en docs/frontend/sistema-de-diseno/componentes.md
import { onBeforeUnmount, onMounted, type Ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useFeedback } from './useFeedback'

export function useLeaveGuard(dirty: Ref<boolean>, message: string) {
  const { confirm } = useFeedback()

  onBeforeRouteLeave(() =>
    dirty.value
      ? confirm({ title: 'Tienes cambios sin guardar', message, confirmLabel: 'Salir sin guardar', danger: true })
      : true,
  )

  const onUnload = (event: BeforeUnloadEvent) => {
    if (dirty.value) event.preventDefault()
  }
  onMounted(() => window.addEventListener('beforeunload', onUnload))
  onBeforeUnmount(() => window.removeEventListener('beforeunload', onUnload))
}
