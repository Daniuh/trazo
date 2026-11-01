// Pruebas de la pantalla de sistema en el front: criterios en la URL, parámetros de la API, estados siguientes y los
// textos de cada medición. Documentado en docs/frontend/system.md
import { describe, expect, it } from 'vitest'
import { fromEventQuery, toEventQuery } from './application/systemQuery'
import { activeEventFilterCount, DEFAULT_EVENT_CRITERIA, nextStatuses } from './domain/system'
import { eventParams } from './infrastructure/systemApi'
import { componentDetails, contextRows, formatBytes, formatSeconds } from './presentation/systemTexts'

describe('criterios de los eventos', () => {
  it('van y vuelven de la URL, sin escribir la vista por defecto', () => {
    const criteria = { view: 'all' as const, severity: 'critical' as const, type: 'job_failed' }

    expect(toEventQuery(criteria)).toEqual({ view: 'all', severity: 'critical', type: 'job_failed' })
    expect(fromEventQuery({ view: 'all', severity: 'critical', type: 'job_failed' })).toEqual(criteria)
    expect(toEventQuery(DEFAULT_EVENT_CRITERIA)).toEqual({})
  })

  it('ignora lo que no reconoce', () => {
    expect(fromEventQuery({ view: 'borrados', severity: 'urgente' })).toEqual(DEFAULT_EVENT_CRITERIA)
  })

  it('arma los filtros de la API: pendientes son abiertos y revisados', () => {
    expect(eventParams(DEFAULT_EVENT_CRITERIA, null)).toEqual({ per_page: 25, 'filter[status]': 'open,acknowledged' })
    expect(eventParams({ view: 'all', severity: 'warning', type: null }, 'abc')).toEqual({
      per_page: 25,
      'filter[severity]': 'warning',
      cursor: 'abc',
    })
  })

  it('cuenta los filtros activos', () => {
    expect(activeEventFilterCount(DEFAULT_EVENT_CRITERIA)).toBe(0)
    expect(activeEventFilterCount({ view: 'resolved', severity: 'info', type: null })).toBe(2)
  })
})

describe('estados de un evento', () => {
  it('ofrece el siguiente paso de cada estado', () => {
    expect(nextStatuses('open')).toEqual(['acknowledged', 'resolved'])
    expect(nextStatuses('acknowledged')).toEqual(['resolved'])
    expect(nextStatuses('resolved')).toEqual(['open'])
  })
})

describe('textos de la salud', () => {
  it('dice cada medición en palabras', () => {
    expect(componentDetails({ code: 'database', status: 'ok', latencyMs: 4.7 })).toEqual(['Responde en 4,7 ms'])
    expect(componentDetails({ code: 'redis', status: 'failing', latencyMs: null })).toEqual(['No responde'])
    expect(
      componentDetails({ code: 'queue', status: 'warning', pending: 1200, oldestSeconds: 400, failed: 3 }),
    ).toEqual(['Pendientes: 1.200 · el más antiguo espera 6 min', 'Fallidos: 3'])
    expect(componentDetails({ code: 'worker', status: 'warning', secondsAgo: null })).toEqual([
      'Sin señal todavía (recién arrancado)',
    ])
    expect(componentDetails({ code: 'scheduler', status: 'ok', secondsAgo: 20 })).toEqual(['Última señal hace 20 s'])
    expect(
      componentDetails({ code: 'storage', status: 'ok', freeBytes: 50 * 1024 ** 3, totalBytes: 200 * 1024 ** 3 }),
    ).toEqual(['Libre: 50 GB de 200 GB (25 %)'])
  })

  it('formatea tiempos y tamaños', () => {
    expect(formatSeconds(59)).toBe('59 s')
    expect(formatSeconds(7200)).toBe('2 h')
    expect(formatBytes(300 * 1024 ** 2)).toBe('300 MB')
  })

  it('traduce las claves conocidas del contexto y deja las demás', () => {
    expect(contextRows({ account: 'da***@gmail.com', with_recovery_code: false, otra: 5 })).toEqual([
      { label: 'Cuenta', value: 'da***@gmail.com' },
      { label: 'Con código de recuperación', value: 'No' },
      { label: 'otra', value: '5' },
    ])
    expect(contextRows(null)).toEqual([])
  })
})
