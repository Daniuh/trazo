// Pantalla de login: validación visual, mensajes del catálogo y redirección segura.
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { Quasar } from 'quasar'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { registerErrorMessages } from '@/shared/errors'
import { ApiError, tokenStore } from '@/shared/http'
import { LOGIN_ROUTE } from '../application/authGuard'
import messages from '../errors'
import { authApi } from '../infrastructure/authApi'
import LoginPage from './LoginPage.vue'

vi.mock('../infrastructure/authApi', () => ({
  authApi: { login: vi.fn(), logout: vi.fn(), me: vi.fn() },
}))

const api = vi.mocked(authApi)
registerErrorMessages(messages)

async function render(query: Record<string, string> = {}) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/ingresar', name: LOGIN_ROUTE, component: LoginPage },
      { path: '/', name: 'home', component: { template: '<div />' } },
      { path: '/incidentes', name: 'incidents', component: { template: '<div />' } },
    ],
  })
  await router.push({ name: LOGIN_ROUTE, query })
  const page = mount(LoginPage, { global: { plugins: [Quasar, router] } })

  const fill = async (email: string, password: string) => {
    await page.find('input[type="email"]').setValue(email)
    await page.find('input[type="password"]').setValue(password)
    await page.find('form').trigger('submit')
    await flushPromises()
  }

  return { page, router, fill }
}

beforeEach(() => {
  setActivePinia(createPinia())
  tokenStore.clear()
  vi.clearAllMocks()
})

describe('LoginPage', () => {
  it('marca los campos vacíos sin llamar a la API', async () => {
    const { page, fill } = await render()

    await fill('', '')

    expect(page.text()).toContain('Este campo es obligatorio.')
    expect(api.login).not.toHaveBeenCalled()
  })

  it('muestra el mensaje de credenciales incorrectas y limpia la contraseña', async () => {
    api.login.mockRejectedValue(new ApiError('http', 'INVALID_CREDENTIALS', { status: 401 }))
    const { page, fill } = await render()

    await fill('a@trazo.test', 'mala')

    expect(page.find('[role="alert"]').text()).toBe(messages.INVALID_CREDENTIALS)
    expect((page.find('input[type="password"]').element as HTMLInputElement).value).toBe('')
  })

  it('dice cuánto esperar cuando el correo quedó bloqueado', async () => {
    api.login.mockRejectedValue(new ApiError('http', 'ACCOUNT_LOCKED', { status: 423, retryAfter: 300 }))
    const { page, fill } = await render()

    await fill('a@trazo.test', 'mala')

    expect(page.find('[role="alert"]').text()).toContain('Vuelve a intentarlo en 5 minutos.')
  })

  it('al ingresar vuelve a la página que se estaba visitando', async () => {
    api.login.mockResolvedValue({ token: 'T1', user: { id: '1', name: 'A', email: 'a', roles: [], permissions: [] } })
    const { router, fill } = await render({ volver: '/incidentes' })

    await fill('a@trazo.test', 'buena')

    expect(router.currentRoute.value.name).toBe('incidents')
  })

  it('ignora un destino externo después de ingresar', async () => {
    api.login.mockResolvedValue({ token: 'T1', user: { id: '1', name: 'A', email: 'a', roles: [], permissions: [] } })
    const { router, fill } = await render({ volver: '//sitio-falso.com' })

    await fill('a@trazo.test', 'buena')

    expect(router.currentRoute.value.name).toBe('home')
  })

  it('avisa cuando llega porque la sesión venció', async () => {
    const { page } = await render({ motivo: 'sesion-vencida' })

    expect(page.find('[role="status"]').text()).toContain('Tu sesión terminó')
  })

  it('permite mostrar la contraseña', async () => {
    const { page } = await render()

    await page.find('button[aria-label="Mostrar contraseña"]').trigger('click')

    expect(page.find('input[autocomplete="current-password"]').attributes('type')).toBe('text')
  })
})
