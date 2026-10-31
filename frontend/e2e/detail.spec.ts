// Detalle del incidente en las cuatro vistas: la descripción en una línea con "Ver más", el historial con solo las 5
// entradas más recientes y "Ver toda la actividad", un mensaje que abre el chat en ese mensaje y una nota larga que
// abre la actividad con la nota completa. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, signInAs } from './support'

const SUPERVISOR = 'supervisor@trazo.test'
const DESCRIPTION =
  'La impresora del piso 3 no imprime desde esta mañana. Ya se reinició, se revisó el cable de red y se cambió el ' +
  'tóner, pero sigue mostrando un error de papel atascado aunque la bandeja está vacía y limpia.'
const LONG_NOTE =
  'Se cambió el rodillo de alimentación y se calibró la bandeja. Se dejó imprimiendo una página de prueba cada ' +
  'hora durante el día y no volvió a fallar; si reaparece, hay que pedir el repuesto de la placa al proveedor.'

async function createIncident(page: Page, title: string): Promise<string> {
  const response = await page.request.post('/api/v1/incidents', {
    headers: { ...authHeaderOf(SUPERVISOR), 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: DESCRIPTION, priority: 'medium' },
  })
  expect(response.ok()).toBe(true)
  return ((await response.json()) as { data: { id: string } }).data.id
}

async function changeStatus(page: Page, id: string, status: string, version: number, note?: string): Promise<void> {
  const response = await page.request.put(`/api/v1/incidents/${id}/status`, {
    headers: authHeaderOf(SUPERVISOR),
    data: { status, version, ...(note ? { note } : {}) },
  })
  expect(response.ok()).toBe(true)
}

test('la descripción va en una línea y "Ver más" la despliega completa', async ({ page }, info) => {
  await signInAs(page, SUPERVISOR)
  const id = await createIncident(page, `Descripción ${info.project.name} ${Date.now()}`)
  await page.goto(`/incidentes/${id}`)

  const more = page.getByRole('button', { name: 'Ver más' })
  await expect(more).toHaveAttribute('aria-expanded', 'false')
  await more.click()
  await expect(page.getByRole('button', { name: 'Ver menos' })).toHaveAttribute('aria-expanded', 'true')
  await expect(page.getByText(DESCRIPTION, { exact: true }).last()).toBeVisible()
  await expectAccessible(page)
})

test('el historial muestra 5 entradas y lleva al chat o a la nota completa', async ({ page }, info) => {
  await signInAs(page, SUPERVISOR)
  const id = await createIncident(page, `Historial ${info.project.name} ${Date.now()}`)
  await changeStatus(page, id, 'in_progress', 1)
  await changeStatus(page, id, 'resolved', 2, LONG_NOTE)
  for (const body of ['Primer mensaje', 'Segundo mensaje', 'Tercer mensaje', 'Último mensaje']) {
    await page.request.post(`/api/v1/incidents/${id}/comments`, { headers: authHeaderOf(SUPERVISOR), data: { body } })
  }
  await page.goto(`/incidentes/${id}`)

  // 3 cambios + 4 mensajes = 7; en la página solo las 5 más recientes
  const recent = page.getByRole('list', { name: 'Actividad reciente' })
  await expect(recent.getByRole('listitem')).toHaveCount(5)
  await expect(recent.getByText('creó el incidente')).toHaveCount(0)
  await expect(page.getByRole('button', { name: 'Ver toda la actividad (7)' })).toBeVisible()
  await expectAccessible(page)

  // Un mensaje abre el chat en ese mensaje
  await recent
    .getByRole('button', { name: /Abrir en el chat$/ })
    .first()
    .click()
  const panel = page.getByRole('dialog', { name: 'Chat y actividad' })
  await expect(panel.getByRole('radio', { name: 'Chat (4)' })).toBeChecked()
  await expect(panel.getByText('Último mensaje')).toBeVisible()
  await panel.getByRole('button', { name: 'Cerrar' }).click()

  // La nota larga, recortada en la página, se ve completa en la actividad
  await recent.getByRole('button', { name: 'Ver nota completa' }).click()
  await expect(panel.getByRole('radio', { name: 'Actividad (7)' })).toBeChecked()
  await expect(panel.getByText(LONG_NOTE)).toBeVisible()
  await expect(panel.getByText('creó el incidente')).toBeVisible()
  await expectAccessible(page)
})
