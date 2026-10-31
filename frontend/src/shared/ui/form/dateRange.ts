// Lógica del rango de fechas: días completos en la hora local y texto corto para el campo ("1 de oct – 22 de oct").
// Documentado en docs/frontend/sistema-de-diseno/componentes.md
import { toParts } from './dateTimeParts'

const short = new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short' })

// "2026/10/01" del calendario a su primer y último instante del día, en UTC
export function dayBounds(day: string): { start: string; end: string } {
  const [year, month, date] = day.split('/').map(Number) as [number, number, number]
  return {
    start: new Date(year, month - 1, date, 0, 0, 0, 0).toISOString(),
    end: new Date(year, month - 1, date, 23, 59, 59, 999).toISOString(),
  }
}

export function toDay(iso: string): string {
  return toParts(iso).date
}

export function rangeLabel(from: string | null, to: string | null): string {
  if (from && to) {
    return toDay(from) === toDay(to)
      ? short.format(new Date(from))
      : `${short.format(new Date(from))} – ${short.format(new Date(to))}`
  }
  if (from) return `Desde el ${short.format(new Date(from))}`
  return to ? `Hasta el ${short.format(new Date(to))}` : ''
}
