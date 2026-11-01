// Dock del celular: si las secciones no caben, las primeras y "Más" con el resto; en los más angostos, un lugar menos.
import { describe, expect, it } from 'vitest'
import { dockSlots, inSection, splitDock } from './dock'

const all = ['Inicio', 'Incidentes', 'Auditoría', 'Usuarios', 'Roles', 'Sistema']

describe('dock del celular', () => {
  it('muestra todas las secciones si caben', () => {
    expect(splitDock(all.slice(0, 4), 4)).toEqual({ shown: all.slice(0, 4), more: [] })
  })

  it('deja un lugar para "Más" cuando sobran', () => {
    expect(splitDock(all, 4)).toEqual({ shown: all.slice(0, 3), more: ['Usuarios', 'Roles', 'Sistema'] })
  })

  it('en los celulares angostos hay menos lugares', () => {
    expect(dockSlots(430)).toBe(4)
    expect(dockSlots(390)).toBe(3)
    expect(dockSlots(320)).toBe(2)
    expect(splitDock(all, dockSlots(390)).shown).toEqual(['Inicio', 'Incidentes'])
    expect(splitDock(all, dockSlots(320)).shown).toEqual(['Inicio'])
  })
})

describe('sección actual', () => {
  it('se mantiene en las páginas que cuelgan de ella', () => {
    expect(inSection('/incidentes/mios', '/incidentes')).toBe(true)
    expect(inSection('/incidentes', '/incidentes')).toBe(true)
    expect(inSection('/incidentes-viejos', '/incidentes')).toBe(false)
  })

  it('el inicio solo se marca en su propia página', () => {
    expect(inSection('/', '/')).toBe(true)
    expect(inSection('/incidentes', '/')).toBe(false)
  })
})
