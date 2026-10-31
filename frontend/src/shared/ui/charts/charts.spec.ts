// Pruebas de los gráficos propios: proporciones, enlaces, porcentajes y datos accesibles sin depender del color.
// Documentado en docs/frontend/sistema-de-diseno/graficos.md
import { mount, RouterLinkStub } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppBarList from './AppBarList.vue'
import AppColumnChart from './AppColumnChart.vue'
import AppDonutChart from './AppDonutChart.vue'
import type { ChartItem } from './types'

const items: ChartItem[] = [
  { key: 'open', label: 'Abierto', value: 1500, tone: 'blue', to: { name: 'incidents' } },
  { key: 'closed', label: 'Cerrado', value: 500, tone: 'dark', detail: '2 vencidos' },
]
const stubs = { RouterLink: RouterLinkStub }

describe('AppBarList', () => {
  it('dibuja cada barra en proporción al mayor y escribe el valor con separador de miles', () => {
    const bars = mount(AppBarList, { props: { items, label: 'Por estado' }, global: { stubs } })

    const fills = bars.findAll('.app-bars__fill')
    expect(fills[0]?.attributes('style')).toContain('width: 100%')
    expect(fills[1]?.attributes('style')).toContain('width: 33%')
    expect(bars.text()).toContain('1.500')
    expect(bars.text()).toContain('2 vencidos')
  })

  it('solo las filas con destino son enlaces', () => {
    const bars = mount(AppBarList, { props: { items, label: 'Por estado' }, global: { stubs } })

    expect(bars.findAllComponents(RouterLinkStub)).toHaveLength(1)
  })
})

describe('AppDonutChart', () => {
  it('reparte la circunferencia según cada valor y muestra el total', () => {
    const donut = mount(AppDonutChart, {
      props: { items, label: 'Por prioridad', totalLabel: 'incidentes' },
      global: { stubs },
    })

    const segments = donut.findAll('.app-donut__segment')
    expect(segments[0]?.attributes('stroke-dasharray')).toBe('75 25')
    expect(segments[1]?.attributes('stroke-dasharray')).toBe('25 75')
    expect(donut.find('.app-donut__total').text()).toBe('2.000')
  })

  it('no dibuja porciones vacías pero las deja en la leyenda', () => {
    const donut = mount(AppDonutChart, {
      props: {
        items: [...items, { key: 'x', label: 'Cancelado', value: 0, tone: 'muted' }],
        label: 'P',
        totalLabel: 'i',
      },
      global: { stubs },
    })

    expect(donut.findAll('.app-donut__segment')).toHaveLength(2)
    expect(donut.text()).toContain('Cancelado')
  })
})

describe('AppColumnChart', () => {
  it('incluye una tabla con todos los datos para lectores de pantalla', () => {
    const chart = mount(AppColumnChart, {
      props: {
        label: 'Creados y resueltos',
        series: [
          { key: 'created', label: 'Creados', tone: 'blue' },
          { key: 'resolved', label: 'Resueltos', tone: 'green' },
        ],
        points: [
          { key: 'a', label: '21 oct', values: { created: 4, resolved: 1 } },
          { key: 'b', label: '22 oct', values: { created: 0, resolved: 3 } },
        ],
      },
    })

    expect(chart.findAll('tbody tr')).toHaveLength(2)
    expect(chart.find('tbody tr').text()).toContain('21 oct')
    expect(chart.findAll('.app-columns__bar')).toHaveLength(4)
    expect(chart.find('title').text()).toBe('21 oct: 4 creados, 1 resueltos')
  })
})
