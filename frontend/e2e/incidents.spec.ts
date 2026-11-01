// Flujo crítico de incidentes en las cuatro vistas: crear, encontrarlo en el listado, cambiar su estado (con la nota
// que piden Resuelto y Cancelado), cerrarlo y reabrirlo, la lista agrupada y accesibilidad de cada pantalla. En portátil, la reapertura
// que pide un agente y aprueba quien supervisa, y el aviso cuando la pantalla no se descarga.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test, type Page } from '@playwright/test'
import { authHeaderOf, expectAccessible, signInAs } from './support'

// Lleva al estado con el botón del siguiente paso o, si no es ese, con "Cambiar a…" del menú ⋯
async function pickStatus(page: Page, status: string): Promise<void> {
  const next = page.getByRole('button', { name: `Pasar a ${status}` })
  if (await next.isVisible()) {
    await next.click()
    return
  }
  await page.getByRole('button', { name: 'Más acciones' }).click()
  await page.getByRole('menuitem', { name: `Cambiar a ${status}` }).click()
}

// Elige el estado y confirma el diálogo, con la nota si se da
async function moveTo(page: Page, status: string, note?: string): Promise<void> {
  await pickStatus(page, status)
  const dialog = page.getByRole('dialog', { name: `¿Pasar a "${status}"?` })
  await expect(dialog).toBeVisible()
  if (note) await dialog.getByRole('textbox').fill(note)
  await dialog.getByRole('button', { name: `Pasar a ${status}` }).click()
  await expect(dialog).toBeHidden()
}

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

  await moveTo(page, 'En progreso')
  await expect(page.getByText('En progreso', { exact: true }).first()).toBeVisible()
  await expect(page.getByText('cambió el estado')).toBeVisible()

  // Resuelto exige contar cómo se resolvió; sin la nota, el diálogo no deja seguir
  // Desde En progreso, el siguiente paso propuesto es Resuelto
  await page.getByRole('button', { name: 'Pasar a Resuelto' }).click()
  const dialog = page.getByRole('dialog', { name: '¿Pasar a "Resuelto"?' })
  await dialog.getByRole('button', { name: 'Pasar a Resuelto' }).click()
  await expect(dialog.getByText('obligatorio')).toBeVisible()
  await expectAccessible(page)
  await dialog.getByRole('textbox').fill('Se cambió el cable de red del puesto.')
  await dialog.getByRole('button', { name: 'Pasar a Resuelto' }).click()
  await expect(page.getByText('Se cambió el cable de red del puesto.')).toBeVisible()

  // Cerrado no pide nota; después solo queda reabrirlo, con su motivo
  await moveTo(page, 'Cerrado')
  await page.getByRole('button', { name: 'Reabrir' }).click()
  const reopen = page.getByRole('dialog', { name: 'Reabrir el incidente' })
  await reopen.getByRole('textbox').fill('El cable nuevo también falla.')
  await reopen.getByRole('button', { name: 'Reabrir' }).click()
  // Sin nadie asignado vuelve al estado inicial
  await expect(page.getByText('Cerrado → Abierto')).toBeVisible()

  await page.goto(`/incidentes?search=${encodeURIComponent(title)}`)
  await expect(page.getByText(title)).toBeVisible()
  await expectAccessible(page)
})

test('el agente se asigna el incidente al crearlo', async ({ page }, info) => {
  const title = `Asignármelo ${info.project.name} ${Date.now()}`
  await signInAs(page, 'agente2@trazo.test')

  await page.goto('/incidentes/nuevo')
  await expect(page.getByRole('checkbox', { name: 'Asignármelo' })).toBeVisible()
  await expect(page.getByLabel('Asignar a')).toHaveCount(0)
  await expectAccessible(page)

  await page.getByLabel('Título').fill(title)
  await page.getByLabel('Descripción').fill('El agente lo registra y lo atiende de una vez.')
  await page.getByRole('checkbox', { name: 'Asignármelo' }).click()
  await page.getByRole('button', { name: 'Crear incidente' }).click()

  await expect(page.getByRole('heading', { level: 1, name: title })).toBeVisible()
  await expect(page.getByText('creó el incidente y se lo asignó')).toBeVisible()
  // Ya es suyo: puede cambiar el estado sin pasar por "Tomar"
  await expect(page.getByRole('button', { name: 'Tomar' })).toHaveCount(0)
})

