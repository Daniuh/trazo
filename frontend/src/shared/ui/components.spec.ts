// Pruebas de los componentes base: accesibilidad, estados y reglas de diseño.
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { ApiError, ClientErrorCode } from '@/shared/http'
import AppBadge from './AppBadge.vue'
import AppButton from './AppButton.vue'
import AppIcon from './AppIcon.vue'
import AppInfoTip from './AppInfoTip.vue'
import AppErrorState from './states/AppErrorState.vue'

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
