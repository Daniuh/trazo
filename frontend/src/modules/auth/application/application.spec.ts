// Estado de la sesión y guarda de rutas, con la API simulada.
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'
import { ApiError, httpEvents, tokenStore } from '@/shared/http'
import type { SessionUser } from '../domain/session'
import { authApi } from '../infrastructure/authApi'
import { CONNECTION_ROUTE, installAuthGuard, LOGIN_ROUTE, SESSION_EXPIRED_REASON } from './authGuard'
import { useRecoveryStore } from './useRecoveryStore'
import { useSessionStore } from './useSessionStore'

vi.mock('../infrastructure/authApi', () => ({
  authApi: {
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    verifyMfa: vi.fn(),
    forgotPassword: vi.fn(),
    verifyResetCode: vi.fn(),
    resetPassword: vi.fn(),
    startMfaSetup: vi.fn(),
    confirmMfaSetup: vi.fn(),
    regenerateRecoveryCodes: vi.fn(),
    disableMfa: vi.fn(),
    changePassword: vi.fn(),
  },
}))

const api = vi.mocked(authApi)
const user: SessionUser = {
  id: '01H',
  name: 'Laura',
  email: 'a@trazo.test',
  roles: [],
  permissions: ['incidents.create'],
  mfa: { enabled: false, required: false, recoveryCodesLeft: 0 },
}
const Blank = { template: '<div />' }

function buildRouter(): Router {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/ingresar', name: LOGIN_ROUTE, component: Blank, meta: { public: true } },
      { path: '/sin-conexion', name: CONNECTION_ROUTE, component: Blank, meta: { public: true } },
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
    api.login.mockResolvedValue({ step: 'complete', token: 'T1', user })

    await useSessionStore().login('a@trazo.test', 'clave')

    expect(tokenStore.get()).toBe('T1')
    expect(useSessionStore().can('incidents.create')).toBe(true)
  })

  it('con MFA pendiente guarda el token solo en memoria, sin abrir la sesión', async () => {
    api.login.mockResolvedValue({ step: 'mfa_verify', token: 'C1', user: null })
    const session = useSessionStore()

    expect(await session.login('a@trazo.test', 'clave')).toBe('mfa_verify')
    expect(tokenStore.get()).toBeNull()
    expect(session.isAuthenticated).toBe(false)
    expect(session.pending).toEqual({ token: 'C1', step: 'mfa_verify' })
  })

  it('al verificar el código cambia el ingreso a medias por la sesión completa', async () => {
    api.login.mockResolvedValue({ step: 'mfa_verify', token: 'C1', user: null })
    api.verifyMfa.mockResolvedValue({ step: 'complete', token: 'T2', user })
    const session = useSessionStore()

    await session.login('a@trazo.test', 'clave')
    await session.verifyMfa({ code: '123456' })

    expect(api.verifyMfa).toHaveBeenCalledWith('C1', { code: '123456' })
    expect(tokenStore.get()).toBe('T2')
    expect(session.pending).toBeNull()
    expect(session.isAuthenticated).toBe(true)
  })

  it('no guarda nada si el ingreso falla', async () => {
    api.login.mockRejectedValue(new ApiError('http', 'INVALID_CREDENTIALS', { status: 401 }))

    await expect(useSessionStore().login('a@trazo.test', 'mala')).rejects.toThrow()
    expect(tokenStore.get()).toBeNull()
  })

  it('recupera el usuario tras recargar la página si queda un token', async () => {
    tokenStore.set('T1')
    api.me.mockResolvedValue(user)

    expect(await useSessionStore().restore()).toBe('signed-in')
    expect(useSessionStore().user).toEqual(user)
  })

  it('descarta el token si la API ya no lo reconoce', async () => {
    tokenStore.set('T1')
    api.me.mockRejectedValue(new ApiError('http', 'UNAUTHENTICATED', { status: 401 }))

    expect(await useSessionStore().restore()).toBe('signed-out')
    expect(tokenStore.get()).toBeNull()
  })

  it('conserva el token si la API no responde o está en mantenimiento', async () => {
    tokenStore.set('T1')
    api.me.mockRejectedValue(new ApiError('timeout', 'TIMEOUT'))

    expect(await useSessionStore().restore()).toBe('unreachable')
    expect(tokenStore.get()).toBe('T1')
    expect(useSessionStore().connectionError?.code).toBe('TIMEOUT')
  })

  it('cierra la sesión local aunque la API no responda', async () => {
    tokenStore.set('T1')
    api.logout.mockRejectedValue(new ApiError('network', 'NETWORK_ERROR'))

    await useSessionStore().logout()

    expect(tokenStore.get()).toBeNull()
    expect(useSessionStore().user).toBeNull()
  })
})

