// Cómo se muestra el plazo de un incidente: tono de la barra y texto ("quedan 5 h", "venció hace 3 h",
// "Resuelto 2 días tarde"). Los datos (grupo, avance, a tiempo o no) los calcula la API.
// Documentado en docs/frontend/incidents.md
import type { Deadline } from './incident'

// Desde aquí el plazo se ve apretado
export const DEADLINE_WARNING_FROM = 75

export type DeadlineTone = 'ok' | 'warning' | 'danger'

export interface DeadlineView {
  // Avance para la barra (0 a 100); null en los terminados, que no llevan barra
  percent: number | null
  tone: DeadlineTone | null
  text: string
}

const MINUTE = 60_000
const HOUR = 60 * MINUTE
const DAY = 24 * HOUR

// "40 min", "5 h", "1 día", "3 días": la unidad más grande que quepa
export function formatSpan(ms: number): string {
  const span = Math.abs(ms)
  if (span >= DAY) {
    const days = Math.floor(span / DAY)
    return days === 1 ? '1 día' : `${days} días`
  }
  if (span >= HOUR) return `${Math.floor(span / HOUR)} h`
  return `${Math.max(1, Math.floor(span / MINUTE))} min`
}

export function deadlineView(
  deadline: Deadline,
  dueDate: string,
  resolvedAt: string | null,
  now: Date = new Date(),
): DeadlineView {
  const due = new Date(dueDate).getTime()

  if (deadline.bucket === 'finished') {
    if (deadline.resolvedOnTime === null || resolvedAt === null) return { percent: null, tone: null, text: '' }
    const text = deadline.resolvedOnTime
      ? 'Resuelto a tiempo'
      : `Resuelto ${formatSpan(new Date(resolvedAt).getTime() - due)} tarde`
    return { percent: null, tone: deadline.resolvedOnTime ? 'ok' : 'danger', text }
  }

  const percent = deadline.timeUsed ?? 0
  if (deadline.bucket === 'overdue') {
    return { percent: 100, tone: 'danger', text: `Venció hace ${formatSpan(now.getTime() - due)}` }
  }
  return {
    percent,
    tone: percent >= DEADLINE_WARNING_FROM ? 'warning' : 'ok',
    text: `Quedan ${formatSpan(due - now.getTime())}`,
  }
}
