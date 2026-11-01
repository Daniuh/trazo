// Modelo del dashboard: el diseño de la persona (widgets, lugar y topes) y los datos de cada widget.
// El back decide qué widgets hay, qué se cuenta y a qué filtro del listado lleva cada cifra. Documentado en docs/frontend/dashboard.md

export type WidgetKind = 'kpi' | 'chart' | 'list'

export type WidgetCode =
  | 'operation_pulse'
  | 'risk_map'
  | 'status_flow'
  | 'backlog_balance'
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

// Una cifra que lleva al listado con su filtro
export interface FilteredFigure {
  value: number
  filter: ListFilter
}

export interface PulseData {
  // Todos los que la persona puede ver, en cualquier estado
  total: FilteredFigure
  pending: FilteredFigure
  overdue: FilteredFigure & { shareOfPending: number }
  // La prioridad más alta del catálogo; null si no hay prioridades
  critical: (FilteredFigure & { name: string }) | null
  today: { created: number; resolved: number }
  // De lo resuelto en la ventana, qué porcentaje fue antes del vencimiento; null si no se resolvió nada
  onTime: { days: number; resolved: number; percent: number | null }
}

export type RiskBucket = 'overdue' | 'today' | 'week' | 'later'

export interface RiskCell {
  bucket: RiskBucket
  count: number
  filter: ListFilter
}

export interface RiskMapData {
  columns: RiskBucket[]
  // De la prioridad más alta a la más baja
  // total: todos los de esa prioridad, en cualquier estado
  rows: { code: string; name: string; cells: RiskCell[]; total: { count: number; filter: ListFilter } }[]
}

export interface FlowStage {
  code: string
  name: string
  count: number
  filter: ListFilter
}

export interface StatusFlowData {
  stages: FlowStage[]
  // Estados que salen del camino sin resolverse (Cancelado)
  exits: FlowStage[]
}

export interface BacklogPoint {
  date: string
  pending: number
  created: number
  exited: number
}

export interface BacklogData {
  days: number
  series: BacklogPoint[]
  // Pendientes al final del periodo menos al inicio
  change: number
  filter: ListFilter
}

export interface WidgetDataMap {
  operation_pulse: PulseData
  risk_map: RiskMapData
  status_flow: StatusFlowData
  backlog_balance: BacklogData
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
