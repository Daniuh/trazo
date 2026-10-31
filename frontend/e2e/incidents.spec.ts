// Flujo crítico de incidentes en las cuatro vistas: crear, encontrarlo en el listado, cambiar su estado, y
// accesibilidad de cada pantalla. En portátil, el aviso cuando la pantalla no se descarga.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test } from '@playwright/test'
import { expectAccessible, signInAs } from './support'

test('crear, encontrar en el listado y cambiar el estado', async ({ page }, info) => {
  const title = `E2E ${info.project.name} ${Date.now()}`
  await signInAs(page, 'supervisor@trazo.test')

  await page.goto('/incidentes/nuevo')
  await expect(page.getByLabel('Título')).toBeVisible()
  await expectAccessible(page)

  await page.getByLabel('Título').fill(title)
  await page.getByLabel('Descripción').fill('Creado por la prueba de extremo a extremo.')
  await page.getByRole('button', { name: 'Crear incidente' }).click()

  await expect(page.getByRole('heading', { level: 1, name: title })).toBeVisible()
  await expect(page.getByText('Abierto', { exact: true }).first()).toBeVisible()
  await expectAccessible(page)

  await page.getByRole('button', { name: 'Cambiar estado' }).click()
  await page.getByRole('menuitem', { name: 'En progreso' }).click()
  await expect(page.getByText('En progreso', { exact: true }).first()).toBeVisible()
  await expect(page.getByText('cambió el estado')).toBeVisible()

  await page.goto(`/incidentes?search=${encodeURIComponent(title)}`)
  await expect(page.getByText(title)).toBeVisible()
  await expectAccessible(page)
})

test('si una pantalla no se descarga, el menú avisa y "Recargar" la abre', async ({ page }, info) => {
  test.skip(info.project.name !== 'portatil', 'Basta una vista')
  await signInAs(page, 'supervisor@trazo.test')
  await page.goto('/')
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible()

  // Como si se hubiera publicado una versión nueva: el archivo de la pantalla ya no existe
  const screenFile = /IncidentListPage[^/]*\.(vue|js)/
  await page.route(screenFile, (route) => route.abort())
  await page.getByRole('navigation').getByRole('link', { name: 'Incidentes' }).click()

  const notice = page.getByText('No pudimos cargar esta pantalla')
  await expect(notice).toBeVisible()
  await expectAccessible(page)

  await page.unroute(screenFile)
  await page.getByRole('button', { name: 'Recargar' }).click()
  await expect(page).toHaveURL(/\/incidentes$/)
  await expect(page.getByRole('heading', { level: 1, name: 'Incidentes' })).toBeVisible()
})

test('los filtros viven en la URL y se pueden quitar', async ({ page }) => {
  await signInAs(page, 'supervisor@trazo.test')

  await page.goto('/incidentes?status=open&priority=critical')
  await expect(page.getByRole('button', { name: 'Quitar filtro Abierto' })).toBeVisible()

  await page.getByRole('button', { name: 'Quitar filtro Abierto' }).click()

  await expect(page).toHaveURL(/priority=critical/)
  await expect(page).not.toHaveURL(/status=open/)
})

test('el solicitante no ve los filtros de gestión', async ({ page }) => {
  await signInAs(page, 'solicitante1@trazo.test')
  await page.goto('/incidentes')

  await expect(page.getByRole('heading', { level: 1, name: 'Incidentes' })).toBeVisible()
  // Exacto: un título de incidente puede contener la palabra "asignado"
  await expect(page.getByLabel('Asignado', { exact: true })).toHaveCount(0)
  // "Más filtros" en portátil y monitor; "Filtros" (panel inferior) en celular y tablet
  await page.getByRole('button', { name: /^(Más filtros|Filtros)/ }).click()
  await expect(page.getByText('Ver eliminados')).toHaveCount(0)
})
