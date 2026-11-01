// Verificación en dos pasos en las cuatro vistas, con una cuenta propia (agente3): la primera vez se configura desde la
// pantalla (QR, clave y códigos de recuperación) y las siguientes se ingresa con el código. Revisa la accesibilidad.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test } from '@playwright/test'
import { freshCode } from './mfa'
import { DEMO_PASSWORD, expectAccessible, setUpMfa } from './support'

const EMAIL = 'agente3@trazo.test'

test('se configura la verificación en dos pasos y luego se ingresa con el código', async ({ page }) => {
  await page.goto('/ingresar')
  await page.getByLabel('Correo electrónico').fill(EMAIL)
  await page.getByLabel('Contraseña', { exact: true }).fill(DEMO_PASSWORD)
  await page.getByRole('button', { name: 'Ingresar' }).click()
  await page.waitForURL((url) => url.pathname !== '/ingresar')

  if (new URL(page.url()).pathname === '/configurar-mfa') {
    // Primera vez: configurarla desde la pantalla
    await expect(page.getByRole('img', { name: /Código QR/ })).toBeVisible()
    await expectAccessible(page)
    await setUpMfa(page, EMAIL)
    await expectAccessible(page)
    await page.getByRole('button', { name: 'Continuar' }).click()
  } else {
    // Ya la tiene: un código incorrecto avisa cuántos intentos quedan, y el correcto deja entrar
    await expect(page).toHaveURL(/\/ingresar\/verificar/)
    await expectAccessible(page)
    await page.getByLabel('Código de verificación').fill('000000')
    await page.getByRole('button', { name: 'Verificar' }).click()
    await expect(page.getByRole('alert')).toContainText('El código no es correcto')
    await expect(page.getByRole('alert')).toContainText('Te quedan 4 intentos')

    await page.getByLabel('Código de verificación').fill(await freshCode(EMAIL))
    await page.getByRole('button', { name: 'Verificar' }).click()
  }

  await expect(page).toHaveURL(/\/$/)
  await page.goto('/mi-cuenta')
  await expect(page.getByText('Activa', { exact: true })).toBeVisible()
  await expectAccessible(page)
})
