// Lógica del selector de fecha y hora, sin interfaz: horas cada 30 minutos, qué ya pasó y conversión a ISO.
// Trabaja en la hora local del navegador; la API recibe UTC. Documentado en docs/frontend/sistema-de-diseno/componentes.md

export interface DateTimeParts {
  // Formato del calendario: "2026/10/25"
  date: string
  // "10:30"
  time: string
}

export const SLOT_MINUTES = 30

const pad = (value: number) => String(value).padStart(2, '0')

export function halfHourSlots(): string[] {
  return Array.from({ length: (24 * 60) / SLOT_MINUTES }, (_, index) => {
    const minutes = index * SLOT_MINUTES
    return `${pad(Math.floor(minutes / 60))}:${pad(minutes % 60)}`
  })
}

export function toParts(iso: string): DateTimeParts {
  const date = new Date(iso)
  return {
    date: `${date.getFullYear()}/${pad(date.getMonth() + 1)}/${pad(date.getDate())}`,
    time: `${pad(date.getHours())}:${pad(date.getMinutes())}`,
  }
}

export function toIso(parts: DateTimeParts): string {
  const [year, month, day] = parts.date.split('/').map(Number) as [number, number, number]
  const [hours, minutes] = parts.time.split(':').map(Number) as [number, number]
  return new Date(year, month - 1, day, hours, minutes).toISOString()
}

export function isPastDay(date: string, now: Date = new Date()): boolean {
  return date < toParts(now.toISOString()).date
}

export function isPast(parts: DateTimeParts, now: Date = new Date()): boolean {
  return new Date(toIso(parts)).getTime() < now.getTime()
}

// Redondea hacia arriba a la media hora: 10:55 pasa a 11:00 y 11:00 se queda igual
export function roundUp(date: Date): DateTimeParts {
  const rounded = new Date(date)
  const partial = rounded.getSeconds() > 0 || rounded.getMilliseconds() > 0 ? 1 : 0
  rounded.setSeconds(0, 0)
  rounded.setMinutes(Math.ceil((rounded.getMinutes() + partial) / SLOT_MINUTES) * SLOT_MINUTES)
  return toParts(rounded.toISOString())
}

// Primer horario disponible a partir de ahora, para que el calendario no abra en un momento pasado
export function nextSlot(now: Date = new Date()): DateTimeParts {
  return roundUp(now)
}

// Si el valor guardado no cae justo en media hora (p. ej. 10:23), se incluye para que siga seleccionado
export function slotsWith(time: string | null): string[] {
  const slots = halfHourSlots()
  return time && !slots.includes(time) ? [...slots, time].sort() : slots
}
