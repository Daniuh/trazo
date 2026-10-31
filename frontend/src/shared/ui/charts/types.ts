// Datos que reciben los gráficos propios. Cada valor lleva su texto: nada se comunica solo con color.
// Documentado en docs/frontend/sistema-de-diseno/graficos.md
import type { RouteLocationRaw } from 'vue-router'
import type { Tone } from '../tones'

export interface ChartItem {
  key: string
  label: string
  value: number
  tone: Tone
  // Texto extra junto al valor, p. ej. "2 vencidos" o "35 %"
  detail?: string
  // Si lo trae, la fila lleva al detalle de esa cifra
  to?: RouteLocationRaw
}

export interface ChartSeries {
  key: string
  label: string
  tone: Tone
}

export interface ChartPoint {
  key: string
  label: string
  values: Record<string, number>
}
