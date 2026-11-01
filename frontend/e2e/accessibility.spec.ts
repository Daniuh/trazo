// Barrido de accesibilidad: cada ruta de la app en las cuatro vistas pasa por axe (WCAG 2.1 AA) y no produce ninguna
// violación de la CSP. Además, los flujos principales se recorren solo con el teclado. Las demás pruebas miden cada
// pantalla dentro de su flujo; esta asegura que ninguna quede sin revisar. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const ROOT = 'superadmin@trazo.test'

// Junta los avisos de la consola que delatan un recurso bloqueado por la CSP
function watchCsp(page: Page): string[] {
  const violations: string[] = []
  page.on('console', (message) => {
    if (/Content Security Policy|Refused to (load|apply|execute|connect)/i.test(message.text())) {
      violations.push(message.text())
    }
  })
  return violations
}

async function firstId(page: Page, url: string): Promise<string> {
  const response = await page.request.get(url, { headers: authHeaderOf(ROOT) })
  expect(response.ok()).toBe(true)
  const body = (await response.json()) as { data: { id: string }[] }
  const first = body.data[0]
  if (!first) throw new Error(`${url} no devolvió registros`)
  return first.id
}

// Espera a que la pantalla termine de cargar: sin esqueletos ni la pantalla de carga de marca
// La guía visual muestra un esqueleto a propósito, como ejemplo: ahí solo se espera la pantalla de carga
async function settled(page: Page, demoSkeleton = false): Promise<void> {
  await page.waitForLoadState('networkidle')
  await expect(page.locator(demoSkeleton ? '.shell-loading' : '.app-skeleton, .shell-loading')).toHaveCount(0)
}

test('las pantallas públicas son accesibles', async ({ page }) => {
  const violations = watchCsp(page)

  for (const path of ['/ingresar', '/recuperar', '/una-ruta-que-no-existe']) {
    await page.goto(path)
    await settled(page)
    await expectAccessible(page)
  }

  expect(violations, 'Violaciones de CSP').toEqual([])
})

test('todas las pantallas con sesión son accesibles', async ({ page }) => {
  test.setTimeout(240_000)
  const violations = watchCsp(page)
  await signInAs(page, ROOT)
  await page.goto('/')
  await settled(page)

  const incident = await firstId(page, '/api/v1/incidents?per_page=1')
  const user = await firstId(page, '/api/v1/users?per_page=1&search=agente1')
  const paths = [
    '/incidentes',
    '/incidentes/nuevo',
    `/incidentes/${incident}`,
    `/incidentes/${incident}/editar`,
    '/auditoria/incidentes',
    '/auditoria/administracion',
    '/administracion/usuarios',
    '/administracion/usuarios/nuevo',
    `/administracion/usuarios/${user}`,
    '/administracion/roles',
    '/administracion/roles/nuevo',
    '/administracion/roles/agent',
    '/sistema/salud',
    '/sistema/eventos',
    '/mi-cuenta',
    '/guia-visual',
  ]

  await expectAccessible(page)
  for (const path of paths) {
    await page.goto(path)
    await settled(page, path === '/guia-visual')
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
    await expectAccessible(page)
  }

  expect(violations, 'Violaciones de CSP').toEqual([])
})

test.describe('solo con el teclado', () => {
  test.skip(({ isMobile }) => isMobile, 'El teclado se prueba en portátil y monitor')

  test('"Saltar al contenido" es lo primero y lleva al contenido', async ({ page }) => {
    await signInAs(page, ROOT)
    await page.goto('/incidentes')
    await settled(page)

    await page.keyboard.press('Tab')
    const skip = page.getByRole('link', { name: 'Saltar al contenido' })
    await expect(skip).toBeFocused()
    await expect(skip).toBeVisible()
    await page.keyboard.press('Enter')
    await expect(page.locator('#contenido')).toBeFocused()

    // El siguiente Tab ya está dentro del contenido, no en el menú
    await page.keyboard.press('Tab')
    const insideContent = await page.evaluate(() =>
      document.querySelector('#contenido')?.contains(document.activeElement),
    )
    expect(insideContent).toBe(true)
  })

  test('se crea un incidente sin usar el ratón', async ({ page }) => {
    await signInAs(page, ROOT)
    await page.goto('/incidentes/nuevo')
    await settled(page)
    const title = `Teclado ${Date.now()}`

    await page.getByLabel('Título').focus()
    await page.keyboard.type(title)
    await page.keyboard.press('Tab')
    await page.keyboard.type('Creado recorriendo el formulario solo con el teclado.')
    await page.getByRole('button', { name: 'Crear incidente' }).focus()
    await page.keyboard.press('Enter')

    await expect(page.getByRole('heading', { level: 1, name: title })).toBeVisible()
  })

  test('un diálogo se cierra con Escape y el foco vuelve al botón que lo abrió', async ({ page }) => {
    await signInAs(page, ROOT)
    await page.goto('/sistema/salud')
    await settled(page)

    const trigger = page.getByRole('button', { name: 'Limpiar caché de datos' })
    await trigger.focus()
    await page.keyboard.press('Enter')
    const dialog = page.getByRole('dialog', { name: 'Limpiar la caché de datos' })
    await expect(dialog).toBeVisible()
    // El foco queda dentro del diálogo, en el campo del código
    await expect(dialog.getByLabel('Código de la aplicación')).toBeFocused()

    await page.keyboard.press('Escape')
    await expect(dialog).toHaveCount(0)
    await expect(trigger).toBeFocused()
  })
})
