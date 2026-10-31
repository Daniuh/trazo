// Fechas para las personas: la API habla en UTC (ISO 8601) y aquí se muestran en la hora local del navegador.
// Documentado en docs/frontend/incidents.md

const LOCALE = 'es-CO'

const dateTime = new Intl.DateTimeFormat(LOCALE, { dateStyle: 'medium', timeStyle: 'short' })
const shortDateTime = new Intl.DateTimeFormat(LOCALE, {
  day: 'numeric',
  month: 'short',
  hour: 'numeric',
  minute: '2-digit',
})
const relative = new Intl.RelativeTimeFormat(LOCALE, { numeric: 'auto' })

const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
  ['day', 86_400],
  ['hour', 3_600],
  ['minute', 60],
]

// "22 oct 2026, 4:10 p. m."
export function formatDateTime(iso: string): string {
  return dateTime.format(new Date(iso))
}

// "25 oct, 10:30 a. m.": para campos, donde el espacio es corto
export function formatShortDateTime(iso: string): string {
  return shortDateTime.format(new Date(iso))
}

// "hace un momento", "hace 2 horas", "ayer", "dentro de 3 días"; pasada una semana, la fecha completa
export function formatRelative(iso: string, now: Date = new Date()): string {
  const seconds = Math.round((new Date(iso).getTime() - now.getTime()) / 1000)
  if (Math.abs(seconds) >= 7 * 86_400) return formatDateTime(iso)

  for (const [unit, size] of UNITS) {
    if (Math.abs(seconds) >= size) return relative.format(Math.round(seconds / size), unit)
  }
  return 'hace un momento'
}
