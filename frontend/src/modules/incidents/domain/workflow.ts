// Lectura del flujo de estados para el detalle (ADR-0083): el camino con la etapa actual y el siguiente paso que se
// propone. Todo sale del catálogo y de lo que la API permite a la persona; aquí no se decide qué está permitido.
// Documentado en docs/frontend/incidents.md
import type { CatalogItem, NextStatus, StatusOption } from './incident'

export type StepState = 'done' | 'current' | 'todo'

export interface StatusPath {
  steps: (CatalogItem & { state: StepState })[]
  // Si el incidente salió del camino (p. ej. Cancelado), cuál fue la salida
  exit: CatalogItem | null
}

// Salidas: los estados finales a los que se llega directo desde el inicial (Cancelado)
function exitCodes(catalog: StatusOption[]): Set<string> {
  const initial = catalog.find((status) => status.isInitial)
  const finals = new Set(catalog.filter((status) => status.isFinal).map((status) => status.code))
  return new Set((initial?.next ?? []).filter((code) => finals.has(code)))
}

export function statusPath(catalog: StatusOption[], current: string): StatusPath {
  const exits = exitCodes(catalog)
  const path = catalog.filter((status) => !exits.has(status.code))
  const position = path.findIndex((status) => status.code === current)
  const exit = exits.has(current) ? (catalog.find((status) => status.code === current) ?? null) : null

  return {
    steps: path.map((status, index) => ({
      code: status.code,
      name: status.name,
      state: index < position ? 'done' : index === position ? 'current' : 'todo',
    })),
    exit: exit ? { code: exit.code, name: exit.name } : null,
  }
}

// Siguiente paso: de los estados permitidos que van más adelante en el camino, primero los que cierran el trabajo (no
// pendientes, como Resuelto o Cerrado); si nada va adelante, el primero permitido. Nunca una salida
export function nextStep(catalog: StatusOption[], current: string, allowed: NextStatus[]): NextStatus | null {
  const exits = exitCodes(catalog)
  const order = new Map(catalog.map((status, index) => [status.code, index]))
  const pending = new Map(catalog.map((status) => [status.code, status.isPending]))
  const here = order.get(current) ?? -1
  const candidates = allowed.filter((status) => !exits.has(status.code))
  const ahead = candidates.filter((status) => (order.get(status.code) ?? -1) > here)

  return ahead.find((status) => pending.get(status.code) === false) ?? ahead[0] ?? candidates[0] ?? null
}
