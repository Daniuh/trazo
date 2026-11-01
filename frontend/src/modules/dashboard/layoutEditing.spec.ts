// Pruebas de la edición del diseño: mover, cambiar el tamaño, quitar y agregar sin huecos, choques ni salirse de los topes.
// Documentado en docs/frontend/dashboard.md
import { describe, expect, it } from 'vitest'
import type { DashboardWidget, WidgetCode } from './domain/dashboard'
import { add, canMove, canResize, move, place, remove, resize, signature } from './domain/layoutEditing'

const COLUMNS = 12

const widget = (code: WidgetCode, x: number, y: number, w: number, h: number, visible = true): DashboardWidget => ({
  code,
  name: code,
  description: '',
  kind: 'chart',
  limits: { minW: 3, maxW: 12, minH: 2, maxH: 6 },
  visible,
  x,
  y,
  w,
  h,
})

// Dos indicadores arriba y un gráfico a lo ancho debajo
const base = (): DashboardWidget[] => [
  widget('total_incidents', 0, 0, 6, 2),
  widget('pending_incidents', 6, 0, 6, 2),
  widget('incidents_by_status', 0, 2, 12, 4),
  widget('due_soon', 0, 6, 6, 4, false),
]

const at = (widgets: DashboardWidget[], code: WidgetCode) => {
  const found = widgets.find((item) => item.code === code) as DashboardWidget
  return { x: found.x, y: found.y, w: found.w, h: found.h, visible: found.visible }
}

const overlapping = (widgets: DashboardWidget[]) =>
  widgets
    .filter((a) => a.visible)
    .some((a) =>
      widgets.some(
        (b) => b !== a && b.visible && a.x < b.x + b.w && b.x < a.x + a.w && a.y < b.y + b.h && b.y < a.y + a.h,
      ),
    )

describe('mover', () => {
  it('al subir intercambia el lugar con el vecino de arriba', () => {
    const result = move(base(), 'incidents_by_status', 'up', COLUMNS)

    expect(at(result, 'incidents_by_status')).toMatchObject({ x: 0, y: 0 })
    expect(at(result, 'total_incidents')).toMatchObject({ y: 4 })
    expect(at(result, 'pending_incidents')).toMatchObject({ y: 4 })
    expect(overlapping(result)).toBe(false)
  })

  it('al bajar queda debajo del vecino y este sube a su lugar si tiene espacio', () => {
    const widgets = [widget('total_incidents', 0, 0, 6, 2), widget('incidents_by_status', 0, 2, 6, 4)]
    const result = move(widgets, 'total_incidents', 'down', COLUMNS)

    expect(at(result, 'incidents_by_status')).toMatchObject({ y: 0 })
    expect(at(result, 'total_incidents')).toMatchObject({ x: 0, y: 4 })
  })

  it('al bajar no sube un vecino que otro widget tapa', () => {
    const result = move(base(), 'total_incidents', 'down', COLUMNS)

    expect(at(result, 'incidents_by_status')).toMatchObject({ y: 2 })
    expect(at(result, 'total_incidents')).toMatchObject({ x: 0, y: 6 })
    expect(overlapping(result)).toBe(false)
  })

  it('a los lados se corre una columna y empuja hacia abajo lo que choque', () => {
    const result = move(base(), 'total_incidents', 'right', COLUMNS)

    expect(at(result, 'total_incidents')).toMatchObject({ x: 1, y: 0 })
    expect(at(result, 'pending_incidents').y).toBeGreaterThanOrEqual(2)
    expect(overlapping(result)).toBe(false)
  })

  it('no se sale de la grilla ni se mueve sin vecino', () => {
    const widgets = base()

    expect(canMove(widgets, 'total_incidents', 'left', COLUMNS)).toBe(false)
    expect(canMove(widgets, 'pending_incidents', 'right', COLUMNS)).toBe(false)
    expect(canMove(widgets, 'total_incidents', 'up', COLUMNS)).toBe(false)
    expect(canMove(widgets, 'incidents_by_status', 'down', COLUMNS)).toBe(false)
    expect(move(widgets, 'total_incidents', 'left', COLUMNS)).toBe(widgets)
  })
})

describe('cambiar el tamaño', () => {
  it('respeta los topes del widget', () => {
    const widgets = base()

    expect(canResize(widgets, 'incidents_by_status', 'w', 1, COLUMNS)).toBe(false)
    expect(canResize(widgets, 'total_incidents', 'h', -1, COLUMNS)).toBe(false)
    expect(canResize(widgets, 'total_incidents', 'h', 1, COLUMNS)).toBe(true)
  })

  it('al crecer empuja hacia abajo lo que tiene debajo', () => {
    const result = resize(base(), 'total_incidents', 'h', 1, COLUMNS)

    expect(at(result, 'total_incidents').h).toBe(3)
    expect(at(result, 'incidents_by_status').y).toBe(3)
    expect(overlapping(result)).toBe(false)
  })

  it('al ensanchar contra el borde derecho se corre a la izquierda', () => {
    const result = resize(base(), 'pending_incidents', 'w', 1, COLUMNS)

    expect(at(result, 'pending_incidents')).toMatchObject({ x: 5, w: 7 })
    expect(overlapping(result)).toBe(false)
  })
})

describe('quitar y agregar', () => {
  it('al quitar, lo de abajo sube a ocupar el espacio', () => {
    const result = remove(base(), 'incidents_by_status')

    expect(at(result, 'incidents_by_status').visible).toBe(false)
    expect(result).toHaveLength(4)
  })

  it('al agregar entra al final con su tamaño', () => {
    const result = add(base(), 'due_soon', COLUMNS)

    expect(at(result, 'due_soon')).toEqual({ x: 0, y: 6, w: 6, h: 4, visible: true })
  })

  it('no cambia el diseño original', () => {
    const widgets = base()
    const before = signature(widgets)

    move(widgets, 'incidents_by_status', 'up', COLUMNS)
    remove(widgets, 'total_incidents')

    expect(signature(widgets)).toBe(before)
  })
})

describe('lugares del arrastre', () => {
  it('toma los lugares que deja la grilla y sabe si hubo cambios', () => {
    const widgets = base()
    const result = place(widgets, [{ i: 'total_incidents', x: 0, y: 6, w: 6, h: 2 }])

    expect(at(result, 'total_incidents')).toMatchObject({ y: 6 })
    expect(signature(result)).not.toBe(signature(widgets))
  })

  it('devuelve el mismo diseño si la grilla no movió nada', () => {
    const widgets = base()

    expect(place(widgets, [{ i: 'total_incidents', x: 0, y: 0, w: 6, h: 2 }])).toBe(widgets)
  })
})
