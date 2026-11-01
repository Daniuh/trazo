// A dónde vuelve "‹ Incidentes" desde el detalle o el formulario: al listado tal como se dejó (filtros, orden y página),
// o al listado sin filtros si se llegó por un enlace directo. Solo vive en memoria. Documentado en docs/frontend/incidents.md
import type { RouteLocationRaw } from 'vue-router'

let lastList: string | null = null

export function rememberListLocation(fullPath: string): void {
  lastList = fullPath
}

export function listReturnLocation(): RouteLocationRaw {
  return lastList ?? { name: 'incidents' }
}
