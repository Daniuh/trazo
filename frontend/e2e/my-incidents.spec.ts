// "Mis incidentes" en las cuatro vistas: se abre desde el listado, una tarjeta pasa de Abierto a En progreso (arrastrando
// en portátil y monitor, con "Mover a…" en celular y tablet), la Lista, el Calendario y Mi día lo muestran, y todo es
// accesible.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const SUPERVISOR = 'supervisor@trazo.test'
const AGENT = 'agente2@trazo.test'

// Incidente nuevo asignado al agente 2 por el supervisor
async function assignedTo(page: Page, title: string, dueDate?: string): Promise<string> {
  await signInAs(page, SUPERVISOR)
  const headers = authHeaderOf(SUPERVISOR)
  const created = await page.request.post('/api/v1/incidents', {
    headers: { ...headers, 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: 'Incidente para probar el tablero.', priority: 'low', due_date: dueDate },
  })
  const { id } = ((await created.json()) as { data: { id: string } }).data
  const people = (await (await page.request.get('/api/v1/incidents/assignees', { headers })).json()) as {
    data: { id: string; name: string }[]
  }
  const agent = people.data.find((person) => person.name === 'Andrés Castro')?.id
  const assigned = await page.request.put(`/api/v1/incidents/${id}/assignee`, {
    headers,
    data: { assignee: agent, version: 1 },
  })
  expect(assigned.ok()).toBe(true)
  return id
}

test('el tablero mueve una tarjeta de estado y la lista muestra lo mismo', async ({ page }, info) => {
  const title = `Tablero ${info.project.name} ${Date.now()}`
  await assignedTo(page, title)
  await signInAs(page, AGENT)

  await page.goto('/incidentes')
  await page.getByRole('link', { name: 'Mis incidentes' }).click()
  await expect(page.getByRole('heading', { name: 'Mis incidentes', level: 1 })).toBeVisible()

  const card = page.getByRole('article').filter({ hasText: title })
  const desktop = info.project.name === 'portatil' || info.project.name === 'monitor'
  if (!desktop) await page.getByRole('tab', { name: /^Abierto/ }).click()
  await expect(card).toBeVisible()
  await expectAccessible(page)

  if (desktop) {
    await card.dragTo(page.getByRole('region', { name: 'En progreso' }))
  } else {
    await card.getByRole('button', { name: /a otro estado$/ }).click()
    await page.getByRole('menuitem', { name: 'En progreso' }).click()
  }
  const dialog = page.getByRole('dialog', { name: '¿Pasar a "En progreso"?' })
  await dialog.getByRole('button', { name: 'Pasar a En progreso' }).click()
  await expect(dialog).toBeHidden()

  if (!desktop) await page.getByRole('tab', { name: /^En progreso/ }).click()
  await expect(
    page.getByRole('region', { name: 'En progreso' }).getByRole('article').filter({ hasText: title }),
  ).toBeVisible()

  await page.getByRole('radio', { name: 'Lista' }).click()
  await expect(page).toHaveURL(/vista=lista/)
  // Exacto y dentro del contenido: el aviso "Te asignaron…" también lleva el título
  await expect(page.locator('main').getByText(title, { exact: true })).toBeVisible()
  await expectAccessible(page)
})

test('el calendario y Mi día muestran el incidente', async ({ page }, info) => {
  const title = `Calendario ${info.project.name} ${Date.now()}`
  // Un día lejano y al azar, para no compartirlo con lo que dejan otras corridas (el día muestra hasta 3)
  const due = new Date(Date.now() + (200 + Math.floor(Math.random() * 400)) * 86_400_000)
  const id = await assignedTo(page, title, due.toISOString())
  await signInAs(page, AGENT)
  // En progreso: así aparece también en Mi día
  const headers = authHeaderOf(AGENT)
  const { version } = (
    (await (await page.request.get(`/api/v1/incidents/${id}`, { headers })).json()) as {
      data: { version: number }
    }
  ).data
  await page.request.put(`/api/v1/incidents/${id}/status`, { headers, data: { status: 'in_progress', version } })
  const month = `${due.getFullYear()}-${String(due.getMonth() + 1).padStart(2, '0')}`

  await page.goto(`/incidentes/mios?vista=calendario&mes=${month}`)
  const link = page.locator('main').getByRole('link', { name: new RegExp(title) })
  await expect(link).toBeVisible()
  await expectAccessible(page)

  await page.getByRole('radio', { name: 'Mi día' }).click()
  await expect(page).toHaveURL(/vista=dia/)
  await expect(page.locator('main').getByRole('link', { name: title, exact: true })).toBeVisible()
  await expectAccessible(page)
})

test('quien no atiende incidentes no ve el botón', async ({ page }, info) => {
  test.skip(info.project.name !== 'portatil', 'Basta una vista')
  await signInAs(page, 'solicitante2@trazo.test')
  await page.goto('/incidentes')

  await expect(page.getByRole('heading', { name: 'Incidentes', level: 1 })).toBeVisible()
  await expect(page.getByRole('link', { name: 'Mis incidentes' })).toHaveCount(0)
})
