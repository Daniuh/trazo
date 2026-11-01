// Pruebas de los componentes base: accesibilidad, estados y reglas de diseño.
import { mount } from '@vue/test-utils'
import { Quasar } from 'quasar'
import { describe, expect, it } from 'vitest'
import { ApiError, ClientErrorCode } from '@/shared/http'
import AppBadge from './AppBadge.vue'
import AppButton from './AppButton.vue'
import AppCodeField from './form/AppCodeField.vue'
import AppIcon from './AppIcon.vue'
import AppInfoTip from './AppInfoTip.vue'
import AppDataTable from './AppDataTable.vue'
import AppQrCode from './AppQrCode.vue'
import AppRadioList from './form/AppRadioList.vue'
import AppErrorState from './states/AppErrorState.vue'
import { rowsThatFit } from './useFitCount'

describe('AppIcon', () => {
  it('dibuja las figuras del ícono sin atributos sobrantes', () => {
    const svg = mount(AppIcon, { props: { name: 'info' } })

    expect(svg.findAll('circle')).toHaveLength(2)
    expect(svg.html()).not.toContain('tag=')
  })

  it('es decorativo por defecto y accesible cuando tiene etiqueta', () => {
    expect(mount(AppIcon, { props: { name: 'home' } }).attributes('aria-hidden')).toBe('true')

    const labeled = mount(AppIcon, { props: { name: 'home', label: 'Inicio' } })
    expect(labeled.attributes('role')).toBe('img')
    expect(labeled.attributes('aria-label')).toBe('Inicio')
  })
})

describe('AppButton', () => {
  it('se bloquea y lo anuncia mientras carga', async () => {
    const button = mount(AppButton, { props: { loading: true }, slots: { default: 'Guardar' } })

    expect(button.attributes('disabled')).toBeDefined()
    expect(button.attributes('aria-busy')).toBe('true')
  })

  it('aplica la variante pedida', () => {
    const button = mount(AppButton, { props: { variant: 'danger' }, slots: { default: 'Eliminar' } })

    expect(button.classes()).toContain('app-button--danger')
  })
})

describe('AppInfoTip', () => {
  it('guarda la ayuda oculta y la asocia al ícono para lectores de pantalla', () => {
    const tip = mount(AppInfoTip, { props: { text: 'Se calcula según la prioridad.' } })
    const trigger = tip.get('button')

    expect(tip.get('[role="tooltip"]').text()).toBe('Se calcula según la prioridad.')
    expect(trigger.attributes('aria-describedby')).toBe(tip.get('[role="tooltip"]').attributes('id'))
    expect(trigger.attributes('aria-expanded')).toBe('false')
  })

  it('se abre al tocarlo y se cierra con Escape', async () => {
    const tip = mount(AppInfoTip, { props: { text: 'Ayuda' }, attachTo: document.body })

    await tip.get('button').trigger('click')
    expect(tip.classes()).toContain('info-tip--open')

    await tip.trigger('keydown', { key: 'Escape' })
    expect(tip.classes()).not.toContain('info-tip--open')
    tip.unmount()
  })

  it('nunca deja la burbuja fuera de la pantalla', async () => {
    const tip = mount(AppInfoTip, { props: { text: 'Ayuda' }, attachTo: document.body })

    await tip.get('button').trigger('click')

    expect(tip.get('[role="tooltip"]').attributes('style')).toContain('left: 8px')
    tip.unmount()
  })
})

describe('AppBadge', () => {
  it('usa el tono indicado', () => {
    expect(mount(AppBadge, { props: { tone: 'orange' } }).classes()).toContain('app-badge--orange')
  })
})

describe('AppErrorState', () => {
  it('muestra el mensaje claro del catálogo y permite reintentar', async () => {
    const state = mount(AppErrorState, { props: { error: new ApiError('network', ClientErrorCode.Network) } })

    expect(state.text()).toContain('No pudimos conectarnos')
    await state.get('button').trigger('click')
    expect(state.emitted('retry')).toHaveLength(1)
  })
})

