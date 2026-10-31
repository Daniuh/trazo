// Vista actual (celular, tablet, portátil o monitor). Usa los breakpoints de Quasar, que coinciden con los tokens.
// Documentado en docs/frontend/sistema-de-diseno/responsive.md
import { useQuasar } from 'quasar'
import { computed } from 'vue'

export type Viewport = 'mobile' | 'tablet' | 'laptop' | 'desktop'

export function useViewport() {
  const { screen } = useQuasar()

  const viewport = computed<Viewport>(() => {
    if (screen.lt.sm) return 'mobile'
    if (screen.sm) return 'tablet'
    if (screen.md) return 'laptop'
    return 'desktop'
  })

  return {
    viewport,
    isMobile: computed(() => viewport.value === 'mobile'),
    isTablet: computed(() => viewport.value === 'tablet'),
    // Portátil o monitor: hay espacio para textos que en pantallas chicas van solo como ícono
    isLaptopUp: computed(() => viewport.value === 'laptop' || viewport.value === 'desktop'),
  }
}
