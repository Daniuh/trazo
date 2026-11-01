// "‹ Incidentes" vuelve al listado como se dejó, o al listado sin filtros si nunca se abrió.
import { describe, expect, it } from 'vitest'
import { listReturnLocation, rememberListLocation } from './listReturn'

describe('listReturn', () => {
  it('sin listado visitado vuelve al listado sin filtros', () => {
    expect(listReturnLocation()).toEqual({ name: 'incidents' })
  })

  it('vuelve al último listado con sus filtros, orden y página', () => {
    rememberListLocation('/incidentes?status=open&page=2')
    rememberListLocation('/incidentes?status=open,in_progress&sort=-due_date')

    expect(listReturnLocation()).toBe('/incidentes?status=open,in_progress&sort=-due_date')
  })
})
