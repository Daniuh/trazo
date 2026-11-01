// Dónde se abre un menú según el espacio que hay alrededor de su botón: abajo si cabe, si no arriba, si no al lado
// (izquierda o derecha, donde haya más espacio) y, si nada alcanza, abajo después de bajar la página lo justo.
// Sin dependencias de la interfaz: AppMenu mide y aplica. Documentado en docs/frontend/sistema-de-diseno/componentes.md

export interface Box {
  top: number
  bottom: number
  left: number
  right: number
}

export interface Size {
  width: number
  height: number
}

export type MenuAlign = 'left' | 'right'
export type MenuPosition = `${'top' | 'bottom'} ${MenuAlign}`

// Las posiciones con el formato de QMenu ("bottom left"); offset en px como lo entiende QMenu
export interface MenuPlacement {
  anchor: MenuPosition
  self: MenuPosition
  offset: [number, number]
  // Cuánto bajar la página antes de abrir (0 si no hace falta)
  scrollBy: number
}

export interface PlacementInput {
  trigger: Box
  menu: Size
  viewport: Size
  align: MenuAlign
  // Separación entre el botón y el menú, y margen mínimo con los bordes de la ventana
  gap: number
  margin: number
}

export function belowPlacement(align: MenuAlign, gap: number): MenuPlacement {
  return { anchor: `bottom ${align}`, self: `top ${align}`, offset: [0, gap], scrollBy: 0 }
}

export function placeMenu({ trigger, menu, viewport, align, gap, margin }: PlacementInput): MenuPlacement {
  const below = belowPlacement(align, gap)
  if (viewport.height - margin - trigger.bottom - gap >= menu.height) return below
  if (trigger.top - gap - margin >= menu.height) {
    return { anchor: `top ${align}`, self: `bottom ${align}`, offset: [0, gap], scrollBy: 0 }
  }

  // Al lado: arranca a la altura del botón y sube lo necesario para quedar entero dentro de la ventana
  if (menu.height <= viewport.height - 2 * margin) {
    const roomRight = viewport.width - margin - trigger.right - gap
    const roomLeft = trigger.left - gap - margin
    if (Math.max(roomRight, roomLeft) >= menu.width) {
      const top = Math.max(margin, Math.min(trigger.top, viewport.height - margin - menu.height))
      // QMenu suma el offset hacia afuera del borde superior del botón: positivo sube el menú
      const offset: [number, number] = [gap, trigger.top - top]
      return roomRight >= roomLeft
        ? { anchor: 'top right', self: 'top left', offset, scrollBy: 0 }
        : { anchor: 'top left', self: 'top right', offset, scrollBy: 0 }
    }
  }

  // Nada alcanza: se baja la página lo justo para abrir hacia abajo, sin que el botón salga por arriba
  const needed = trigger.bottom + gap + menu.height + margin - viewport.height
  return { ...below, scrollBy: Math.max(0, Math.min(needed, trigger.top - margin)) }
}
