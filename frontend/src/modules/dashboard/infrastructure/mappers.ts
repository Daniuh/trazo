// Traducción de las respuestas del dashboard al modelo: diseño y métricas, de snake_case a camelCase.
// Documentado en docs/frontend/dashboard.md
import type { DashboardLayout, DashboardWidget, Metrics, WidgetCode } from '../domain/dashboard'

export interface WidgetDto {
  code: WidgetCode
  name: string
  description: string
  kind: DashboardWidget['kind']
  limits: { min_w: number; max_w: number; min_h: number; max_h: number }
  visible: boolean
  x: number
  y: number
  w: number
  h: number
}

export interface LayoutDto {
  scope: DashboardLayout['scope']
  customized: boolean
  columns: number
  widgets: WidgetDto[]
}

const toWidget = (dto: WidgetDto): DashboardWidget => ({
  code: dto.code,
  name: dto.name,
  description: dto.description,
  kind: dto.kind,
  limits: { minW: dto.limits.min_w, maxW: dto.limits.max_w, minH: dto.limits.min_h, maxH: dto.limits.max_h },
  visible: dto.visible,
  x: dto.x,
  y: dto.y,
  w: dto.w,
  h: dto.h,
})

export const toLayout = (dto: LayoutDto): DashboardLayout => ({ ...dto, widgets: dto.widgets.map(toWidget) })

// Solo se renombran las claves que traen guion bajo; los códigos de widget (también con guion bajo) se conservan
const RENAMES: Record<string, string> = {
  share_of_pending: 'shareOfPending',
  due_date: 'dueDate',
  is_overdue: 'isOverdue',
  due_from: 'dueFrom',
  due_to: 'dueTo',
}

function camelize(value: unknown): unknown {
  if (Array.isArray(value)) return value.map(camelize)
  if (value === null || typeof value !== 'object') return value
  return Object.fromEntries(Object.entries(value).map(([key, inner]) => [RENAMES[key] ?? key, camelize(inner)]))
}

// La API valida cada forma; aquí solo se renombran las claves
export const toMetrics = (data: Record<string, unknown>): Metrics => camelize(data) as Metrics
