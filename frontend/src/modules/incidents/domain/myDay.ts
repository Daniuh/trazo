// "Mi día": lo de hoy en bloques, para la reunión diaria. Cada incidente va en un solo bloque, el primero que le toque:
// vencidos, vencen hoy, en progreso, en espera y resueltos ayer. "Vencido" lo calcula la API; aquí solo se agrupa.
// Documentado en docs/frontend/incidents.md
import { dayKey } from './calendar'
import type { Incident } from './incident'

// Códigos estables del catálogo de estados
const IN_PROGRESS = 'in_progress'
const ON_HOLD = 'on_hold'
const RESOLVED = 'resolved'

export type DaySectionKey = 'overdue' | 'dueToday' | 'inProgress' | 'onHold' | 'resolvedYesterday'

export interface DaySection {
  key: DaySectionKey
  label: string
  // Qué decir si no hay nada
  empty: string
  items: Incident[]
}

const SECTIONS: {
  key: DaySectionKey
  label: string
  empty: string
  matches: (item: Incident, today: string, yesterday: string) => boolean
}[] = [
  { key: 'overdue', label: 'Vencidos', empty: 'Nada vencido.', matches: (item) => item.isOverdue },
  {
    key: 'dueToday',
    label: 'Vencen hoy',
    empty: 'Nada vence hoy.',
    matches: (item, today) =>
      !item.status.isFinal && item.status.code !== RESOLVED && dayKey(new Date(item.dueDate)) === today,
  },
  {
    key: 'inProgress',
    label: 'En progreso',
    empty: 'Nada en progreso.',
    matches: (item) => item.status.code === IN_PROGRESS,
  },
  { key: 'onHold', label: 'En espera', empty: 'Nada en espera.', matches: (item) => item.status.code === ON_HOLD },
  {
    key: 'resolvedYesterday',
    label: 'Resueltos ayer',
    empty: 'Ayer no resolviste ninguno.',
    matches: (item, _today, yesterday) => item.resolvedAt !== null && dayKey(new Date(item.resolvedAt)) === yesterday,
  },
]

export function daySections(items: Incident[], now: Date): DaySection[] {
  const today = dayKey(now)
  const yesterday = dayKey(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1))
  const placed = new Set<string>()

  return SECTIONS.map(({ key, label, empty, matches }) => {
    const found = items
      .filter((item) => !placed.has(item.id) && matches(item, today, yesterday))
      .sort((a, b) => a.dueDate.localeCompare(b.dueDate))
    found.forEach((item) => placed.add(item.id))
    return { key, label, empty, items: found }
  })
}
