// Dashboard en las cuatro vistas: cada rol ve su resumen, las cifras llevan al listado filtrado y la página es accesible.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, test } from '@playwright/test'
import { expectAccessible, signInAs } from './support'

// El superadmin parte del diseño original (las pruebas de Personalizar lo restauran); el supervisor puede tener uno guardado
test('el resumen general muestra el diseño centrado en el riesgo, accesible, y cada celda lleva al listado', async ({
  page,
}) => {
  await signInAs(page, 'superadmin@trazo.test')
  await page.goto('/')

  await expect(page.getByRole('heading', { level: 1, name: 'Resumen general' })).toBeVisible()
  for (const name of [
    'Pulso de la operación',
    'Mapa de riesgo',
    'Flujo de estados',
    'Balance del backlog',
    'Carga por agente',
  ]) {
    await expect(page.getByRole('heading', { name })).toBeVisible()
  }
  // Lo que pide la prueba: total, por estado, vencidos y por prioridad
  await expect(page.getByRole('link', { name: /^Total: / })).toBeVisible()
  await expect(page.getByRole('list', { name: 'Etapas del flujo' })).toBeVisible()
  await expect(page.getByRole('link', { name: /^Crítica, total en cualquier estado: / })).toBeVisible()
  await expectAccessible(page)

  await page.getByRole('link', { name: /^Crítica, vencido: .*Ver en el listado$/ }).click()

  await expect(page).toHaveURL(/priority=critical/)
  await expect(page).toHaveURL(/overdue=1/)
  await expect(page.getByRole('button', { name: 'Quitar filtro Vencidos' })).toBeVisible()
})

test('el solicitante ve su resumen sin los widgets de gestión', async ({ page }) => {
  await signInAs(page, 'solicitante1@trazo.test')
  await page.goto('/')

  await expect(page.getByRole('heading', { level: 1, name: 'Mi resumen' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Pulso de la operación' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Mapa de riesgo' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Carga por agente' })).toHaveCount(0)
  await expect(page.getByRole('heading', { name: 'Asignados a mí' })).toHaveCount(0)
})

// agente2 y no agente1: el login de auth.spec usa agente1 por el formulario y ambos comparten el límite de 5 por minuto
test('el balance del backlog cambia de período', async ({ page }) => {
  await signInAs(page, 'agente2@trazo.test')
  await page.goto('/')

  const period = page.getByRole('radiogroup', { name: 'Período' })
  await period.getByRole('radio', { name: '7 días' }).click()

  await expect(period.getByRole('radio', { name: '7 días' })).toHaveAttribute('aria-checked', 'true')
  await expect(page.getByRole('table', { name: /últimos 7 días/ }).locator('tbody tr')).toHaveCount(7)
})

// Personalizar se edita en portátil y monitor; usa una cuenta que las demás pruebas no miran y vuelve al original
test.describe('modo Personalizar', () => {
  const editable = ['portatil', 'monitor']

  test('en portátil y monitor se quita, se agrega, se guarda y se restaura el original', async ({ page }, info) => {
    test.skip(!editable.includes(info.project.name), 'Solo se personaliza con pantalla de computador')
    await signInAs(page, 'superadmin@trazo.test')
    await page.goto('/')

    const restoreOriginal = async () => {
      await page.getByRole('button', { name: 'Personalizar', exact: true }).click()
      await page.getByRole('button', { name: 'Restaurar original' }).click()
      await page.getByRole('alertdialog').getByRole('button', { name: 'Restaurar', exact: true }).click()
      await expect(page.getByRole('heading', { level: 1, name: 'Resumen general' })).toBeVisible()
    }
    const riskMap = page.getByRole('heading', { name: 'Mapa de riesgo' })

    // Parte siempre del diseño original, aunque una ejecución anterior se haya cortado. Se recarga para que el aviso
    // de "restaurado" no quede a medio aparecer cuando axe revisa el contraste
    await restoreOriginal()
    await page.reload()

    await page.getByRole('button', { name: 'Personalizar', exact: true }).click()
    await expect(page.getByRole('heading', { level: 1, name: 'Personalizar inicio' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Guardar' })).toBeDisabled()
    await expectAccessible(page)

    // Con teclado: el menú ⋯ de cada widget hace lo mismo que arrastrar
    await page.getByRole('button', { name: /^Opciones de Mapa de riesgo/ }).click()
    await page.getByRole('menuitem', { name: 'Quitar del inicio' }).click()
    await page.getByRole('button', { name: 'Guardar' }).click()

    await expect(page.getByRole('heading', { level: 1, name: 'Resumen general' })).toBeVisible()
    await expect(riskMap).toHaveCount(0)

    await page.getByRole('button', { name: 'Personalizar', exact: true }).click()
    await page.getByRole('button', { name: 'Agregar widget' }).click()
    await page.getByRole('menuitem', { name: /^Mapa de riesgo/ }).click()
    await page.getByRole('button', { name: 'Guardar' }).click()

    await expect(page.getByRole('heading', { level: 1, name: 'Resumen general' })).toBeVisible()
    await expect(riskMap).toBeVisible()

    await restoreOriginal()
  })

  test('avisa antes de salir con cambios sin guardar', async ({ page }, info) => {
    test.skip(!editable.includes(info.project.name), 'Solo se personaliza con pantalla de computador')
    await signInAs(page, 'superadmin@trazo.test')
    await page.goto('/')

    await page.getByRole('button', { name: 'Personalizar', exact: true }).click()
    await page.getByRole('button', { name: /^Opciones de Mapa de riesgo/ }).click()
    await page.getByRole('menuitem', { name: 'Mover a la derecha' }).click()
    await page.getByRole('link', { name: 'Incidentes', exact: true }).click()

    const dialog = page.getByRole('alertdialog')
    await expect(dialog.getByRole('heading', { name: 'Tienes cambios sin guardar' })).toBeVisible()
    await dialog.getByRole('button', { name: 'Cancelar' }).click()
    await expect(dialog).toHaveCount(0)
    await expect(page).toHaveURL(/\/$/)

    await page.getByRole('button', { name: 'Cancelar' }).click()
    await page.getByRole('alertdialog').getByRole('button', { name: 'Descartar' }).click()
    await expect(page.getByRole('heading', { level: 1, name: 'Resumen general' })).toBeVisible()
  })

  test('en tablet y celular no se edita y se explica dónde hacerlo', async ({ page }, info) => {
    test.skip(editable.includes(info.project.name), 'En computador sí se personaliza')
    await signInAs(page, 'superadmin@trazo.test')
    await page.goto('/')

    await expect(page.getByRole('heading', { level: 1, name: 'Resumen general' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Personalizar', exact: true })).toHaveCount(0)
    await expect(page.getByRole('button', { name: 'Cómo personalizar el inicio' })).toBeVisible()
  })
})