test('el agente pide reabrir y quien supervisa lo aprueba', async ({ page, browser }, info) => {
  test.skip(info.project.name !== 'portatil', 'Basta una vista')
  const SUPERVISOR = 'supervisor@trazo.test'
  const AGENT = 'agente2@trazo.test'
  const title = `Reapertura ${Date.now()}`

  // Preparación por la API: un incidente atendido por el agente y ya cerrado
  await signInAs(page, SUPERVISOR)
  const headers = authHeaderOf(SUPERVISOR)
  const agentId = (
    (await (await page.request.get('/api/v1/incidents/assignees', { headers })).json()) as {
      data: { id: string; name: string }[]
    }
  ).data.find((person) => person.name === 'Andrés Castro')?.id
  const created = await page.request.post('/api/v1/incidents', {
    headers: { ...headers, 'Idempotency-Key': crypto.randomUUID() },
    data: { title, description: 'Para pedir que se reabra.', priority: 'low', assignee: agentId },
  })
  let incident = ((await created.json()) as { data: { id: string; version: number } }).data
  for (const [status, note] of [
    ['in_progress', null],
    ['resolved', 'Listo'],
    ['closed', null],
  ] as const) {
    const moved = await page.request.put(`/api/v1/incidents/${incident.id}/status`, {
      headers,
      data: { status, note, version: incident.version },
    })
    incident = ((await moved.json()) as { data: typeof incident }).data
  }

  const agent = await (await browser.newContext()).newPage()
  try {
    await signInAs(agent, AGENT)
    await agent.goto(`/incidentes/${incident.id}`)
    await agent.getByRole('button', { name: 'Solicitar reapertura' }).click()
    const request = agent.getByRole('dialog', { name: 'Solicitar reapertura' })
    await request.getByRole('textbox').fill('El problema volvió esta mañana.')
    await request.getByRole('button', { name: 'Enviar solicitud' }).click()
    await expect(agent.getByText('Está esperando la respuesta de quien supervisa.')).toBeVisible()
    await expectAccessible(agent)
  } finally {
    await agent.context().close()
  }

  // El filtro "Con reapertura pendiente" lo muestra; luego se decide en el detalle
  await page.goto(`/incidentes?reopen=1&search=${encodeURIComponent(title)}`)
  await expect(page.getByText(title)).toBeVisible()
  await expect(page.getByText('Reapertura pendiente', { exact: true })).toBeVisible()
  await page.goto(`/incidentes/${incident.id}`)
  await page.getByRole('button', { name: 'Aprobar y reabrir' }).click()
  await page.getByRole('dialog').getByRole('button', { name: 'Aprobar y reabrir' }).click()
  await expect(page.getByText('reabrió el incidente')).toBeVisible()
  await expect(page.getByText('El problema volvió esta mañana.').first()).toBeVisible()
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

test('el formulario guía: parecidos, plazo de cada prioridad y atajos de vencimiento', async ({ page }) => {
  await signInAs(page, 'supervisor@trazo.test')
  await page.goto('/incidentes/nuevo')

  // Los datos de demostración traen incidentes de impresoras
  await page.getByLabel('Título').fill('Impresora no imprime')
  await expect(page.getByRole('heading', { name: /^¿Ya lo reportaron\?/ })).toBeVisible()

  await page.getByRole('radio', { name: /^Crítica/ }).check()
  await expect(page.getByText(/· en 4 h$/)).toBeVisible()
  await page.getByRole('radiogroup', { name: 'Cuándo vence' }).getByRole('radio', { name: 'Mañana' }).click()
  await expect(page.getByText(/· en 4 h$/)).toHaveCount(0)
  await expectAccessible(page)
})

test('la lista agrupa por vencimiento, cada grupo se pliega y se puede pasar a la tabla', async ({ page }) => {
  await signInAs(page, 'supervisor@trazo.test')
  await page.goto('/incidentes')

  // Los datos de demostración siempre traen vencidos
  const overdue = page.getByRole('button', { name: /^Vencidos \d/ })
  await expect(overdue).toHaveAttribute('aria-expanded', 'true')
  await expect(page.getByText(/^Venció hace/).first()).toBeVisible()
  await expectAccessible(page)

  await overdue.click()
  await expect(overdue).toHaveAttribute('aria-expanded', 'false')

  await page.goto('/incidentes?agrupar=prioridad')
  await expect(page.getByRole('button', { name: /^Crítica \d/ })).toBeVisible()

  await page.getByRole('radio', { name: 'Tabla' }).click()
  await expect(page).toHaveURL(/vista=tabla/)
  await expect(page.getByRole('button', { name: /^Crítica \d/ })).toHaveCount(0)
  await expect(page.getByLabel('Agrupar por')).toHaveCount(0)
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
