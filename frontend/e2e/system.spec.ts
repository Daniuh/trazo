// Pantalla de sistema. En las cuatro vistas: el panel de salud y un evento provocado a propósito (un ingreso
// bloqueado), que se abre y se marca como revisado. En portátil: una acción con su código de reautenticación. En
// monitor, con dos personas: el modo mantenimiento deja fuera a la administradora y el Super admin sigue trabajando.
// Todo pasa por axe. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Browser, type Page } from '@playwright/test'
import { freshCode } from './mfa'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const ROOT = 'superadmin@trazo.test'

// Cinco contraseñas incorrectas seguidas bloquean el ingreso y dejan el evento. El dominio identifica la corrida
async function provokeLockout(page: Page, domain: string): Promise<void> {
  for (let attempt = 0; attempt < 5; attempt++) {
    await page.request.post('/api/v1/auth/login', {
      data: { email: `bloqueo@${domain}`, password: 'no-es-la-contrasena' },
    })
  }
}

async function confirmWithCode(page: Page, dialogName: string, button: string): Promise<void> {
  const dialog = page.getByRole('dialog', { name: dialogName })
  await expect(dialog).toBeVisible()
  await expectAccessible(page)
  await dialog.getByLabel('Código de la aplicación').fill(await freshCode(ROOT))
  await dialog.getByRole('button', { name: button }).click()
  await expect(page.getByRole('dialog')).toHaveCount(0)
}

async function openAs(browser: Browser, email: string, path: string): Promise<Page> {
  const page = await (await browser.newContext()).newPage()
  await signInAs(page, email)
  await page.goto(path)
  return page
}

test('el Super admin ve la salud y revisa un evento de seguridad', async ({ page }, info) => {
  const domain = `${info.project.name}-${Date.now()}.test`
  await provokeLockout(page, domain)
  await signInAs(page, ROOT)

  await page.goto('/sistema')
  await expect(page).toHaveURL(/\/sistema\/salud/)
  await expect(page.getByRole('article', { name: /^Base de datos:/ })).toBeVisible()
  await expect(page.getByRole('article', { name: /^Modo mantenimiento: Apagado/ })).toBeVisible()
  await expectAccessible(page)

  await page.getByRole('radio', { name: 'Eventos' }).click()
  await page.goto('/sistema/eventos?type=login_locked')
  const event = page.getByLabel(/^Ingreso bloqueado por intentos fallidos:/).first()
  await expect(event).toBeVisible()
  await event.click()

  const detail = info.project.name === 'monitor' ? page.getByRole('complementary') : page.getByRole('dialog')
  await expect(detail.getByText(`bl***@${domain}`)).toBeVisible()
  await expectAccessible(page)
  await detail.getByRole('button', { name: 'Marcar como revisado' }).click()
  await expect(page.getByText('Evento marcado como revisado.')).toBeVisible()
  await expect(detail.getByRole('button', { name: 'Marcar como resuelto' })).toBeVisible()
  await expect(detail.getByRole('button', { name: 'Marcar como revisado' })).toHaveCount(0)
})

test('una acción de sistema pide el código de la aplicación', async ({ page }, info) => {
  test.skip(info.project.name !== 'portatil', 'Basta una vista: el diálogo es el mismo en todas')

  await signInAs(page, ROOT)
  await page.goto('/sistema/salud')
  await page.getByRole('button', { name: 'Limpiar caché de datos' }).click()
  await confirmWithCode(page, 'Limpiar la caché de datos', 'Limpiar')

  await expect(page.getByText('Caché de datos limpiada.')).toBeVisible()
})

test('el modo mantenimiento deja fuera a los demás y el Super admin sigue trabajando', async ({
  page,
  browser,
}, info) => {
  test.skip(info.project.name !== 'monitor', 'Con dos personas basta una vista ancha')

  await signInAs(page, ROOT)
  await page.goto('/sistema/salud')
  try {
    await page.getByRole('button', { name: 'Activar' }).click()
    await confirmWithCode(page, 'Activar el modo mantenimiento', 'Activar')
    await expect(page.getByText('Modo mantenimiento activado.')).toBeVisible()
    await expect(page.getByRole('article', { name: /^Modo mantenimiento: Activo/ })).toBeVisible()
    await expect(page.getByText('Modo mantenimiento activo: solo quienes administran el sistema')).toBeVisible()
    await expectAccessible(page)

    const admin = await openAs(browser, 'admin@trazo.test', '/')
    await expect(admin.getByText('Trazo está en mantenimiento')).toBeVisible()
    await expectAccessible(admin)

    await page.getByRole('button', { name: 'Desactivar' }).click()
    await confirmWithCode(page, 'Desactivar el modo mantenimiento', 'Desactivar')
    await expect(page.getByRole('article', { name: /^Modo mantenimiento: Apagado/ })).toBeVisible()

    await admin.getByRole('button', { name: 'Reintentar' }).click()
    await expect(admin.getByRole('heading', { level: 1 })).toBeVisible()
    await expect(admin.getByText('Trazo está en mantenimiento')).toHaveCount(0)
  } finally {
    // Si algo falló a mitad, el mantenimiento no puede quedar activo para las demás pruebas
    await page.request.put('/api/v1/system/maintenance', {
      headers: authHeaderOf(ROOT),
      data: { enabled: false, mfa_code: await freshCode(ROOT) },
    })
  }
})
