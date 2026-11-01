// Convierte un registro del historial en una frase clara: "Laura Gómez cambió el estado: Abierto → En progreso".
// Documentado en docs/frontend/incidents.md
import { formatDateTime } from '@/shared/format/dates'
import type { Activity } from '../domain/incident'

type Named = { name?: unknown } | null | undefined

const FIELD_LABELS: Record<string, string> = {
  title: 'el título',
  description: 'la descripción',
  priority: 'la prioridad',
  due_date: 'el vencimiento',
}

const nameOf = (value: unknown): string => String((value as Named)?.name ?? '')
// Quien hizo el cambio quedó asignado a sí mismo (lo tomó)
const isSelf = (value: unknown, activity: Activity): boolean =>
  (value as { id?: unknown } | null)?.id === activity.user.id

function listFields(fields: string[]): string {
  const labels = fields.map((field) => FIELD_LABELS[field] ?? field)
  return labels.length > 1 ? `${labels.slice(0, -1).join(', ')} y ${labels.at(-1)}` : (labels[0] ?? '')
}

// Detalle de un cambio puntual, para los campos que se entienden en una línea
function changeDetail(activity: Activity): string | null {
  const changes = activity.changes ?? {}
  if (changes.priority) return `${nameOf(changes.priority.from)} → ${nameOf(changes.priority.to)}`
  if (changes.due_date) return `Nuevo vencimiento: ${formatDateTime(String(changes.due_date.to))}`
  return null
}

export function describeActivity(activity: Activity): { text: string; detail: string | null } {
  const who = activity.user.name
  const changes = activity.changes ?? {}

  switch (activity.type) {
    case 'created': {
      const to = changes.assignee?.to
      if (!to) return { text: `${who} creó el incidente`, detail: null }
      const text = isSelf(to, activity)
        ? `${who} creó el incidente y se lo asignó`
        : `${who} creó el incidente y lo asignó a ${nameOf(to)}`
      return { text, detail: null }
    }
    case 'updated':
      return { text: `${who} editó ${listFields(Object.keys(changes))}`, detail: changeDetail(activity) }
    case 'status_changed':
      return {
        text: `${who} cambió el estado`,
        detail: `${nameOf(changes.status?.from)} → ${nameOf(changes.status?.to)}`,
      }
    case 'assigned': {
      const to = changes.assignee?.to
      return {
        text: isSelf(to, activity) ? `${who} tomó el incidente` : `${who} asignó el incidente a ${nameOf(to)}`,
        detail: null,
      }
    }
    case 'unassigned':
      return { text: `${who} quitó la asignación a ${nameOf(changes.assignee?.from)}`, detail: null }
    case 'deleted':
      return { text: `${who} eliminó el incidente`, detail: null }
    case 'restored':
      return { text: `${who} restauró el incidente`, detail: null }
    case 'reopen_requested':
      return { text: `${who} pidió reabrir el incidente`, detail: null }
    case 'reopen_rejected':
      return { text: `${who} rechazó la reapertura`, detail: null }
    case 'reopened':
      return {
        text: `${who} reabrió el incidente`,
        detail: `${nameOf(changes.status?.from)} → ${nameOf(changes.status?.to)}`,
      }
    default:
      return { text: `${who} hizo un cambio`, detail: null }
  }
}
