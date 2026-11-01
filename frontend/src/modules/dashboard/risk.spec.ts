// Lectura de los widgets del riesgo: rumbo del backlog e intensidad de las celdas del mapa.
import { describe, expect, it } from 'vitest'
import { backlogTrend, riskLevel } from './domain/risk'

describe('backlogTrend', () => {
  it('sube si entra más de lo que se resuelve, baja si no, y se mantiene si es igual', () => {
    expect([backlogTrend(18, 12), backlogTrend(5, 9), backlogTrend(4, 4)]).toEqual(['up', 'down', 'flat'])
  })
})

describe('riskLevel', () => {
  it('mide cada celda frente a la más cargada; vacía es 0', () => {
    expect([0, 10, 40, 70, 100].map((count) => riskLevel(count, 100))).toEqual([0, 1, 2, 3, 3])
    expect(riskLevel(5, 0)).toBe(0)
  })
})
