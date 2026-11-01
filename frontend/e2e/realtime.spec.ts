// Tiempo real con dos personas a la vez: una cambia algo por la API y la otra lo ve sin recargar. En las cuatro
// vistas, el aviso del listado; en portátil, el detalle que se actualiza solo; en monitor, el aviso "Te asignaron";
// en tablet, el menú que cambia cuando le conceden un permiso. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Browser, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const SUPERVISOR = 'supervisor@trazo.test'
const ROOT = 'superadmin@trazo.test'

interface IncidentBody {
  data: { id: string; code: string; version: number }
}

async function createIncident(page: Page, title: string): Promise<IncidentBody['data']> {
  const response = await page.request.post('/api/v1/incidents', {
    headers: { ...authHeaderOf(SUPERVISOR), 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: 'Incidente creado por la prueba de tiempo real.', priority: 'low' },
  })
  expect(response.ok()).toBe(true)
  return ((await response.json()) as IncidentBody).data
}

// La segunda persona, en su propio navegador
async function openAs(browser: Browser, email: string, path: string): Promise<Page> {
  const page = await (await browser.newContext()).newPage()
  await signInAs(page, email)
  await page.goto(path)
  return page
}

// Da tiempo a que la pestaña se conecte y se suscriba antes de provocar el cambio
async function waitForRealtime(page: Page): Promise<void> {
  await page.waitForResponse((response) => response.url().includes('/broadcasting/auth') && response.ok())
}

test('el listado avisa los cambios de otras personas sin mover las filas', async ({ page }, info) => {
  await signInAs(page, SUPERVISOR)
  const subscribed = waitForRealtime(page)
  await page.goto('/incidentes')
  await expect(page.getByRole('heading', { level: 1, name: 'Incidentes' })).toBeVisible()
  await subscribed

  const title = `Tiempo real ${info.project.name} ${Date.now()}`
  await createIncident(page, title)

  const notice = page.getByRole('status').filter({ hasText: 'con cambios de otras personas' })
  await expect(notice).toBeVisible()
  await expect(page.getByText(title)).toHaveCount(0)
  await expectAccessible(page)

  await notice.getByRole('button', { name: 'Actualizar' }).click()
  await expect(page.getByText(title).first()).toBeVisible()
  await expect(notice).toHaveCount(0)
})

test('el detalle se actualiza solo cuando otra persona cambia el estado', async ({ page }, info) => {
  test.skip(info.project.name !== 'portatil', 'Basta una vista')
  await signInAs(page, SUPERVISOR)
  await page.goto('/')
  const incident = await createIncident(page, `Detalle en vivo ${Date.now()}`)

  const subscribed = waitForRealtime(page)
  await page.goto(`/incidentes/${incident.id}`)
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
  await subscribed

  const response = await page.request.put(`/api/v1/incidents/${incident.id}/status`, {
    headers: authHeaderOf(SUPERVISOR),
    data: { status: 'in_progress', version: incident.version },
  })
  expect(response.ok()).toBe(true)

  await expect(page.getByText('En progreso').first()).toBeVisible()
  await expect(page.getByText(/cambió el estado/).first()).toBeVisible()
})

test('a quien le asignan un incidente le llega el aviso con "Abrir"', async ({ page, browser }, info) => {
  test.skip(info.project.name !== 'monitor', 'Basta una vista')
  await signInAs(page, SUPERVISOR)
  await page.goto('/')
  const incident = await createIncident(page, `Asignación en vivo ${Date.now()}`)

  const agent = await openAs(browser, 'agente2@trazo.test', '/')
  await waitForRealtime(agent)
  const me = await page.request.get('/api/v1/auth/me', { headers: authHeaderOf('agente2@trazo.test') })
  const agentId = ((await me.json()) as { data: { id: string } }).data.id

  const response = await page.request.put(`/api/v1/incidents/${incident.id}/assignee`, {
    headers: authHeaderOf(SUPERVISOR),
    data: { assignee: agentId, version: incident.version },
  })
  expect(response.ok()).toBe(true)

  await expect(agent.getByText(`Te asignaron ${incident.code}`)).toBeVisible()
  await agent.getByRole('button', { name: 'Abrir' }).click()
  await expect(agent).toHaveURL(new RegExp(`/incidentes/${incident.id}`))
  await agent.context().close()
})

test('el menú cambia en vivo cuando le conceden un permiso a la persona', async ({ page, browser }, info) => {
  test.skip(info.project.name !== 'tablet', 'Basta una vista')
  await signInAs(page, ROOT)
  await page.goto('/')
  const agent = await openAs(browser, 'agente3@trazo.test', '/')
  await waitForRealtime(agent)
  await expect(agent.getByRole('link', { name: 'Auditoría' })).toHaveCount(0)

  const headers = authHeaderOf(ROOT)
  const users = (await (await page.request.get('/api/v1/users?search=agente3', { headers })).json()) as {
    data: { id: string }[]
  }
  const id = users.data[0]?.id
  const detail = (await (await page.request.get(`/api/v1/users/${id}`, { headers })).json()) as {
    data: { permissions: { effective: string[] } }
  }
  const original = detail.data.permissions.effective
  const grant = await page.request.put(`/api/v1/users/${id}/permissions`, {
    headers,
    data: { permissions: [...original, 'audit.incidents.view'] },
  })
  expect(grant.ok()).toBe(true)

  try {
    await expect(agent.getByRole('link', { name: 'Auditoría' })).toBeVisible()
  } finally {
    await page.request.put(`/api/v1/users/${id}/permissions`, { headers, data: { permissions: original } })
    await agent.context().close()
  }
})
