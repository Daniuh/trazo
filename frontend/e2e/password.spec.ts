// Recuperar y cambiar la contraseña con una cuenta propia (solicitante3), que siempre termina con su contraseña de
// demostración. La recuperación corre en celular y monitor: la API acepta 3 solicitudes de código cada 15 minutos por
// correo, y cuatro vistas seguidas la pasarían. El cambio desde Mi cuenta corre en tablet y portátil.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { resetCodeFor } from './mailpit'
import { freshCode } from './mfa'
import { DEMO_PASSWORD, completeMfa, expectAccessible, signInAs } from './support'

const EMAIL = 'solicitante3@trazo.test'
const TEMPORARY = 'Cielo-Naranja-Tranvia-42'

async function fillNewPassword(page: Page, password: string): Promise<void> {
  await page.getByLabel('Contraseña nueva', { exact: true }).fill(password)
  await page.getByLabel('Repite la contraseña nueva').fill(password)
}

// Desde Mi cuenta: de una contraseña a otra, con el diálogo
async function changePassword(page: Page, from: string, to: string): Promise<void> {
  await page.getByRole('button', { name: 'Cambiar contraseña' }).click()
  const dialog = page.getByRole('dialog', { name: 'Cambiar la contraseña' })
  await dialog.getByLabel('Contraseña actual').fill(from)
  await fillNewPassword(page, to)
  await dialog.getByRole('button', { name: 'Cambiar contraseña' }).click()
  await expect(dialog).toHaveCount(0)
  await expect(page.getByText('Tu contraseña cambió.')).toBeVisible()
}

test('se recupera la contraseña con el código del correo y luego se vuelve a la original', async ({ page }, info) => {
  test.skip(!['celular', 'monitor'].includes(info.project.name), 'La API limita las solicitudes de código')

  await page.goto('/ingresar')
  await page.getByRole('link', { name: '¿Olvidaste tu contraseña?' }).click()
  await expectAccessible(page)
  const since = new Date(Date.now() - 2000)
  await page.getByLabel('Correo electrónico').fill(EMAIL)
  await page.getByRole('button', { name: 'Enviar código' }).click()

  await expect(page).toHaveURL(/\/recuperar\/codigo$/)
  await expectAccessible(page)
  await page.getByLabel('Código del correo').fill(await resetCodeFor(page.request, EMAIL, since))
  await page.getByRole('button', { name: 'Continuar' }).click()

  await expect(page).toHaveURL(/\/recuperar\/nueva$/)
  await fillNewPassword(page, TEMPORARY)
  // Si la cuenta ya activó la verificación en dos pasos (en una vista anterior), la recuperación también la pide
  const mfaCode = page.getByLabel('Código de la aplicación')
  if (await mfaCode.isVisible()) await mfaCode.fill(await freshCode(EMAIL))
  await expectAccessible(page)
  await page.getByRole('button', { name: 'Guardar contraseña' }).click()

  await expect(page).toHaveURL(/motivo=contrasena-cambiada/)
  await expect(page.getByText('Tu contraseña cambió y cerramos tus sesiones abiertas')).toBeVisible()

  // Entra con la nueva y la devuelve a la de demostración, para que las demás pruebas sigan funcionando
  await page.getByLabel('Correo electrónico').fill(EMAIL)
  await page.getByLabel('Contraseña', { exact: true }).fill(TEMPORARY)
  await page.getByRole('button', { name: 'Ingresar' }).click()
  await completeMfa(page, EMAIL)
  await page.goto('/mi-cuenta')
  await changePassword(page, TEMPORARY, DEMO_PASSWORD)
})

test('desde Mi cuenta se cambia la contraseña y se vuelve a la original', async ({ page }, info) => {
  test.skip(
    !['tablet', 'portatil'].includes(info.project.name),
    'Celular y monitor la prueban junto con la recuperación',
  )
  await signInAs(page, EMAIL)
  await page.goto('/mi-cuenta')

  // La API acepta 5 cambios por minuto por persona y las dos vistas corren seguidas: el error solo se prueba en tablet
  if (info.project.name === 'tablet') {
    await page.getByRole('button', { name: 'Cambiar contraseña' }).click()
    const dialog = page.getByRole('dialog', { name: 'Cambiar la contraseña' })
    await dialog.getByLabel('Contraseña actual').fill('no-es-la-actual')
    await fillNewPassword(page, TEMPORARY)
    await expectAccessible(page)
    await dialog.getByRole('button', { name: 'Cambiar contraseña' }).click()
    // La actual equivocada se marca en su campo, sin cerrar el diálogo
    await expect(dialog.getByText('La contraseña actual no es correcta.')).toBeVisible()
    await dialog.getByRole('button', { name: 'Cancelar' }).click()
    await expect(dialog).toHaveCount(0)
  }

  await changePassword(page, DEMO_PASSWORD, TEMPORARY)
  await changePassword(page, TEMPORARY, DEMO_PASSWORD)
})
