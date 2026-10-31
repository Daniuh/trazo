// Estado de la sesión y guarda de rutas, con la API simulada.
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'
import { ApiError, httpEvents, tokenStore } from '@/shared/http'
import type { SessionUser } from '../domain/session'
import { authApi } from '../infrastructure/authApi'
import { installAuthGuard, LOGIN_ROUTE, SESSION_EXPIRED_REASON } from './authGuard'
import { useSessionStore } from './useSessionStore'

vi.mock('../infrastructure/authApi', () => ({
  authApi: { login: vi.fn(), logout: vi.fn(), me: vi.fn() },
}))

const api = vi.mocked(authApi)
const user: SessionUser = {
  id: '01H',
  name: 'Laura',
  email: 'a@trazo.test',
  roles: [],
  permissions: ['incidents.create'],
}
const Blank = { template: '<div />' }

function buildRouter(): Router {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/ingresar', name: LOGIN_ROUTE, component: Blank, meta: { public: true } },
      { path: '/', name: 'home', component: Blank },
      { path: '/incidentes', name: 'incidents', component: Blank },
    ],
  })
  installAuthGuard(router)
  return router
}

beforeEach(() => {
  setActivePinia(createPinia())
  tokenStore.clear()
  vi.clearAllMocks()
})

describe('useSessionStore', () => {
  it('al ingresar guarda el token y el usuario', async () => {
    api.login.mockResolvedValue({ token: 'T1', user })

    await useSessionStore().login('a@trazo.test', 'clave')

    expect(tokenStore.get()).toBe('T1')
    expect(useSessionStore().can('incidents.create')).toBe(true)
  })

  it('no guarda nada si el ingreso falla', async () => {
    api.login.mockRejectedValue(new ApiError('http', 'INVALID_CREDENTIALS', { status: 401 }))

    await expect(useSessionStore().login('a@trazo.test', 'mala')).rejects.toThrow()
    expect(tokenStore.get()).toBeNull()
  })

  it('recupera el usuario tras recargar la página si queda un token', async () => {
    tokenStore.set('T1')
    api.me.mockResolvedValue(user)

    expect(await useSessionStore().restore()).toBe(true)
    expect(useSessionStore().user).toEqual(user)
  })

  it('descarta el token si la API ya no lo reconoce', async () => {
    tokenStore.set('T1')
    api.me.mockRejectedValue(new ApiError('http', 'UNAUTHENTICATED', { status: 401 }))

    expect(await useSessionStore().restore()).toBe(false)
    expect(tokenStore.get()).toBeNull()
  })

  it('cierra la sesión local aunque la API no responda', async () => {
    tokenStore.set('T1')
    api.logout.mockRejectedValue(new ApiError('network', 'NETWORK_ERROR'))

    await useSessionStore().logout()

    expect(tokenStore.get()).toBeNull()
    expect(useSessionStore().user).toBeNull()
  })
})

describe('authGuard', () => {
  it('sin sesión lleva al login y recuerda a dónde iba', async () => {
    const router = buildRouter()

    await router.push('/incidentes')

    expect(router.currentRoute.value.name).toBe(LOGIN_ROUTE)
    expect(router.currentRoute.value.query.volver).toBe('/incidentes')
  })

  it('con sesión deja pasar y saca del login', async () => {
    tokenStore.set('T1')
    api.me.mockResolvedValue(user)
    const router = buildRouter()

    await router.push('/incidentes')
    expect(router.currentRoute.value.name).toBe('incidents')

    await router.push('/ingresar')
    expect(router.currentRoute.value.name).toBe('home')
  })

  it('cuando la sesión vence vuelve al login con el aviso', async () => {
    tokenStore.set('T1')
    api.me.mockResolvedValue(user)
    const router = buildRouter()
    await router.push('/incidentes')

    httpEvents.emit('session-expired')
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe(LOGIN_ROUTE))

    expect(router.currentRoute.value.query).toMatchObject({ motivo: SESSION_EXPIRED_REASON, volver: '/incidentes' })
  })
})
