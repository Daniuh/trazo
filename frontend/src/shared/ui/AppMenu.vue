<!-- Menú que se abre desde el elemento que lo contiene (envuelve QMenu). Antes de mostrarse mide su tamaño y el espacio
     libre, y se abre donde cabe entero según menuPlacement: abajo, arriba, al lado o abajo tras bajar la página.
     Todos los menús de la app lo usan. Documentado en docs/frontend/sistema-de-diseno/componentes.md -->
<script setup lang="ts">
import { QMenu } from 'quasar'
import { nextTick, ref, shallowRef, useId } from 'vue'
import { layout } from '@/design/tokens/layout'
import { belowPlacement, placeMenu, type MenuAlign } from './menuPlacement'

defineOptions({ inheritAttrs: false })
const props = withDefaults(defineProps<{ align?: MenuAlign; maxHeight?: string }>(), {
  align: 'left',
  maxHeight: undefined,
})

const probe = ref<HTMLElement | null>(null)
const menu = ref<InstanceType<typeof QMenu> | null>(null)
const menuClass = `app-menu-${useId().replace(/[^\w-]/g, '')}`
const placement = shallowRef(belowPlacement(props.align, layout.menuGap))
// Mientras se mide, el menú existe pero no se ve: así no salta de un lugar a otro
const placing = ref(false)

// Un cuadro de dibujo; con la pestaña en segundo plano el navegador los pausa, así que hay un tope de tiempo
const FRAME_FALLBACK_MS = 50
const nextFrame = () =>
  new Promise<void>((resolve) => {
    requestAnimationFrame(() => resolve())
    setTimeout(resolve, FRAME_FALLBACK_MS)
  })

function scrollParentOf(element: HTMLElement): Element {
  for (let node = element.parentElement; node; node = node.parentElement) {
    const { overflowY } = getComputedStyle(node)
    if ((overflowY === 'auto' || overflowY === 'scroll') && node.scrollHeight > node.clientHeight) return node
  }
  return document.scrollingElement ?? document.documentElement
}

async function place(): Promise<void> {
  placement.value = belowPlacement(props.align, layout.menuGap)
  placing.value = true
  await nextTick()
  await nextFrame()

  // El botón entero, como lo toma QMenu (dentro de un botón de Quasar el padre directo es solo su contenido)
  const parent = probe.value?.parentElement
  const trigger = parent?.closest<HTMLElement>('.q-btn') ?? parent
  const content = document.querySelector<HTMLElement>(`.${menuClass}`)
  if (trigger && content) {
    // Alto natural: QMenu pudo haberlo recortado; el borde se suma aparte
    const height = content.scrollHeight + (content.offsetHeight - content.clientHeight)
    const { clientWidth, clientHeight } = document.documentElement
    const next = placeMenu({
      trigger: trigger.getBoundingClientRect(),
      menu: { width: content.offsetWidth, height },
      viewport: { width: clientWidth, height: clientHeight },
      align: props.align,
      gap: layout.menuGap,
      margin: layout.menuViewportMargin,
    })
    if (next.scrollBy > 0) scrollParentOf(trigger).scrollBy({ top: next.scrollBy, behavior: 'instant' })
    placement.value = next
    await nextTick()
    menu.value?.updatePosition()
    await nextFrame()
  }
  placing.value = false
}
</script>

<template>
  <span ref="probe" class="app-menu__probe" aria-hidden="true" />
  <QMenu
    ref="menu"
    v-bind="$attrs"
    :class="menuClass"
    :style="placing ? { opacity: 0 } : undefined"
    :anchor="placement.anchor"
    :self="placement.self"
    :offset="placement.offset"
    :max-height="maxHeight"
    @before-show="place"
  >
    <slot />
  </QMenu>
</template>

<style scoped lang="scss">
.app-menu__probe {
  display: none;
}
</style>
