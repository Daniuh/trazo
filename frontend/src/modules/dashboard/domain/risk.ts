// Cómo se leen los widgets del diseño centrado en el riesgo (ADR-0081): rumbo del backlog, intensidad de cada celda del
// mapa y nombres de sus columnas. Los números los calcula la API. Documentado en docs/frontend/dashboard.md
import type { RiskBucket } from './dashboard'

export const RISK_COLUMN_LABELS: Record<RiskBucket, string> = {
  overdue: 'Vencido',
  today: 'Hoy',
  week: '7 días',
  later: 'Después',
}

export type BacklogTrend = 'up' | 'down' | 'flat'

// Hoy: si entró más de lo que se resolvió, el backlog sube
export function backlogTrend(created: number, resolved: number): BacklogTrend {
  if (created > resolved) return 'up'
  return created < resolved ? 'down' : 'flat'
}

// Intensidad de una celda del mapa (0 = vacía, 3 = la más cargada), relativa a la celda más cargada
export type RiskLevel = 0 | 1 | 2 | 3

export function riskLevel(count: number, max: number): RiskLevel {
  if (count <= 0 || max <= 0) return 0
  const ratio = count / max
  if (ratio >= 2 / 3) return 3
  return ratio >= 1 / 3 ? 2 : 1
}
