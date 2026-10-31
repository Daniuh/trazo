// Reglas puras del módulo de sesión: permisos y redirección segura después del login.
import { describe, expect, it } from 'vitest'
import { safeRedirect } from './safeRedirect'
import { hasPermission, type SessionUser } from './session'

const user: SessionUser = {
  id: '01H',
  name: 'Laura Gómez',
  email: 'agente1@trazo.test',
  roles: [{ code: 'agent', name: 'Agente' }],
  permissions: ['incidents.create', 'incidents.view.assigned'],
  mfa: { enabled: false, required: false, recoveryCodesLeft: 0 },
}

describe('hasPermission', () => {
  it('responde según la lista de permisos que entregó la API', () => {
    expect(hasPermission(user, 'incidents.create')).toBe(true)
    expect(hasPermission(user, 'system.logs.view')).toBe(false)
  })

  it('sin sesión no hay permisos', () => {
    expect(hasPermission(null, 'incidents.create')).toBe(false)
  })
})

describe('safeRedirect', () => {
  it('acepta rutas internas', () => {
    expect(safeRedirect('/incidentes?estado=abierto')).toBe('/incidentes?estado=abierto')
  })

  it.each([
    'https://sitio-falso.com',
    '//sitio-falso.com',
    '/\\sitio-falso.com',
    'javascript:alert(1)',
    '',
    undefined,
    ['/a'],
  ])('rechaza %s', (target) => {
    expect(safeRedirect(target)).toBeNull()
  })
})
