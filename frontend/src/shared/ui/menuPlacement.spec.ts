// Pruebas de dónde se abre un menú según el espacio libre. Documentado en docs/frontend/sistema-de-diseno/componentes.md
import { describe, expect, it } from 'vitest'
import { placeMenu, type Box, type PlacementInput } from './menuPlacement'

const viewport = { width: 1280, height: 800 }
const at = (top: number, left = 500): Box => ({ top, bottom: top + 40, left, right: left + 200 })
const input = (trigger: Box, height: number, width = 300): PlacementInput => ({
  trigger,
  menu: { width, height },
  viewport,
  align: 'left',
  gap: 6,
  margin: 8,
})

describe('placeMenu', () => {
  it('abre hacia abajo cuando cabe', () => {
    expect(placeMenu(input(at(100), 200))).toEqual({
      anchor: 'bottom left',
      self: 'top left',
      offset: [0, 6],
      scrollBy: 0,
    })
  })

  it('respeta la alineación pedida', () => {
    expect(placeMenu({ ...input(at(100), 200), align: 'right' }).anchor).toBe('bottom right')
  })

  it('abre hacia arriba si abajo no cabe y arriba sí', () => {
    expect(placeMenu(input(at(600), 300))).toMatchObject({ anchor: 'top left', self: 'bottom left', scrollBy: 0 })
  })

  it('abre al lado con más espacio y sube lo justo para quedar entero', () => {
    // Campo a la derecha de la pantalla, a media altura: el menú (450 px) no cabe ni arriba ni abajo
    const placement = placeMenu(input(at(380, 1000), 450, 560))

    expect(placement).toMatchObject({ anchor: 'top left', self: 'top right', scrollBy: 0 })
    // Arriba en 800 - 8 - 450 = 342: sube 38 px desde el borde superior del campo
    expect(placement.offset).toEqual([6, 38])
  })

  it('si tampoco cabe al lado, baja la página lo justo y abre hacia abajo', () => {
    const placement = placeMenu(input(at(380, 20), 450, 1200))

    expect(placement).toMatchObject({ anchor: 'bottom left', self: 'top left' })
    // 420 + 6 + 450 + 8 - 800
    expect(placement.scrollBy).toBe(84)
  })

  it('nunca baja tanto que el botón salga por arriba', () => {
    expect(placeMenu(input(at(50, 20), 900, 1200)).scrollBy).toBe(42)
  })
})
