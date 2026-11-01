// Colores de estados y prioridades: los catálogos no guardan colores, el front asocia cada código a un tono.
// Un código nuevo sin tono se ve neutro. Documentado en docs/frontend/sistema-de-diseno/tokens.md
import type { Tone } from '@/shared/ui/tones'

const STATUS_TONES: Record<string, Tone> = {
  open: 'blue',
  in_progress: 'orange',
  on_hold: 'neutral',
  resolved: 'green',
  closed: 'dark',
  cancelled: 'muted',
}

const PRIORITY_TONES: Record<string, Tone> = {
  low: 'green',
  medium: 'blue',
  high: 'orange',
  critical: 'danger',
}

export const OVERDUE_TONE: Tone = 'danger'

export const statusTone = (code: string): Tone => STATUS_TONES[code] ?? 'neutral'
export const priorityTone = (code: string): Tone => PRIORITY_TONES[code] ?? 'neutral'
