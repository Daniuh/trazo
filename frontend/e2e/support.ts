// Ayudas compartidas de las pruebas E2E: abrir sesión sin pasar por el formulario (completando el MFA como lo haría
// una persona con su teléfono), terminar el MFA en la interfaz y revisar la accesibilidad con axe.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import AxeBuilder from '@axe-core/playwright'
import { expect, type APIRequestContext, type Page } from '@playwright/test'
import { freshCode, saveSecret } from './mfa'

export const DEMO_PASSWORD = 'Trazo.Demo.2026'

// Mismas claves que usa la app (shared/http/tokenStore.ts)
const SESSION_KEY = 'trazo.session'

// El login real tiene un límite de 5 intentos por minuto: cada usuario entra una sola vez por proceso de pruebas
const tokens = new Map<string, string>()

interface SessionBody {
  data: { status: string; token: string }
}

async function post(request: APIRequestContext, url: string, token: string, data: object = {}): Promise<unknown> {
  const response = await request.post(url, { headers: { Authorization: `Bearer ${token}` }, data })
  expect(response.ok(), `${url}: ${await response.text()}`).toBe(true)
  return response.json()
}

// Ingreso por la API. Con gracia entra directo; sin gracia configura el MFA; con MFA escribe el código
async function apiSession(request: APIRequestContext, email: string): Promise<string> {
  const response = await request.post('/api/v1/auth/login', { data: { email, password: DEMO_PASSWORD } })
  expect(response.status(), `Login de ${email}`).toBe(200)
  const { status, token } = ((await response.json()) as SessionBody).data

  if (status === 'complete' || status === 'setup_suggested') return token

  if (status === 'mfa_setup') {
    const setup = (await post(request, '/api/v1/auth/mfa/setup', token)) as { data: { secret: string } }
    saveSecret(email, setup.data.secret)
    const confirmed = (await post(request, '/api/v1/auth/mfa/setup/confirm', token, {
      code: await freshCode(email),
    })) as { data: { session: SessionBody['data'] } }
    return confirmed.data.session.token
  }

  const verified = (await post(request, '/api/v1/auth/mfa/verify', token, {
    code: await freshCode(email),
  })) as SessionBody
  return verified.data.token
}

export async function signInAs(page: Page, email: string): Promise<void> {
  let token = tokens.get(email)
  if (!token) {
    token = await apiSession(page.request, email)
    tokens.set(email, token)
  }

  await page.addInitScript(([key, value]) => window.sessionStorage.setItem(key, value), [SESSION_KEY, token] as const)
}

// Olvida la sesión guardada de una persona (p. ej. después de desactivarla, que revoca su token)
export function forgetSession(email: string): void {
  tokens.delete(email)
}

// Cabecera para llamar a la API como esa persona (después de signInAs), p. ej. para limpiar datos de una prueba
export function authHeaderOf(email: string): Record<string, string> {
  const token = tokens.get(email)
  if (!token) throw new Error(`${email} todavía no inició sesión en esta prueba`)
  return { Authorization: `Bearer ${token}` }
}

// Después de enviar el formulario de login: termina el segundo paso como lo haría la persona en la pantalla
export async function completeMfa(page: Page, email: string): Promise<void> {
  await page.waitForURL((url) => url.pathname !== '/ingresar')
  const { pathname, searchParams } = new URL(page.url())

  if (pathname === '/ingresar/verificar') {
    await page.getByLabel('Código de verificación').fill(await freshCode(email))
    await page.getByRole('button', { name: 'Verificar' }).click()
  } else if (pathname === '/configurar-mfa' && searchParams.get('sugerido') === '1') {
    await page.getByRole('button', { name: 'Configurar más tarde' }).click()
  } else if (pathname === '/configurar-mfa') {
    await setUpMfa(page, email)
    await page.getByRole('button', { name: 'Continuar' }).click()
  }

  // Hasta salir del segundo paso: navegar antes cortaría la verificación y la sesión no quedaría guardada
  await page.waitForURL((url) => !['/ingresar', '/ingresar/verificar', '/configurar-mfa'].includes(url.pathname))
}

// Configura el MFA en la pantalla: toma la clave que se muestra, escribe el código y confirma que guardó los códigos
export async function setUpMfa(page: Page, email: string): Promise<void> {
  const secret = (await page.locator('.app-copy-block__value').innerText()).replace(/\s/g, '')
  saveSecret(email, secret)
  await page.getByLabel('Código de verificación').fill(await freshCode(email))
  await page.getByRole('button', { name: 'Activar' }).click()

  await expect(page.getByRole('heading', { name: 'Códigos de recuperación' })).toBeVisible()
  await expect(page.getByRole('listitem')).toHaveCount(8)
  await page.getByLabel('Ya guardé mis códigos de recuperación').check()
}

// Espera a que la pantalla quede quieta: ninguna lista recargándose (se atenúa con aria-busy) y ninguna animación en
// curso (un diálogo que aparece tiene opacidad parcial). Las animaciones infinitas, como la de carga, no cuentan
async function settle(page: Page): Promise<void> {
  await expect(page.locator('section[aria-busy="true"]')).toHaveCount(0)
  await page.waitForFunction(() =>
    document
      .getAnimations()
      .every(
        (animation) =>
          animation.playState !== 'running' || animation.effect?.getComputedTiming().iterations === Infinity,
      ),
  )
}

// Marca si alguna lista se puso a recargar mientras axe medía (por ejemplo, al reconectarse el tiempo real)
async function watchBusy(page: Page): Promise<() => Promise<boolean>> {
  await page.evaluate(() => {
    const flags = window as unknown as { __trazoBusySeen?: boolean; __trazoBusyObserver?: MutationObserver }
    flags.__trazoBusyObserver?.disconnect()
    flags.__trazoBusySeen = false
    flags.__trazoBusyObserver = new MutationObserver(() => {
      if (document.querySelector('section[aria-busy="true"]')) flags.__trazoBusySeen = true
    })
    flags.__trazoBusyObserver.observe(document.body, {
      subtree: true,
      attributes: true,
      attributeFilter: ['aria-busy'],
    })
  })
  return () => page.evaluate(() => (window as unknown as { __trazoBusySeen?: boolean }).__trazoBusySeen === true)
}

// WCAG 2.1 nivel AA, el estándar que sigue el sistema de diseño. Si una lista se recargó durante la medición, se mide
// de nuevo: el contraste de una lista atenuada no es el real
export async function expectAccessible(page: Page): Promise<void> {
  let summary: string[] = []
  for (let attempt = 0; attempt < 3; attempt++) {
    await settle(page)
    const busyDuring = await watchBusy(page)
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze()
    summary = results.violations.map(
      (violation) => `${violation.id}: ${violation.nodes.map((node) => node.target.join(' ')).join(' | ')}`,
    )
    if (!(await busyDuring())) break
  }

  expect(summary, 'Problemas de accesibilidad').toEqual([])
}
