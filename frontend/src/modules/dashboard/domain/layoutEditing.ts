// Reglas para editar el diseño del dashboard: mover, cambiar el tamaño, quitar y agregar widgets sin salirse de la
// grilla ni de los topes de cada uno. Después de cada cambio los widgets suben solos para no dejar huecos, igual que
// al arrastrar. Son funciones puras: reciben el diseño y devuelven uno nuevo. Documentado en docs/frontend/dashboard.md
// El back vuelve a validar todo al guardar; esto solo evita que la persona llegue a un diseño que será rechazado.
import type { DashboardWidget, WidgetCode } from './dashboard'

export type Direction = 'up' | 'down' | 'left' | 'right'
export type Dimension = 'w' | 'h'

type Box = Pick<DashboardWidget, 'x' | 'y' | 'w' | 'h'>

const overlaps = (a: Box, b: Box): boolean => a.x < b.x + b.w && b.x < a.x + a.w && a.y < b.y + b.h && b.y < a.y + a.h

const sharesColumns = (a: Box, b: Box): boolean => a.x < b.x + b.w && b.x < a.x + a.w

const clamp = (value: number, min: number, max: number): number => Math.min(Math.max(value, min), max)

// Fila donde termina el último widget visible
export function bottomOf(widgets: DashboardWidget[]): number {
  return widgets.filter((widget) => widget.visible).reduce((bottom, widget) => Math.max(bottom, widget.y + widget.h), 0)
}

// Lo que choca con el widget fijo baja justo debajo de él, y así en cadena
function pushDown(widgets: DashboardWidget[], fixed: DashboardWidget): void {
  const queue = [fixed]
  while (queue.length > 0) {
    const pusher = queue.shift() as DashboardWidget
    for (const other of widgets) {
      if (other === pusher || other === fixed || !other.visible || !overlaps(pusher, other)) continue
      other.y = pusher.y + pusher.h
      queue.push(other)
    }
  }
}

// Cada widget sube hasta donde no choque con los que ya quedaron arriba
function compact(widgets: DashboardWidget[]): void {
  const placed: DashboardWidget[] = []
  const ordered = widgets.filter((widget) => widget.visible).sort((a, b) => a.y - b.y || a.x - b.x)
  for (const widget of ordered) {
    while (widget.y > 0 && !placed.some((other) => overlaps({ ...widget, y: widget.y - 1 }, other))) widget.y -= 1
    placed.push(widget)
  }
}

// Aplica un cambio a una copia del diseño y deja todo acomodado
function edit(
  widgets: DashboardWidget[],
  code: WidgetCode,
  change: (widget: DashboardWidget, all: DashboardWidget[]) => void,
): DashboardWidget[] {
  const copy = widgets.map((widget) => ({ ...widget }))
  const target = copy.find((widget) => widget.code === code)
  if (!target) return widgets
  change(target, copy)
  if (target.visible) pushDown(copy, target)
  compact(copy)
  return copy
}

// El vecino más cercano arriba o abajo que comparte columnas con el widget
function neighbour(widgets: DashboardWidget[], widget: DashboardWidget, direction: 'up' | 'down') {
  const candidates = widgets.filter(
    (other) =>
      other !== widget &&
      other.visible &&
      sharesColumns(widget, other) &&
      (direction === 'up' ? other.y < widget.y : other.y > widget.y),
  )
  return candidates.sort((a, b) => (direction === 'up' ? b.y - a.y : a.y - b.y))[0]
}

export function canMove(widgets: DashboardWidget[], code: WidgetCode, direction: Direction, columns: number): boolean {
  const widget = widgets.find((item) => item.code === code)
  if (!widget) return false
  if (direction === 'left') return widget.x > 0
  if (direction === 'right') return widget.x + widget.w < columns
  return neighbour(widgets, widget, direction) !== undefined
}

// Arriba y abajo intercambian el lugar con el vecino; a los lados se corre una columna
export function move(
  widgets: DashboardWidget[],
  code: WidgetCode,
  direction: Direction,
  columns: number,
): DashboardWidget[] {
  if (!canMove(widgets, code, direction, columns)) return widgets
  return edit(widgets, code, (widget, all) => {
    if (direction === 'left' || direction === 'right') {
      widget.x = clamp(widget.x + (direction === 'left' ? -1 : 1), 0, columns - widget.w)
      return
    }
    // Al subir, el vecino queda empujado debajo; al bajar, la compactación lo sube al lugar que se liberó
    const other = neighbour(all, widget, direction) as DashboardWidget
    widget.y = direction === 'up' ? other.y : other.y + other.h
  })
}

export function canResize(
  widgets: DashboardWidget[],
  code: WidgetCode,
  dimension: Dimension,
  delta: 1 | -1,
  columns: number,
): boolean {
  const widget = widgets.find((item) => item.code === code)
  if (!widget) return false
  const limits = widget.limits
  const next = widget[dimension] + delta
  if (dimension === 'w') return next >= limits.minW && next <= Math.min(limits.maxW, columns)
  return next >= limits.minH && next <= limits.maxH
}

// Al ensanchar contra el borde derecho, el widget se corre a la izquierda para caber
export function resize(
  widgets: DashboardWidget[],
  code: WidgetCode,
  dimension: Dimension,
  delta: 1 | -1,
  columns: number,
): DashboardWidget[] {
  if (!canResize(widgets, code, dimension, delta, columns)) return widgets
  return edit(widgets, code, (widget) => {
    widget[dimension] += delta
    if (dimension === 'w') widget.x = clamp(widget.x, 0, columns - widget.w)
  })
}

// El widget oculto conserva su lugar, como lo guarda el back
export function remove(widgets: DashboardWidget[], code: WidgetCode): DashboardWidget[] {
  return edit(widgets, code, (widget) => (widget.visible = false))
}

// Entra al final con el tamaño que tenía, dentro de sus topes
export function add(widgets: DashboardWidget[], code: WidgetCode, columns: number): DashboardWidget[] {
  return edit(widgets, code, (widget, all) => {
    widget.w = clamp(widget.w, widget.limits.minW, Math.min(widget.limits.maxW, columns))
    widget.h = clamp(widget.h, widget.limits.minH, widget.limits.maxH)
    widget.x = clamp(widget.x, 0, columns - widget.w)
    widget.y = bottomOf(all)
    widget.visible = true
  })
}

// Toma los lugares que dejó la grilla al arrastrar o cambiar el tamaño con el mouse. Si nada cambió devuelve el
// mismo diseño: la grilla avisa también cuando recibe uno nuevo, y una copia igual la haría avisar sin fin
export function place(widgets: DashboardWidget[], boxes: (Box & { i: string | number })[]): DashboardWidget[] {
  const byCode = new Map(boxes.map((box) => [String(box.i), box]))
  const moved = (widget: DashboardWidget, box: Box) =>
    widget.x !== box.x || widget.y !== box.y || widget.w !== box.w || widget.h !== box.h
  if (!widgets.some((widget) => byCode.has(widget.code) && moved(widget, byCode.get(widget.code) as Box))) {
    return widgets
  }
  return widgets.map((widget) => {
    const box = byCode.get(widget.code)
    return box ? { ...widget, x: box.x, y: box.y, w: box.w, h: box.h } : widget
  })
}

// Firma del diseño para saber si hay cambios sin guardar
export function signature(widgets: DashboardWidget[]): string {
  return [...widgets]
    .sort((a, b) => a.code.localeCompare(b.code))
    .map(({ code, x, y, w, h, visible }) => `${code}:${x},${y},${w},${h},${visible ? 1 : 0}`)
    .join('|')
}
