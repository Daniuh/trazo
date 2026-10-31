// Pruebas de la capa de aplicación de la auditoría con la API simulada: la lista de cada pestaña con los criterios de
// la URL, el catálogo y el enlace a la auditoría filtrada. Documentado en docs/frontend/audit.md
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/shared/http'
import { flush, withSetup } from '@/shared/testing/withSetup'
import { auditApi } from '../infrastructure/auditApi'
import { auditLocation } from './auditLink'
import { useAuditCatalog, useAuditList } from './useAudit'

vi.mock('../infrastructure/auditApi', () => ({
  auditApi: { catalog: vi.fn(), incidents: vi.fn(), admin: vi.fn() },
}))

const api = vi.mocked(auditApi)
const empty = { items: [], next: null, meta: { from: '2026-09-25T00:00:00Z', to: '2026-10-25T00:00:00Z' } }

beforeEach(() => {
  vi.clearAllMocks()
  api.incidents.mockResolvedValue(empty)
  api.admin.mockResolvedValue(empty)
})

describe('lista de cada pestaña', () => {
  it('incidentes usa su consulta, con el código del incidente de la URL', async () => {
    await withSetup(() => useAuditList('incidents'), '/auditoria/incidentes?incident=INC-7')
    await flush()

    expect(api.incidents).toHaveBeenCalledWith(expect.objectContaining({ target: 'INC-7' }), null, expect.anything())
    expect(api.admin).not.toHaveBeenCalled()
  })

  it('administración usa la suya, con la persona afectada', async () => {
    await withSetup(() => useAuditList('admin'), '/auditoria/administracion?user=U1')
    await flush()

    expect(api.admin).toHaveBeenCalledWith(expect.objectContaining({ target: 'U1' }), null, expect.anything())
  })
})

describe('catálogo', () => {
  it('se pide al abrir y guarda el error si falla', async () => {
    api.catalog.mockRejectedValueOnce(new ApiError('http', 'FORBIDDEN_ACTION', { status: 403 }))
    const { result } = await withSetup(() => useAuditCatalog())
    await flush()
    expect(result.error.value?.code).toBe('FORBIDDEN_ACTION')

    api.catalog.mockResolvedValueOnce({ incidentTypes: null, adminTypes: [], roles: [], permissions: [], people: [] })
    await result.load()
    expect(result.error.value).toBeNull()
    expect(result.catalog.value?.adminTypes).toEqual([])
  })
})

describe('enlace a la auditoría', () => {
  it('lleva a la pestaña con los filtros en la URL', () => {
    expect(auditLocation('admin', { actor: 'U1' })).toEqual({ name: 'audit-admin', query: { actor: 'U1' } })
    expect(auditLocation('incidents')).toEqual({ name: 'audit-incidents', query: {} })
  })
})
