// Chat del incidente en las cuatro vistas, dentro del panel del detalle: se abre desde la ficha, se escribe con Enter,
// la pestaña Actividad lleva de vuelta al mensaje, se borra el propio y es accesible.
// En portátil, la respuesta de otra persona llega sola, sin recargar. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, gotoSubscribed, signInAs } from './support'

const SUPERVISOR = 'supervisor@trazo.test'
const AGENT = 'agente2@trazo.test'

async function createIncident(page: Page, title: string): Promise<string> {
  const response = await page.request.post('/api/v1/incidents', {
    headers: { ...authHeaderOf(SUPERVISOR), 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: 'Incidente para probar la conversación.', priority: 'low' },
  })
  expect(response.ok()).toBe(true)
  return ((await response.json()) as { data: { id: string } }).data.id
}

test('el chat se abre desde la ficha, se escribe con Enter, se borra el propio y es accesible', async ({
  page,
}, info) => {
  await signInAs(page, SUPERVISOR)
  const id = await createIncident(page, `Conversación ${info.project.name} ${Date.now()}`)
  await page.goto(`/incidentes/${id}`)

  await expect(page.getByRole('list', { name: 'Actividad reciente' }).getByText('creó el incidente')).toBeVisible()
  await expectAccessible(page)

  await page.getByRole('button', { name: 'Chat (0)' }).click()
  const panel = page.getByRole('dialog', { name: 'Chat y actividad' })
  const chat = panel.getByRole('log', { name: 'Mensajes del incidente' })
  await expect(panel.getByRole('radio', { name: 'Chat (0)' })).toBeChecked()

  await panel.getByRole('textbox', { name: 'Mensaje' }).fill('¿Alguien puede revisarlo hoy?')
  await panel.getByRole('textbox', { name: 'Mensaje' }).press('Enter')
  await expect(chat.getByText('¿Alguien puede revisarlo hoy?')).toBeVisible()
  await expect(panel.getByRole('radio', { name: 'Chat (1)' })).toBeChecked()
  await expectAccessible(page)

  // En Actividad están los cambios y el mensaje; el mensaje lleva de vuelta al chat
  await panel.getByRole('radio', { name: /^Actividad/ }).click()
  await expect(panel.getByText('creó el incidente')).toBeVisible()
  await expect(panel.getByRole('textbox', { name: 'Mensaje' })).toHaveCount(0)
  await panel.getByRole('button', { name: /Abrir en el chat$/ }).click()
  await expect(panel.getByRole('radio', { name: 'Chat (1)' })).toBeChecked()

  await chat.getByRole('button', { name: 'Borrar' }).click()
  await page.getByRole('alertdialog').getByRole('button', { name: 'Borrar' }).click()
  await expect(chat.getByText('Mensaje eliminado')).toBeVisible()
})

test('la respuesta de otra persona llega sola al chat', async ({ page, browser }, info) => {
  test.skip(info.project.name !== 'portatil', 'Basta una vista')
  await signInAs(page, SUPERVISOR)
  const id = await createIncident(page, `Conversación en vivo ${Date.now()}`)
  // El agente atiende el incidente para poder verlo
  const agentId = (
    (await (await page.request.get('/api/v1/incidents/assignees', { headers: authHeaderOf(SUPERVISOR) })).json()) as {
      data: { id: string; name: string }[]
    }
  ).data.find((person) => person.name === 'Andrés Castro')?.id
  await page.request.put(`/api/v1/incidents/${id}/assignee`, {
    headers: authHeaderOf(SUPERVISOR),
    data: { assignee: agentId, version: 1 },
  })

  await gotoSubscribed(page, `/incidentes/${id}`, 'incidents.all')
  await page.getByRole('button', { name: 'Chat (0)' }).click()
  const chat = page
    .getByRole('dialog', { name: 'Chat y actividad' })
    .getByRole('log', { name: 'Mensajes del incidente' })

  const agent = await (await browser.newContext()).newPage()
  try {
    await signInAs(agent, AGENT)
    await agent.request.post(`/api/v1/incidents/${id}/comments`, {
      headers: authHeaderOf(AGENT),
      data: { body: 'Ya lo estoy revisando.' },
    })
  } finally {
    await agent.context().close()
  }

  await expect(chat.getByText('Ya lo estoy revisando.')).toBeVisible()
  await expect(chat.getByText('Andrés Castro', { exact: true })).toBeVisible()
})
