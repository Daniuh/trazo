// Lectura tolerante de la URL: un valor, una lista separada por comas o un entero positivo. Lo que no se reconoce
// vuelve al valor por defecto. Lo usan los listados que guardan sus criterios en la URL.
// Documentado en docs/frontend/http-y-errores.md
import type { LocationQueryRaw } from 'vue-router'

export function queryOne(value: unknown): string | null {
  const raw = Array.isArray(value) ? value[0] : value
  return typeof raw === 'string' && raw !== '' ? raw : null
}

export function queryList(value: unknown): string[] {
  return queryOne(value)?.split(',').filter(Boolean) ?? []
}

export function queryPositive(value: unknown, fallback: number): number {
  const parsed = Number(queryOne(value))
  return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback
}

// Uno de los valores permitidos, o el de por defecto
export function queryChoice<T extends string>(value: unknown, allowed: readonly T[], fallback: T): T {
  const raw = queryOne(value)
  return raw !== null && (allowed as readonly string[]).includes(raw) ? (raw as T) : fallback
}

// Arma la URL con solo lo que tiene valor, para que quede corta y legible
export function compactQuery(values: Record<string, string | null | undefined>): LocationQueryRaw {
  const query: LocationQueryRaw = {}
  for (const [key, value] of Object.entries(values)) {
    if (value) query[key] = value
  }
  return query
}
