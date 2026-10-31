// Dashboard en las cuatro vistas: cada rol ve su resumen, las cifras llevan al listado filtrado y la página es accesible.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test } from '@playwright/test'
import { expectAccessible, signInAs } from './support'

test('el supervisor ve el resumen general, accesible, y "Vencidos" lleva al listado filtrado', async ({ page }) => {
  await signInAs(page, 'supervisor@trazo.test')
  await page.goto('/')

  await expect(page.getByRole('heading', { level: 1, name: 'Resumen general' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Carga por agente' })).toBeVisible()
  await expect(page.getByRole('list', { name: 'Incidentes por estado' })).toBeVisible()
  await expectAccessible(page)

  await page.getByRole('link', { name: /^Vencidos: .*Ver en el listado$/ }).click()

  await expect(page).toHaveURL(/\/incidentes\?overdue=1$/)
  await expect(page.getByRole('button', { name: 'Quitar filtro Vencidos' })).toBeVisible()
})

test('el solicitante ve su resumen sin los widgets de gestión', async ({ page }) => {
  await signInAs(page, 'solicitante1@trazo.test')
  await page.goto('/')

  await expect(page.getByRole('heading', { level: 1, name: 'Mi resumen' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Total de incidentes' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Carga por agente' })).toHaveCount(0)
  await expect(page.getByRole('heading', { name: 'Sin asignar' })).toHaveCount(0)
})

test('la tendencia cambia de período', async ({ page }) => {
  await signInAs(page, 'agente1@trazo.test')
  await page.goto('/')

  const period = page.getByRole('radiogroup', { name: 'Período' })
  await period.getByRole('radio', { name: '7 días' }).click()

  await expect(period.getByRole('radio', { name: '7 días' })).toHaveAttribute('aria-checked', 'true')
  await expect(page.getByRole('table', { name: /últimos 7 días/ }).locator('tbody tr')).toHaveCount(7)
})
