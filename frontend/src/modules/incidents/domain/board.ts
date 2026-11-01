// "Mis incidentes": las vistas (Kanban, Lista, Calendario y Mi día), las columnas del Kanban y el orden de la lista.
// A dónde se puede mover una tarjeta lo dice la API (nextStatuses); aquí solo se consulta. Documentado en docs/frontend/incidents.md
import type { CatalogItem, Incident, NextStatus, StatusOption } from './incident'
import type { SortField } from './listing'

export type BoardView = 'kanban' | 'lista' | 'calendario' | 'dia'

export const BOARD_VIEWS: { value: BoardView; label: string }[] = [
  { value: 'kanban', label: 'Kanban' },
  { value: 'lista', label: 'Lista' },
  { value: 'calendario', label: 'Calendario' },
  { value: 'dia', label: 'Mi día' },
]

export const DEFAULT_VIEW: BoardView = 'kanban'

export const parseView = (value: unknown): BoardView | null =>
  BOARD_VIEWS.some((view) => view.value === value) ? (value as BoardView) : null

export interface BoardColumn {
  status: CatalogItem & { isFinal: boolean }
  items: Incident[]
}

// Una columna por estado, en el orden del catálogo; cada tarjeta conserva el orden de la API (por vencimiento)
export function boardColumns(statuses: StatusOption[], items: Incident[]): BoardColumn[] {
  return statuses.map((status) => ({
    status: { code: status.code, name: status.name, isFinal: status.isFinal },
    items: items.filter((item) => item.status.code === status.code),
  }))
}

export const nextStatusFor = (item: Incident, code: string): NextStatus | null =>
  item.nextStatuses.find((status) => status.code === code) ?? null

export const canMoveTo = (item: Incident, code: string): boolean => nextStatusFor(item, code) !== null

// La lista se ordena en el navegador: el tablero ya trae todo (sin páginas)
export function sortBoardItems(
  items: Incident[],
  field: SortField,
  descending: boolean,
  statusOrder: string[],
): Incident[] {
  const value = (item: Incident): string | number => {
    switch (field) {
      case 'priority':
        return item.priority.level
      case 'status':
        return statusOrder.indexOf(item.status.code)
      case 'code':
        return item.code
      case 'created_at':
        return item.createdAt
      case 'updated_at':
        return item.updatedAt
      case 'due_date':
        return item.dueDate
    }
  }
  const direction = descending ? -1 : 1
  return [...items].sort((a, b) => {
    const [left, right] = [value(a), value(b)]
    if (left === right) return a.id < b.id ? -1 : 1
    return (left < right ? -1 : 1) * direction
  })
}
