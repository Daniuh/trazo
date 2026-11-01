// Si un texto recortado (con line-clamp o ellipsis) se sale de su caja, para ofrecer "Ver completo" solo cuando hace
// falta. Se vuelve a medir cuando cambia el ancho. Documentado en docs/frontend/sistema-de-diseno/componentes.md
import { onBeforeUnmount, ref, watch, type Ref } from 'vue'

export function useTextOverflow(element: Ref<HTMLElement | null>) {
  const overflowing = ref(false)
  let observer: ResizeObserver | null = null

  function measure(): void {
    const box = element.value
    overflowing.value =
      box !== null && (box.scrollHeight > box.clientHeight + 1 || box.scrollWidth > box.clientWidth + 1)
  }

  watch(
    element,
    (box) => {
      observer?.disconnect()
      if (box && typeof ResizeObserver !== 'undefined') {
        observer = new ResizeObserver(measure)
        observer.observe(box)
      }
      measure()
    },
    { flush: 'post' },
  )
  onBeforeUnmount(() => observer?.disconnect())

  return { overflowing, measure }
}
