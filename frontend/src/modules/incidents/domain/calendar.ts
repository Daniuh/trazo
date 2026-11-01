// Calendario de "Mis incidentes": cada incidente cae en el día en que vence (hora local de quien mira). Arma la grilla
// del mes, de lunes a domingo, y la agenda con solo los días que tienen algo. Documentado en docs/frontend/incidents.md
import type { Incident } from './incident'

export interface CalendarMonth {
  year: number
  // 1 a 12
  month: number
}

export interface CalendarDay {
  key: string
  date: Date
  inMonth: boolean
  isToday: boolean
  items: Incident[]
}

const pad = (value: number) => String(value).padStart(2, '0')

// "2026-10-28", con la fecha local
export const dayKey = (date: Date): string => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`

export const monthKey = (month: CalendarMonth): string => `${month.year}-${pad(month.month)}`

export const monthOf = (date: Date): CalendarMonth => ({ year: date.getFullYear(), month: date.getMonth() + 1 })

// "2026-10" desde la URL; cualquier otra cosa no es un mes
export function parseMonth(value: unknown): CalendarMonth | null {
  if (typeof value !== 'string') return null
  const match = /^(\d{4})-(\d{2})$/.exec(value)
  if (!match) return null
  const month = Number(match[2])
  return month >= 1 && month <= 12 ? { year: Number(match[1]), month } : null
}

export function shiftMonth(month: CalendarMonth, delta: number): CalendarMonth {
  return monthOf(new Date(month.year, month.month - 1 + delta, 1))
}

// Incidentes por día de vencimiento, cada día ordenado por hora
function byDay(items: Incident[]): Map<string, Incident[]> {
  const days = new Map<string, Incident[]>()
  for (const item of [...items].sort((a, b) => a.dueDate.localeCompare(b.dueDate))) {
    const key = dayKey(new Date(item.dueDate))
    days.set(key, [...(days.get(key) ?? []), item])
  }
  return days
}

// Semanas completas de lunes a domingo que cubren el mes; los días de los meses vecinos van marcados como fuera
export function monthGrid(month: CalendarMonth, items: Incident[], today: Date): CalendarDay[][] {
  const days = byDay(items)
  const first = new Date(month.year, month.month - 1, 1)
  // getDay: domingo 0; la semana empieza el lunes
  const start = new Date(month.year, month.month - 1, 1 - ((first.getDay() + 6) % 7))
  const todayKey = dayKey(today)
  const weeks: CalendarDay[][] = []

  for (let cursor = start; ;) {
    const week: CalendarDay[] = []
    for (let index = 0; index < 7; index++) {
      const key = dayKey(cursor)
      week.push({
        key,
        date: cursor,
        inMonth: cursor.getMonth() === month.month - 1,
        isToday: key === todayKey,
        items: days.get(key) ?? [],
      })
      cursor = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate() + 1)
    }
    weeks.push(week)
    if (cursor.getMonth() !== month.month - 1) return weeks
  }
}

// La agenda (celular y tablet): solo los días del mes que tienen incidentes, en orden
export function agendaDays(month: CalendarMonth, items: Incident[], today: Date): CalendarDay[] {
  return monthGrid(month, items, today)
    .flat()
    .filter((day) => day.inMonth && day.items.length > 0)
}
