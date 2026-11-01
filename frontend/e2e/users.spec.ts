// Administración de usuarios en las cuatro vistas, con el Super admin: crear una cuenta (llega la bienvenida a
// Mailpit), concederle un permiso adicional y desactivarla; en portátil, darle un rol de administración confirmando con
// el código de la aplicación. El Administrador no puede tocar al Super admin. Cada prueba crea su propia cuenta, así
// que se pueden repetir. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { mailTextFor } from './mailpit'
import { freshCode } from './mfa'
import { expectAccessible, signInAs } from './support'

const ROOT = 'superadmin@trazo.test'

async function createAccount(page: Page, name: string, email: string, role: string): Promise<void> {
  await page.goto('/administracion/usuarios/nuevo')
  await page.getByRole('textbox', { name: 'Nombre completo' }).fill(name)
  await page.getByRole('textbox', { name: 'Correo' }).fill(email)
  await page.getByRole('radio', { name: new RegExp(`^${role}`) }).check()
  await expectAccessible(page)
  await page.getByRole('button', { name: 'Crear cuenta' }).click()
  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible()
}

test('crear una cuenta, darle un permiso adicional y desactivarla', async ({ page, request }, info) => {
  const stamp = `${info.project.name}${Date.now()}`
  const name = `Persona E2E ${stamp}`
  const email = `e2e.${stamp}@empresa.test`
  const since = new Date(Date.now() - 2000)
  await signInAs(page, ROOT)

  await page.goto('/administracion/usuarios')
  await expect(page.getByRole('heading', { level: 1, name: 'Usuarios' })).toBeVisible()
  await expectAccessible(page)

  await createAccount(page, name, email, 'Agente')
  await expect(page.getByText('Tiene exactamente los permisos de Agente.')).toBeVisible()
  await expectAccessible(page)

  // La bienvenida explica cómo crear la contraseña, sin enviar ninguna
  const welcome = await mailTextFor(request, email, 'Te damos la bienvenida a Trazo', since)
  expect(welcome).toContain('/recuperar')
  expect(welcome).not.toMatch(/contraseña es/i)

  await page.getByRole('button', { name: 'Editar permisos' }).click()
  await page.getByRole('checkbox', { name: 'Ver auditoría global' }).click()
  await expectAccessible(page)
  await page.getByRole('button', { name: 'Guardar permisos' }).click()
  await expect(page.getByText('Permisos de Agente con 1 cambio.')).toBeVisible()
  await expect(page.getByText('Adicional', { exact: true })).toBeVisible()

  await page.getByRole('button', { name: 'Desactivar cuenta' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Desactivar' }).click()
  await expect(page.getByRole('button', { name: 'Activar cuenta' })).toBeVisible()

  // Se encuentra en el listado como desactivada
  await page.goto(`/administracion/usuarios?search=${encodeURIComponent(email)}&status=inactive`)
  await expect(page.getByText(name)).toBeVisible()
})

test('dar un rol de administración pide el código de quien lo da', async ({ page }, info) => {
  test.skip(info.project.name !== 'portatil', 'Cada código sirve una vez: basta una vista')
  const stamp = `${info.project.name}${Date.now()}`
  const name = `Admin E2E ${stamp}`
  await signInAs(page, ROOT)
  await createAccount(page, name, `e2e.admin.${stamp}@empresa.test`, 'Solicitante')

  await page.getByRole('button', { name: 'Cambiar rol' }).click()
  await page.getByRole('radio', { name: /^Administrador/ }).check()
  await page.getByRole('button', { name: 'Guardar rol' }).click()

  const confirm = page.getByRole('dialog', { name: 'Confirma que eres tú' })
  await expect(confirm).toBeVisible()
  await expectAccessible(page)
  await confirm.getByLabel('Código de la aplicación').fill(await freshCode(ROOT))
  await confirm.getByRole('button', { name: 'Cambiar rol' }).click()

  await expect(page.getByText('Ahora su rol es Administrador.')).toBeVisible()
  await expect(page.getByRole('dialog')).toHaveCount(0)
  await expect(page.getByText('Crea y administra a las personas por debajo de su nivel.')).toBeVisible()

  // Se deja desactivada, para no sumar administradores activos en cada ejecución
  await page.getByRole('button', { name: 'Desactivar cuenta' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Desactivar' }).click()
  await expect(page.getByRole('button', { name: 'Activar cuenta' })).toBeVisible()
})

test('el Administrador ve al Super admin pero no puede cambiar nada de su cuenta', async ({ page }, info) => {
  test.skip(info.project.name !== 'tablet', 'Basta una vista: la regla vive en la API')
  await signInAs(page, 'admin@trazo.test')

  await page.goto('/administracion/usuarios?search=superadmin')
  await page.getByText('Sofía Ramírez').click()
  await expect(page.getByRole('heading', { level: 1, name: 'Sofía Ramírez' })).toBeVisible()

  await expect(page.getByRole('button', { name: /Editar|Cambiar rol|Desactivar|Restablecer/ })).toHaveCount(0)
  await expectAccessible(page)
})
