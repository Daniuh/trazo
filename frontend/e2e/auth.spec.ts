// Inicio y cierre de sesión en las cuatro vistas, con revisión de accesibilidad del login.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test } from '@playwright/test'
import { DEMO_PASSWORD, completeMfa, expectAccessible, signInAs } from './support'

test('el login es accesible y avisa si los datos no son correctos', async ({ page }, info) => {
  await page.goto('/ingresar')
  await expectAccessible(page)

  // Un correo distinto por vista, para no acercarse al bloqueo por intentos
  await page.getByLabel('Correo electrónico').fill(`e2e-${info.project.name}@trazo.test`)
  await page.getByLabel('Contraseña', { exact: true }).fill('no-es-la-contraseña')
  await page.getByRole('button', { name: 'Ingresar' }).click()

  await expect(page.getByRole('alert')).toContainText('El correo o la contraseña no son correctos')
})

test('sin sesión lleva al login y vuelve a la página pedida', async ({ page }) => {
  await page.goto('/incidentes')
  await expect(page).toHaveURL(/\/ingresar\?volver=/)

  await page.getByLabel('Correo electrónico').fill('agente1@trazo.test')
  await page.getByLabel('Contraseña', { exact: true }).fill(DEMO_PASSWORD)
  await page.getByRole('button', { name: 'Ingresar' }).click()
  await completeMfa(page, 'agente1@trazo.test')

  await expect(page).toHaveURL(/\/incidentes$/)
})

test('cerrar sesión lleva al login', async ({ page }) => {
  await signInAs(page, 'solicitante2@trazo.test')
  await page.goto('/')

  // La cuenta va al final del dock (en celular, en el encabezado) y abre su menú
  await page.getByRole('button', { name: /^Tu cuenta/ }).click()
  await page.getByRole('menuitem', { name: 'Cerrar sesión' }).click()
  // Pide confirmación, por si se tocó sin querer
  await page.getByRole('alertdialog').getByRole('button', { name: 'Cerrar sesión' }).click()

  await expect(page).toHaveURL(/\/ingresar/)
})
