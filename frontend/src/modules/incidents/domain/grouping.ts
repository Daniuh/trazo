// Lista agrupada del listado: criterios de grupo, nombre de cada grupo y armado de secciones con lo que llega de la
// API. La API ya ordena por grupo y envía el total de cada uno; aquí solo se corta la página en secciones.
// Documentado en docs/frontend/incidents.md
import type { DueBucket } from './incident'
import type { IncidentSummary } from './listing'

export const GROUP_OPTIONS = ['due', 'status', 'priority', 'assignee', 'none'] as const
export type GroupBy = (typeof GROUP_OPTIONS)[number]

export const DEFAULT_GROUP: GroupBy = 'due'

// Clave de "Sin asignar" en los totales que manda la API
export const UNASSIGNED_GROUP = 'none'

export const GROUP_LABELS: Record<GroupBy, string> = {
  due: 'Vencimiento',
  status: 'Estado',
  priority: 'Prioridad',
  assignee: 'Asignado',
  none: 'Ninguno',
}

export const DUE_BUCKET_LABELS: Record<DueBucket, string> = {
  overdue: 'Vencidos',
  today: 'Vencen hoy',
  week: 'Próximos 7 días',
  later: 'Más adelante',
  finished: 'Terminados',
}

export interface GroupSection {
  key: string
  label: string
  // Total del grupo con los filtros (no solo los de esta página); null si la API no lo mandó
  total: number | null
  items: IncidentSummary[]
}

function keyOf(item: IncidentSummary, groupBy: Exclude<GroupBy, 'none'>): { key: string; label: string } {
  switch (groupBy) {
    case 'due':
      return { key: item.deadline.bucket, label: DUE_BUCKET_LABELS[item.deadline.bucket] }
    case 'status':
      return { key: item.status.code, label: item.status.name }
    case 'priority':
      return { key: item.priority.code, label: item.priority.name }
    case 'assignee':
      return item.assignee
        ? { key: item.assignee.id, label: item.assignee.name }
        : { key: UNASSIGNED_GROUP, label: 'Sin asignar' }
  }
}

// Corta la página en secciones seguidas, en el orden en que llegó. Sin grupo, una sola sección sin nombre
export function groupSections(
  items: IncidentSummary[],
  groupBy: GroupBy,
  totals: Record<string, number>,
): GroupSection[] {
  if (groupBy === 'none') return items.length > 0 ? [{ key: 'all', label: '', total: null, items }] : []

  const sections: GroupSection[] = []
  for (const item of items) {
    const { key, label } = keyOf(item, groupBy)
    const last = sections.at(-1)
    if (last?.key === key) last.items.push(item)
    else sections.push({ key, label, total: totals[key] ?? null, items: [item] })
  }
  return sections
}
