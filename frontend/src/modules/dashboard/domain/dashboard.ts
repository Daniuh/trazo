// Modelo del dashboard: el diseño de la persona (widgets, lugar y topes) y los datos de cada widget.
// El back decide qué widgets hay, qué se cuenta y a qué filtro del listado lleva cada cifra. Documentado en docs/frontend/dashboard.md

export type WidgetKind = 'kpi' | 'chart' | 'list'

export type WidgetCode =
  | 'total_incidents'
  | 'pending_incidents'
  | 'overdue_incidents'
  | 'unassigned_incidents'
  | 'incidents_by_status'
  | 'incidents_by_priority'
  | 'created_vs_resolved'
  | 'due_soon'
  | 'assigned_to_me'
  | 'workload_by_agent'

export interface WidgetLimits {
  minW: number
  maxW: number
  minH: number
  maxH: number
}

export interface DashboardWidget {
  code: WidgetCode
  name: string
  description: string
  kind: WidgetKind
  limits: WidgetLimits
  visible: boolean
  x: number
  y: number
  w: number
  h: number
}

export interface DashboardLayout {
  // "general" para quien ve todo; "personal" para quien ve solo lo suyo
  scope: 'general' | 'personal'
  customized: boolean
  columns: number
  widgets: DashboardWidget[]
}

// Filtro del listado al que lleva una cifra, con los nombres de la API
export interface ListFilter {
  status?: string[]
  priority?: string[]
  assignee?: string[]
  overdue?: boolean
  dueFrom?: string
  dueTo?: string
}

export interface KpiData {
  value: number
  shareOfPending?: number
  filter: ListFilter
}

export interface BreakdownItem {
  code: string
  name: string
  count: number
  percent?: number
  filter: ListFilter
}

export interface BreakdownData {
  total: number
  items: BreakdownItem[]
}

export interface TrendPoint {
  date: string
  created: number
  resolved: number
}

export interface TrendData {
  days: number
  series: TrendPoint[]
  totals: { created: number; resolved: number }
}

export interface DigestIncident {
  id: string
  code: string
  title: string
  priority: { code: string; name: string }
  status: { code: string; name: string }
  dueDate: string
  isOverdue: boolean
}

export interface DigestData {
  total: number
  items: DigestIncident[]
  filter: ListFilter
}

export interface WorkloadItem {
  id: string
  name: string
  pending: number
  overdue: number
  filter: ListFilter
}

export interface WorkloadData {
  items: WorkloadItem[]
}

export interface WidgetDataMap {
  total_incidents: KpiData
  pending_incidents: KpiData
  overdue_incidents: KpiData
  unassigned_incidents: KpiData
  incidents_by_status: BreakdownData
  incidents_by_priority: BreakdownData
  created_vs_resolved: TrendData
  due_soon: DigestData
  assigned_to_me: DigestData
  workload_by_agent: WorkloadData
}

export type Metrics = Partial<WidgetDataMap>

export const TREND_DAYS = [7, 30, 90] as const
export type TrendDays = (typeof TREND_DAYS)[number]

// Orden de lectura en celular y tablet: de arriba abajo y de izquierda a derecha, como en la grilla
export function readingOrder(widgets: DashboardWidget[]): DashboardWidget[] {
  return widgets.filter((widget) => widget.visible).sort((a, b) => a.y - b.y || a.x - b.x)
}
