// Pruebas del módulo del dashboard: orden de lectura, traducción de respuestas y enlaces al listado.
// Documentado en docs/frontend/dashboard.md
import { describe, expect, it } from 'vitest'
import { incidentListLocation } from '@/modules/incidents/application/listLink'
import { readingOrder, type DashboardWidget } from './domain/dashboard'
import { toLayout, toMetrics } from './infrastructure/mappers'

const widget = (code: DashboardWidget['code'], x: number, y: number, visible = true): DashboardWidget => ({
  code,
  name: code,
  description: '',
  kind: 'kpi',
  limits: { minW: 2, maxW: 6, minH: 2, maxH: 3 },
  visible,
  x,
  y,
  w: 3,
  h: 2,
})

describe('readingOrder', () => {
  it('ordena de arriba abajo y de izquierda a derecha, y deja fuera los ocultos', () => {
    const order = readingOrder([
      widget('due_soon', 0, 6),
      widget('overdue_incidents', 6, 0),
      widget('total_incidents', 0, 0),
      widget('pending_incidents', 3, 0, false),
    ])

    expect(order.map((item) => item.code)).toEqual(['total_incidents', 'overdue_incidents', 'due_soon'])
  })
})

describe('mappers', () => {
  it('traduce los topes del diseño', () => {
    const layout = toLayout({
      scope: 'general',
      customized: false,
      columns: 12,
      widgets: [{ ...widget('total_incidents', 0, 0), limits: { min_w: 2, max_w: 6, min_h: 2, max_h: 3 } }],
    })

    expect(layout.widgets[0]?.limits).toEqual({ minW: 2, maxW: 6, minH: 2, maxH: 3 })
  })

  it('renombra las claves de los datos sin tocar los códigos de los widgets', () => {
    const metrics = toMetrics({
      overdue_incidents: { value: 3, share_of_pending: 20, filter: { overdue: true } },
      due_soon: {
        total: 1,
        items: [{ id: 'a', due_date: '2026-10-22T10:00:00+00:00', is_overdue: false }],
        filter: { due_from: 'x', due_to: 'y' },
      },
    })

    expect(metrics.overdue_incidents?.shareOfPending).toBe(20)
    expect(metrics.due_soon?.items[0]).toMatchObject({ dueDate: '2026-10-22T10:00:00+00:00', isOverdue: false })
    expect(metrics.due_soon?.filter).toEqual({ dueFrom: 'x', dueTo: 'y' })
  })

  it('renombra las claves del pulso de la operación', () => {
    const metrics = toMetrics({
      operation_pulse: {
        overdue: { value: 3, share_of_pending: 20, filter: { overdue: true } },
        on_time: { days: 30, resolved: 10, percent: 80 },
      },
    })

    expect(metrics.operation_pulse?.overdue.shareOfPending).toBe(20)
    expect(metrics.operation_pulse?.onTime).toEqual({ days: 30, resolved: 10, percent: 80 })
  })
})

describe('incidentListLocation', () => {
  it('lleva al listado con el filtro en la URL, igual que si se filtrara a mano', () => {
    expect(incidentListLocation({ status: ['open', 'in_progress'], assignee: ['none'] })).toEqual({
      name: 'incidents',
      query: { status: 'open,in_progress', assignee: 'none' },
    })
    expect(incidentListLocation({ overdue: true })).toEqual({ name: 'incidents', query: { overdue: '1' } })
  })
})
