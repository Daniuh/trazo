// Cuántas filas de una lista caben en el alto de su contenedor, para mostrar solo esas y resumir el resto ("Y N más").
// El contenedor necesita un alto que no dependa de su contenido (p. ej. su tarjeta con `contain: size`); si no, crecería
// con lo que se muestra. Documentado en docs/frontend/sistema-de-diseno/componentes.md
import { onBeforeUnmount, ref, watch, type Ref } from 'vue'

// Filas completas que caben, contando la separación entre ellas
export function rowsThatFit(available: number, rowHeight: number, gap: number): number {
  if (rowHeight <= 0) return 0
  return Math.max(0, Math.floor((available + gap) / (rowHeight + gap)))
}

// `fit` es null mientras no se mide o si `enabled` está apagado: en ese caso se muestra la lista normal
export function useFitCount(container: Ref<HTMLElement | null>, enabled: Ref<boolean>) {
  const fit = ref<number | null>(null)
  let rowHeight = 0
  let observer: ResizeObserver | null = null

  function measure(): void {
    const list = container.value
    if (!list || !enabled.value) {
      fit.value = null
      return
    }
    // Se recuerda el alto de una fila por si en algún momento no se muestra ninguna
    const first = list.firstElementChild
    if (first) rowHeight = first.getBoundingClientRect().height
    const gap = Number.parseFloat(getComputedStyle(list).rowGap) || 0
    fit.value = rowHeight > 0 ? rowsThatFit(list.clientHeight, rowHeight, gap) : null
  }

  watch(
    [container, enabled],
    ([list]) => {
      observer?.disconnect()
      if (list && typeof ResizeObserver !== 'undefined') {
        observer = new ResizeObserver(measure)
        observer.observe(list)
      }
      measure()
    },
    { flush: 'post' },
  )
  onBeforeUnmount(() => observer?.disconnect())

  return { fit }
}
