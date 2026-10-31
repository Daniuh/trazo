// Auditoría en las cuatro vistas: el supervisor encuentra por su código un incidente recién creado y abre el registro
// con su IP y navegador; el administrador solo ve la pestaña de administración, y el Super admin ve las dos.
// Todo pasa por axe. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const SUPERVISOR = 'supervisor@trazo.test'

// Un incidente nuevo por la API, para tener un registro propio que buscar
async function createIncident(page: Page, title: string): Promise<string> {
  const response = await page.request.post('/api/v1/incidents', {
    headers: { ...authHeaderOf(SUPERVISOR), 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: 'Incidente creado por la prueba de auditoría.', priority: 'low' },
  })
  expect(response.ok()).toBe(true)
  return ((await response.json()) as { data: { code: string } }).data.code
}

// En portátil y monitor el campo está en la barra; en tablet y celular, dentro del panel de filtros
async function filterByIncident(page: Page, code: string, compact: boolean): Promise<void> {
  if (compact) {
    await page.getByRole('button', { name: 'Filtros' }).click()
    await page.getByRole('dialog').getByRole('searchbox', { name: 'Incidente' }).fill(code)
    await expectAccessible(page)
    await page.getByRole('button', { name: 'Ver resultados' }).click()
  } else {
    await page.getByRole('searchbox', { name: 'Incidente' }).fill(code)
  }
  await expect(page).toHaveURL(new RegExp(`incident=${code}`))
}

test('el supervisor encuentra un incidente por su código y ve desde dónde se hizo cada cambio', async ({
  page,
}, info) => {
  const compact = info.project.name === 'celular' || info.project.name === 'tablet'
  await signInAs(page, SUPERVISOR)
  await page.goto('/')
  const title = `Auditoría ${info.project.name} ${Date.now()}`
  const code = await createIncident(page, title)

  await page.goto('/auditoria')
  await expect(page).toHaveURL(/\/auditoria\/incidentes/)
  await expect(page.getByRole('heading', { level: 1, name: 'Auditoría' })).toBeVisible()
  // Solo tiene una pestaña: no hay selector de registro
  await expect(page.getByRole('radiogroup', { name: 'Registro' })).toHaveCount(0)
  await expectAccessible(page)

  await filterByIncident(page, code, compact)
  const entry = page.getByLabel(`Creado: ${code} · ${title}`)
  await expect(entry).toBeVisible()
  await entry.click()

  const detail =
    info.project.name === 'monitor'
      ? page.getByRole('complementary', { name: 'Detalle del registro' })
      : page.getByRole('dialog')
  await expect(detail.getByText('Dirección IP')).toBeVisible()
  await expect(detail.getByRole('link', { name: 'Abrir incidente' })).toBeVisible()
  await expectAccessible(page)

  await detail.getByRole('link', { name: 'Abrir incidente' }).click()
  await expect(page.getByRole('heading', { level: 1, name: title })).toBeVisible()
})

test('el administrador ve solo la administración y el Super admin ve las dos pestañas', async ({ page }, info) => {
  test.skip(info.project.name !== 'tablet' && info.project.name !== 'monitor', 'Basta una vista angosta y una ancha')

  await signInAs(page, 'admin@trazo.test')
  await page.goto('/auditoria')
  await expect(page).toHaveURL(/\/auditoria\/administracion/)
  await expect(page.getByRole('radiogroup', { name: 'Registro' })).toHaveCount(0)
  await expectAccessible(page)

  await signInAs(page, 'superadmin@trazo.test')
  await page.goto('/auditoria/administracion')
  const tabs = page.getByRole('radiogroup', { name: 'Registro' })
  await expect(tabs.getByRole('radio', { name: 'Administración' })).toBeChecked()
  await tabs.getByRole('radio', { name: 'Incidentes' }).click()
  await expect(page).toHaveURL(/\/auditoria\/incidentes/)
  await expectAccessible(page)
})
