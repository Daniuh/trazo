// Acciones de sistema de punta a punta contra el Docker real: "Reiniciar workers" y "Reiniciar" el tiempo real hacen que
// el proceso termine y Docker lo levante de nuevo, y la app se recupera sola. Reinician contenedores compartidos, así que
// no corren en la suite normal: npm run test:e2e:ops. Documentado en docs/calidad/estrategia-de-pruebas.md
import { execSync } from 'node:child_process'
import { expect, test, type Page } from '@playwright/test'
import { freshCode } from './mfa'
import { signInAs } from './support'

const ROOT = 'superadmin@trazo.test'

const restarts = (container: string) =>
  Number(execSync(`docker inspect ${container} --format {{.RestartCount}}`).toString().trim())

async function confirmWithCode(page: Page, dialogName: string, button: string): Promise<void> {
  const dialog = page.getByRole('dialog', { name: dialogName })
  await dialog.getByLabel('Código de la aplicación').fill(await freshCode(ROOT))
  await dialog.getByRole('button', { name: button }).click()
  await expect(page.getByRole('dialog')).toHaveCount(0)
}

test.describe('@operacion acciones de sistema reales', () => {
  test.skip(({ isMobile }) => isMobile, 'Una vista ancha basta')
  test.setTimeout(240_000)

  test('reiniciar workers: el contenedor se reinicia y el panel vuelve a "Bien"', async ({ page }) => {
    const before = restarts('trazo-worker-1')
    await signInAs(page, ROOT)
    await page.goto('/sistema/salud')

    await page.getByRole('button', { name: 'Reiniciar workers' }).click()
    await confirmWithCode(page, 'Reiniciar workers', 'Reiniciar')
    await expect(page.getByText('Workers reiniciados.')).toBeVisible()

    // El worker termina cuando revisa la señal (cada pocos segundos) y Docker lo levanta
    await expect.poll(() => restarts('trazo-worker-1'), { timeout: 60_000 }).toBeGreaterThan(before)
    // Al minuto siguiente el scheduler encola la señal y el worker nuevo la procesa
    await expect
      .poll(
        async () => {
          await page.getByRole('button', { name: 'Actualizar' }).click()
          return page.getByRole('article', { name: /^Workers:/ }).getAttribute('aria-label')
        },
        { timeout: 150_000, intervals: [10_000] },
      )
      .toBe('Workers: Bien')
  })

  test('reiniciar el tiempo real: Reverb vuelve y las pantallas se reconectan solas', async ({ page }) => {
    const before = restarts('trazo-reverb-1')
    await signInAs(page, ROOT)
    await page.goto('/sistema/salud')
    await page.waitForResponse((response) => response.url().includes('/broadcasting/auth') && response.ok())

    await page.getByRole('button', { name: 'Reiniciar', exact: true }).click()
    // Al reconectarse, la pestaña vuelve a pedir la firma de sus canales
    const resubscribed = page.waitForResponse(
      (response) => response.url().includes('/broadcasting/auth') && response.ok(),
      { timeout: 120_000 },
    )
    await confirmWithCode(page, 'Reiniciar el tiempo real', 'Reiniciar')

    await expect.poll(() => restarts('trazo-reverb-1'), { timeout: 60_000 }).toBeGreaterThan(before)
    await resubscribed
    await page.getByRole('button', { name: 'Actualizar' }).click()
    await expect(page.getByRole('article', { name: 'Tiempo real: Bien' })).toBeVisible()
  })
})
