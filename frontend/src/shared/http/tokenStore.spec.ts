// Pruebas del almacén del token de sesión.
import { describe, expect, it } from 'vitest'
import { createTokenStore } from './tokenStore'

describe('almacén del token', () => {
  it('guarda en sessionStorage y lo recupera al recargar', () => {
    window.sessionStorage.clear()
    createTokenStore(window.sessionStorage).set('abc')

    expect(createTokenStore(window.sessionStorage).get()).toBe('abc')
  })

  it('nunca usa localStorage', () => {
    window.localStorage.clear()
    createTokenStore(window.sessionStorage).set('abc')

    expect(window.localStorage.length).toBe(0)
  })

  it('borra el token al cerrar sesión', () => {
    const store = createTokenStore(window.sessionStorage)
    store.set('abc')

    store.clear()

    expect(store.get()).toBeNull()
    expect(window.sessionStorage.getItem('trazo.session')).toBeNull()
  })

  it('sigue funcionando en memoria si el navegador bloquea el almacenamiento', () => {
    const store = createTokenStore(null)
    store.set('abc')

    expect(store.get()).toBe('abc')
  })
})
