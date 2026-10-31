// Reglas de presentación del formulario que guía (ADR-0082): cuándo usar cada prioridad, cómo se lee su plazo, los
// atajos de vencimiento y qué palabras del título buscan incidentes parecidos. La API sigue decidiendo y validando.
// Documentado en docs/frontend/incidents.md
import { isSearchable } from './listing'

// Cuándo usar cada prioridad. Un código sin ayuda muestra solo su plazo
export const PRIORITY_HINTS: Record<string, string> = {
  low: 'Molesta, pero se puede seguir trabajando',
  medium: 'Afecta a una persona o a un proceso',
  high: 'Afecta a un equipo o a clientes',
  critical: 'Detiene la operación',
}

const HOUR = 60
const DAY = 24 * HOUR

// "30 min", "4 h", "1 día", "3 días": el tiempo de resolución del catálogo, en minutos
export function formatResolution(minutes: number): string {
  if (minutes >= DAY && minutes % DAY === 0) return minutes === DAY ? '1 día' : `${minutes / DAY} días`
  if (minutes >= HOUR && minutes % HOUR === 0) return `${minutes / HOUR} h`
  return `${minutes} min`
}

// Atajos del vencimiento. "Sugerido" deja el campo vacío para que la API lo calcule con la prioridad
export type DueChoice = 'suggested' | 'today' | 'tomorrow' | 'other'

// Hoy y mañana vencen al cierre de la jornada, en la hora local de quien reporta
export const END_OF_DAY_HOUR = 18

function endOfDay(now: Date, daysAhead: number): Date {
  const date = new Date(now)
  date.setDate(date.getDate() + daysAhead)
  date.setHours(END_OF_DAY_HOUR, 0, 0, 0)
  return date
}

// Atajos que se pueden usar ahora: "Hoy" desaparece al pasar el cierre; "Sugerido" solo al crear
export function dueChoices(now: Date, creating: boolean): DueChoice[] {
  const today = endOfDay(now, 0) > now
  return [...(creating ? ['suggested' as const] : []), ...(today ? ['today' as const] : []), 'tomorrow', 'other']
}

// Fecha de un atajo (ISO en UTC); null para "Sugerido" y "Otra fecha" (la elige la persona)
export function dueDateFor(choice: DueChoice, now: Date): string | null {
  if (choice === 'today') return endOfDay(now, 0).toISOString()
  if (choice === 'tomorrow') return endOfDay(now, 1).toISOString()
  return null
}

// Qué atajo corresponde a un vencimiento ya puesto (al editar o al volver a la página)
export function choiceFor(dueDate: string | null, now: Date, creating: boolean): DueChoice {
  if (dueDate === null) return creating ? 'suggested' : 'other'
  if (dueDate === dueDateFor('today', now)) return 'today'
  return dueDate === dueDateFor('tomorrow', now) ? 'tomorrow' : 'other'
}

// Palabras que no ayudan a encontrar un parecido
const STOPWORDS = new Set(
  'que los las del por para con una uno unos unas desde hasta esta este esto pero como cuando donde sin sobre entre muy mas más hay ese esa son'.split(
    ' ',
  ),
)

// Búsquedas de parecidos, de la más precisa a la más amplia: las dos palabras más largas del título y, si eso no
// encuentra nada, la más larga sola. La API exige todas las palabras, así que con más casi nunca habría parecidos.
// Vacío si no queda nada que buscar
export function similarQueries(title: string): string[] {
  const words = (title.toLowerCase().match(/[\p{L}\p{N}]{3,}/gu) ?? []).filter((word) => !STOPWORDS.has(word))
  const longest = [...new Set(words)].sort((a, b) => b.length - a.length)
  const queries = [longest.slice(0, 2).join(' '), longest[0] ?? '']
  return [...new Set(queries)].filter((query) => query !== '' && isSearchable(query))
}