describe('AppCodeField', () => {
  it('deja solo dígitos y corta en el largo del código, para poder pegar "123 456"', async () => {
    const field = mount(AppCodeField, {
      props: { label: 'Código', modelValue: '' },
      global: { plugins: [Quasar] },
    })

    await field.find('input').setValue('123 45-678')

    expect(field.emitted('update:modelValue')?.at(-1)).toEqual(['123456'])
    expect(field.find('input').attributes('inputmode')).toBe('numeric')
    expect(field.find('input').attributes('autocomplete')).toBe('one-time-code')
  })
})

describe('AppQrCode', () => {
  it('dibuja el código como imagen con nombre y deja el margen que exige la norma', () => {
    const qr = mount(AppQrCode, {
      props: { value: 'otpauth://totp/Trazo:a@trazo.test?secret=ABC', label: 'Código QR' },
    })
    const [, , width] = (qr.attributes('viewBox') ?? '').split(' ').map(Number)

    expect(qr.attributes('role')).toBe('img')
    expect(qr.attributes('aria-label')).toBe('Código QR')
    // Un QR tiene 21 módulos como mínimo, más 4 de margen por lado
    expect(width).toBeGreaterThanOrEqual(29)
    // El patrón de esquina siempre empieza oscuro, justo después del margen
    expect(qr.find('path').attributes('d')).toMatch(/^M4 4h1v1h-1z/)
  })
})

describe('AppDataTable', () => {
  const rows = [
    { id: 'a', name: 'Ana' },
    { id: 'b', name: 'Beto' },
  ]
  const columns = [
    { key: 'name', label: 'Nombre', sortable: true },
    { key: 'actions', label: 'Acciones', hiddenLabel: true },
  ]

  it('ordena desde el encabezado: primero de mayor a menor y luego al revés', async () => {
    const table = mount(AppDataTable, {
      props: { columns, rows, rowKey: (row: unknown) => (row as { id: string }).id, sort: 'name', descending: true },
    })

    expect(table.get('th').attributes('aria-sort')).toBe('descending')
    await table.get('th button').trigger('click')
    expect(table.emitted('sort')?.[0]).toEqual(['name', false])
  })

  it('abre una fila con clic o con Enter, y la columna sin nombre visible lo conserva para lectores', async () => {
    const table = mount(AppDataTable, {
      props: { columns, rows, rowKey: (row: unknown) => (row as { id: string }).id },
    })

    await table.findAll('tbody tr')[1]?.trigger('keydown.enter')
    expect(table.emitted('open')?.[0]).toEqual(['b'])
    expect(table.findAll('th')[1]?.get('span').classes()).toContain('app-data-table__hidden')
  })
})

describe('AppRadioList', () => {
  it('agrupa las opciones con su nombre y descripción, y muestra el error asociado', () => {
    const list = mount(AppRadioList, {
      global: { plugins: [Quasar] },
      props: {
        label: 'Rol',
        modelValue: 'agent',
        error: 'Este campo es obligatorio.',
        options: [
          { value: 'agent', label: 'Agente', description: 'Atiende incidentes.' },
          { value: 'requester', label: 'Solicitante' },
        ],
      },
    })

    expect(list.get('legend').text()).toBe('Rol')
    expect(list.text()).toContain('Atiende incidentes.')
    expect(list.findAll('.app-radio-list__option--selected')).toHaveLength(1)
    expect(list.get('fieldset').attributes('aria-describedby')).toBe(list.get('[role="alert"]').attributes('id'))
  })
})

describe('rowsThatFit', () => {
  it('cuenta solo las filas completas que caben, con su separación', () => {
    // Filas de 24 px con 8 px entre ellas: en 100 px caben 3 (24 + 8 + 24 + 8 + 24 = 88)
    expect(rowsThatFit(100, 24, 8)).toBe(3)
    expect(rowsThatFit(88, 24, 8)).toBe(3)
    expect(rowsThatFit(87, 24, 8)).toBe(2)
    expect(rowsThatFit(10, 24, 8)).toBe(0)
    expect(rowsThatFit(100, 0, 8)).toBe(0)
  })
})
