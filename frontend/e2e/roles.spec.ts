// Roles en las cuatro vistas, con el Super admin: crear uno copiando permisos, editarlo y eliminarlo. En portátil,
// volver opcional su verificación en dos pasos pide el código de la aplicación. Cada vista usa su propio nivel y lo
// libera al empezar si una corrida anterior quedó a medias. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type APIRequestContext, type Page } from '@playwright/test'
import { freshCode } from './mfa'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const ROOT = 'superadmin@trazo.test'
const LEVELS: Record<string, number> = { celular: 51, tablet: 52, portatil: 53, monitor: 54 }

// Si una corrida anterior dejó un rol de prueba en este nivel, se elimina
async function freeLevel(request: APIRequestContext, level: number): Promise<void> {
  const headers = authHeaderOf(ROOT)
  const { data } = (await (await request.get('/api/v1/roles', { headers })).json()) as {
    data: { code: string; level: number }[]
  }
  for (const role of data.filter((item) => item.level === level && item.code.startsWith('e2e_'))) {
    await request.delete(`/api/v1/roles/${role.code}`, { headers })
  }
}

async function createRole(page: Page, code: string, name: string, level: number): Promise<void> {
  await page.goto('/administracion/roles')
  await expect(page.getByRole('heading', { level: 1, name: 'Roles' })).toBeVisible()
  await expectAccessible(page)
  await page.getByRole('button', { name: 'Nuevo rol' }).click()

  await page.getByRole('textbox', { name: 'Código' }).fill(code)
  await page.getByRole('textbox', { name: 'Nombre' }).fill(name)
  await page.getByRole('textbox', { name: 'Nivel' }).fill(String(level))
  await page.getByRole('combobox', { name: /Copiar permisos de/ }).click()
  await page.getByRole('option', { name: 'Solicitante' }).click()
  await page.getByRole('checkbox', { name: 'Ver la auditoría de administración' }).click()
  await expectAccessible(page)
  await page.getByRole('button', { name: 'Crear rol' }).click()

  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible()
}

test('crear un rol copiando permisos, editarlo y eliminarlo', async ({ page, request }, info) => {
  const level = LEVELS[info.project.name] ?? 55
  const code = `e2e_${info.project.name}_${Date.now()}`
  await signInAs(page, ROOT)
  await page.goto('/')
  await freeLevel(request, level)

  await createRole(page, code, `Rol E2E ${info.project.name}`, level)
  await expect(page.getByRole('checkbox', { name: 'Crear incidentes' })).toBeChecked()
  await expect(page.getByRole('checkbox', { name: 'Ver la auditoría de administración' })).toBeChecked()
  await expectAccessible(page)

  await page.getByRole('textbox', { name: 'Nombre' }).fill(`Rol E2E ${info.project.name} editado`)
  await page.getByRole('button', { name: 'Guardar cambios' }).click()
  await expect(page.getByText('Cambios guardados.')).toBeVisible()

  await page.getByRole('button', { name: 'Eliminar rol' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Eliminar' }).click()
  await expect(page.getByRole('heading', { level: 1, name: 'Roles' })).toBeVisible()
  await expect(page.getByText(`Rol E2E ${info.project.name} editado`)).toHaveCount(0)
})

test('volver opcional la verificación en dos pasos de un rol pide el código', async ({ page, request }, info) => {
  test.skip(info.project.name !== 'portatil', 'Cada código sirve una vez: basta una vista')
  const level = 58
  await signInAs(page, ROOT)
  await page.goto('/')
  await freeLevel(request, level)
  await createRole(page, `e2e_mfa_${Date.now()}`, 'Rol E2E MFA', level)

  await page.getByRole('checkbox', { name: 'Obligatoria para este rol' }).click()
  await page.getByRole('button', { name: 'Guardar cambios' }).click()

  const confirm = page.getByRole('dialog', { name: 'Confirma que eres tú' })
  await expect(confirm).toBeVisible()
  await confirm.getByLabel('Código de la aplicación').fill(await freshCode(ROOT))
  await confirm.getByRole('button', { name: 'Guardar cambios' }).click()
  await expect(page.getByText('Cambios guardados.')).toBeVisible()

  await page.getByRole('button', { name: 'Eliminar rol' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Eliminar' }).click()
  await expect(page.getByRole('heading', { level: 1, name: 'Roles' })).toBeVisible()
})

test('el rol Super admin se ve, pero no se edita', async ({ page }, info) => {
  test.skip(info.project.name !== 'tablet', 'Basta una vista: la regla vive en la API')
  await signInAs(page, ROOT)
  await page.goto('/administracion/roles/super_admin')

  await expect(page.getByText('El rol Super admin tiene siempre todos los permisos y no se edita.')).toBeVisible()
  await expect(page.getByRole('checkbox')).toHaveCount(0)
  await expect(page.getByRole('button', { name: /Guardar|Eliminar/ })).toHaveCount(0)
  await expectAccessible(page)
})