describe('MFA y contraseña con la sesión abierta', () => {
  it('configura el MFA con el token del ingreso a medias y abre la sesión', async () => {
    api.login.mockResolvedValue({ token: 'medio', step: 'mfa_setup', user: null })
    api.startMfaSetup.mockResolvedValue({ secret: 'ABC', uri: 'otpauth://totp/x' })
    api.confirmMfaSetup.mockResolvedValue({
      recoveryCodes: ['a', 'b'],
      session: { token: 'pleno', step: 'complete', user },
    })
    const session = useSessionStore()

    await session.login('a@trazo.test', 'clave')
    await session.startMfaSetup()
    expect(api.startMfaSetup).toHaveBeenCalledWith('medio')
    expect(await session.confirmMfaSetup('123456')).toEqual(['a', 'b'])
    expect(api.confirmMfaSetup).toHaveBeenCalledWith('123456', 'medio')
    expect(session.user).toEqual(user)
    expect(tokenStore.get()).toBe('pleno')
  })

  it('con la sesión abierta, cada cambio de MFA vuelve a pedir quién es', async () => {
    api.me.mockResolvedValue({ ...user, mfa: { enabled: true, required: true, recoveryCodesLeft: 8 } })
    api.startMfaSetup.mockResolvedValue({ secret: 'ABC', uri: 'otpauth://totp/x' })
    api.confirmMfaSetup.mockResolvedValue({ recoveryCodes: ['a'], session: null })
    api.regenerateRecoveryCodes.mockResolvedValue(['c'])
    api.disableMfa.mockResolvedValue(undefined)
    api.changePassword.mockResolvedValue(undefined)
    const session = useSessionStore()

    await session.startMfaSetup()
    expect(api.startMfaSetup).toHaveBeenCalledWith(undefined)
    await session.confirmMfaSetup('123456')
    expect(await session.regenerateRecoveryCodes('123456')).toEqual(['c'])
    await session.disableMfa('123456')
    await session.changePassword('vieja', 'nueva-segura', 'nueva-segura')

    expect(api.me).toHaveBeenCalledTimes(3)
    expect(session.user?.mfa.enabled).toBe(true)
    expect(api.changePassword).toHaveBeenCalledWith('vieja', 'nueva-segura', 'nueva-segura')
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

  it('si no puede comprobar la sesión avisa sin conexión en vez de mandar al login', async () => {
    tokenStore.set('T1')
    api.me.mockRejectedValue(new ApiError('http', 'SERVICE_UNAVAILABLE', { status: 503 }))
    const router = buildRouter()

    await router.push('/incidentes')

    expect(router.currentRoute.value.name).toBe(CONNECTION_ROUTE)
    expect(router.currentRoute.value.query.volver).toBe('/incidentes')
    expect(tokenStore.get()).toBe('T1')

    api.me.mockResolvedValue(user)
    await router.replace('/incidentes')
    expect(router.currentRoute.value.name).toBe('incidents')
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

describe('useRecoveryStore', () => {
  it('guarda el correo, cambia el código por el pase y lo usa una sola vez para la contraseña nueva', async () => {
    api.verifyResetCode.mockResolvedValue({ ticket: 'P1', mfaRequired: true })
    const recovery = useRecoveryStore()

    await recovery.request('a@trazo.test')
    await recovery.verify('123456')
    expect(api.verifyResetCode).toHaveBeenCalledWith('a@trazo.test', '123456')
    expect(recovery.mfaRequired).toBe(true)

    const chosen = 'Cielo-Naranja-42'
    await recovery.reset({ password: chosen, confirmation: chosen, mfaCode: '654321' })
    expect(api.resetPassword).toHaveBeenCalledWith(
      expect.objectContaining({ email: 'a@trazo.test', ticket: 'P1', mfaCode: '654321' }),
    )
    expect(recovery.ticket).toBeNull()
    expect(recovery.email).toBe('')
  })
})
