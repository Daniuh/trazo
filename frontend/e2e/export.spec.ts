// Exportación en las cuatro vistas: el supervisor filtra el listado, confirma y descarga un CSV para Excel con
// exactamente lo filtrado. Un agente no ve la opción. Todo pasa por axe. Documentado en docs/calidad/estrategia-de-pruebas.md
import { readFile } from 'node:fs/promises'
import { expect, test } from '@playwright/test'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const SUPERVISOR = 'supervisor@trazo.test'

test('el supervisor exporta lo que tiene filtrado', async ({ page }, info) => {
  const compact = info.project.name === 'celular' || info.project.name === 'tablet'
  await signInAs(page, SUPERVISOR)
  await page.goto('/')
  const title = `Exportación ${info.project.name} ${Date.now()}`
  const created = await page.request.post('/api/v1/incidents', {
    headers: { ...authHeaderOf(SUPERVISOR), 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: 'Incidente creado por la prueba de exportación.', priority: 'high' },
  })
  expect(created.ok()).toBe(true)
  const code = ((await created.json()) as { data: { code: string } }).data.code

  await page.goto(`/incidentes?search=${code}`)
  await expect(page.getByText(title).first()).toBeVisible()

  if (compact) {
    await page.getByRole('button', { name: 'Más opciones del listado' }).click()
    await page.getByRole('menuitem', { name: 'Exportar CSV' }).click()
  } else {
    await page.getByRole('button', { name: 'Exportar' }).click()
  }

  const dialog = page.getByRole('dialog')
  await expect(dialog.getByText('Se exportarán 1 incidente')).toBeVisible()
  await expectAccessible(page)

  const downloading = page.waitForEvent('download')
  await dialog.getByRole('button', { name: 'Exportar' }).click()
  const download = await downloading

  expect(download.suggestedFilename()).toMatch(/^incidentes-\d{4}-\d{2}-\d{2}-\d{4}\.csv$/)
  const content = await readFile(await download.path(), 'utf8')
  // Empieza con la marca UTF-8 (BOM) para que Excel lea bien las tildes
  expect(content.charCodeAt(0)).toBe(0xfeff)
  const lines = content.slice(1).trim().split('\r\n')
  expect(lines).toHaveLength(2)
  expect(lines[0]).toContain('Código;Título;Estado;Prioridad')
  // Las celdas con espacios van entre comillas, como manda el formato CSV
  expect(lines[1]).toContain(`${code};"${title}";Abierto;Alta`)
  await expect(page.getByText('Exportación lista')).toBeVisible()
})

test('un agente no ve la opción de exportar', async ({ page }, info) => {
  test.skip(info.project.name !== 'celular' && info.project.name !== 'portatil', 'Basta una vista angosta y una ancha')

  await signInAs(page, 'agente1@trazo.test')
  await page.goto('/incidentes')
  await expect(page.getByRole('heading', { level: 1, name: 'Incidentes' })).toBeVisible()

  await expect(page.getByRole('button', { name: 'Exportar' })).toHaveCount(0)
  await expect(page.getByRole('button', { name: 'Más opciones del listado' })).toHaveCount(0)
})
