// Pruebas del aviso global: si una pantalla no se descarga, se avisa con "Recargar" hacia la pantalla buscada.
import { describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { ClientErrorCode } from '@/shared/http'
import { installScreenLoadNotice } from './screenLoadNotice'

function setup(failure: Error) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', component: { template: '<div />' } },
      { path: '/incidentes', component: () => Promise.reject(failure) },
    ],
  })
  const notifyError = vi.fn()
  const reload = vi.fn()
  installScreenLoadNotice(router, notifyError, reload)
  return { router, notifyError, reload }
}

describe('aviso cuando una pantalla no se descarga', () => {
  it('avisa con el código propio y "Recargar" lleva a la pantalla buscada', async () => {
    const { router, notifyError, reload } = setup(new TypeError('Failed to fetch dynamically imported module: x.js'))

    await router.push('/incidentes?estado=open').catch(() => undefined)

    expect(notifyError).toHaveBeenCalledOnce()
    const [error, action] = notifyError.mock.calls[0] as [{ code: string }, { label: string; handler: () => void }]
    expect(error.code).toBe(ClientErrorCode.ScreenLoadFailed)
    expect(action.label).toBe('Recargar')

    action.handler()
    expect(reload).toHaveBeenCalledWith('/incidentes?estado=open')
  })

  it('no interviene en otras fallas de navegación', async () => {
    const { router, notifyError } = setup(new Error('falla de una guarda'))

    await router.push('/incidentes').catch(() => undefined)

    expect(notifyError).not.toHaveBeenCalled()
  })
})
