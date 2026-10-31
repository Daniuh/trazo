// Centro de notificaciones en las cuatro vistas: un mensaje de otra persona aparece en la campana y lleva a la
// conversación abierta. En portátil, un cambio de estado llega solo al panel abierto. En monitor, un crítico sin asignar
// avisa al supervisor (aviso de coordinación). Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, gotoSubscribed, signInAs } from './support'

const SUPERVISOR = 'supervisor@trazo.test'
const AGENT = 'agente2@trazo.test'
const REQUESTER = 'solicitante2@trazo.test'

// Incidente del supervisor, atendido por el agente 2 (así el agente puede verlo y cambiarlo)
async function assignedIncident(page: Page, title: string): Promise<{ id: string; version: number }> {
  const headers = authHeaderOf(SUPERVISOR)
  const created = await page.request.post('/api/v1/incidents', {
    headers: { ...headers, 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: 'Incidente para probar las notificaciones.', priority: 'low' },
  })
  const { id } = ((await created.json()) as { data: { id: string } }).data
  const people = (await (await page.request.get('/api/v1/incidents/assignees', { headers })).json()) as {
    data: { id: string; name: string }[]
  }
  const agentId = people.data.find((person) => person.name === 'Andrés Castro')?.id
  const assigned = await page.request.put(`/api/v1/incidents/${id}/assignee`, {
    headers,
    data: { assignee: agentId, version: 1 },
  })
  return { id, version: ((await assigned.json()) as { data: { version: number } }).data.version }
}

// Lo que hace otra persona desde su propia sesión, por la API
async function asPerson(
  page: Page,
  email: string,
  act: (headers: Record<string, string>) => Promise<unknown>,
): Promise<void> {
  const other = await page.context().browser()?.newContext()
  if (!other) throw new Error('Sin navegador para la segunda persona')
  try {
    await signInAs(await other.newPage(), email)
    await act(authHeaderOf(email))
  } finally {
    await other.close()
  }
}

const asAgent = (page: Page, act: (headers: Record<string, string>) => Promise<unknown>) => asPerson(page, AGENT, act)

test('un mensaje nuevo aparece en la campana y lleva a la conversación', async ({ page }, info) => {
  const title = `Aviso ${info.project.name} ${Date.now()}`
  await signInAs(page, SUPERVISOR)
  const { id } = await assignedIncident(page, title)
  await asAgent(page, (headers) =>
    page.request.post(`/api/v1/incidents/${id}/comments`, {
      headers,
      data: { body: 'Necesito el número del equipo.' },
    }),
  )

  await page.goto('/')
  // La notificación se crea en la cola: puede tardar un momento
  await expect(async () => {
    await page.reload()
    await page.getByRole('button', { name: /sin leer$/ }).click()
    await expect(page.getByRole('dialog', { name: 'Notificaciones' }).getByText(title)).toBeVisible({ timeout: 3000 })
  }).toPass({ timeout: 30_000 })

  const item = page.getByRole('dialog', { name: 'Notificaciones' }).getByRole('listitem').filter({ hasText: title })
  await expect(item).toContainText('Andrés Castro escribió')
  await expect(item).toContainText('Necesito el número del equipo.')
  await expectAccessible(page)

  await item.getByRole('button').click()
  await expect(page).toHaveURL(new RegExp(`/incidentes/${id}$`))
  // Abre el chat del incidente, listo para responder
  const panel = page.getByRole('dialog', { name: 'Chat y actividad' })
  await expect(
    panel.getByRole('log', { name: 'Mensajes del incidente' }).getByText('Necesito el número del equipo.'),
  ).toBeVisible()
  await expect(panel.getByRole('textbox', { name: 'Mensaje' })).toBeFocused()
})

test('un cambio de estado llega solo al panel abierto', async ({ page }, info) => {
  test.skip(info.project.name !== 'portatil', 'Basta una vista')
  const title = `Estado en vivo ${Date.now()}`
  await signInAs(page, SUPERVISOR)
  const { id, version } = await assignedIncident(page, title)
  const me = (
    (await (await page.request.get('/api/v1/auth/me', { headers: authHeaderOf(SUPERVISOR) })).json()) as {
      data: { id: string }
    }
  ).data.id

  await gotoSubscribed(page, '/', `users.${me}`)
  await page.getByRole('button', { name: /^Notificaciones/ }).click()
  const center = page.getByRole('dialog', { name: 'Notificaciones' })
  await center.getByRole('radio', { name: /^Actividad/ }).click()

  await asAgent(page, (headers) =>
    page.request.put(`/api/v1/incidents/${id}/status`, { headers, data: { status: 'in_progress', version } }),
  )

  const item = center.getByRole('listitem').filter({ hasText: title })
  await expect(item).toContainText('Andrés Castro lo pasó a En progreso', { timeout: 30_000 })
  await expect(item).toContainText('Sin leer')
})

test('un crítico reportado sin asignar le avisa a quien reparte el trabajo', async ({ page }, info) => {
  test.skip(info.project.name !== 'monitor', 'Basta una vista')
  const title = `Crítico sin asignar ${Date.now()}`
  await signInAs(page, SUPERVISOR)
  const me = (
    (await (await page.request.get('/api/v1/auth/me', { headers: authHeaderOf(SUPERVISOR) })).json()) as {
      data: { id: string }
    }
  ).data.id

  await gotoSubscribed(page, '/', `users.${me}`)
  await page.getByRole('button', { name: /^Notificaciones/ }).click()
  const center = page.getByRole('dialog', { name: 'Notificaciones' })
  await center.getByRole('radio', { name: /^Actividad/ }).click()

  await asPerson(page, REQUESTER, (headers) =>
    page.request.post('/api/v1/incidents', {
      headers: { ...headers, 'Idempotency-Key': crypto.randomUUID() },
      data: { title, description: 'El servidor de correo no responde.', priority: 'critical' },
    }),
  )

  const item = center.getByRole('listitem').filter({ hasText: title })
  await expect(item).toContainText('reportó un incidente crítico sin asignar', { timeout: 30_000 })
  await expectAccessible(page)
})
