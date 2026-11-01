// Reparto de las secciones del dock en el celular: si no caben todas, las primeras y "Más" con el resto.
// Documentado en docs/frontend/sistema-de-diseno/layout.md
import { layout } from '@/design/tokens/layout'

// Lugares para las secciones en el celular (la campana y la cuenta van aparte, al final). En los más angostos un nombre
// como "Incidentes" no cabe con aire a los lados, así que hay menos lugares
export function dockSlots(width: number): number {
  if (width < layout.tinyPhoneWidth) return 2
  return width < layout.smallPhoneWidth ? 3 : 4
}

export function splitDock<T>(items: T[], slots: number): { shown: T[]; more: T[] } {
  if (items.length <= slots) return { shown: items, more: [] }
  // "Más" ocupa un lugar
  return { shown: items.slice(0, slots - 1), more: items.slice(slots - 1) }
}

// Sección actual por la dirección: "Incidentes" sigue marcada en el detalle o en "Mis incidentes" (el inicio, solo exacto)
export function inSection(current: string, section: string, exact = false): boolean {
  if (exact || section === '/') return current === section
  return current === section || current.startsWith(`${section}/`)
}
