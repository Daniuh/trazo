// Ayudas compartidas de las pruebas E2E: abrir sesión sin pasar por el formulario y revisar la accesibilidad con axe.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import AxeBuilder from '@axe-core/playwright'
import { expect, type Page } from '@playwright/test'

export const DEMO_PASSWORD = 'Trazo.Demo.2026'

// Mismas claves que usa la app (shared/http/tokenStore.ts)
const SESSION_KEY = 'trazo.session'

// El login real tiene un límite de 5 intentos por minuto: cada usuario entra una sola vez por proceso de pruebas
const tokens = new Map<string, string>()

export async function signInAs(page: Page, email: string): Promise<void> {
  let token = tokens.get(email)
  if (!token) {
    const response = await page.request.post('/api/v1/auth/login', { data: { email, password: DEMO_PASSWORD } })
    expect(response.status(), `Login de ${email}`).toBe(200)
    token = ((await response.json()) as { data: { token: string } }).data.token
    tokens.set(email, token)
  }

  await page.addInitScript(([key, value]) => window.sessionStorage.setItem(key, value), [SESSION_KEY, token] as const)
}

// WCAG 2.1 nivel AA, el estándar que sigue el sistema de diseño
export async function expectAccessible(page: Page): Promise<void> {
  const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze()
  const summary = results.violations.map(
    (violation) => `${violation.id}: ${violation.nodes.map((node) => node.target.join(' ')).join(' | ')}`,
  )

  expect(summary, 'Problemas de accesibilidad').toEqual([])
}
