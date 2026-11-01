// Pruebas del error normalizado: una pantalla que no se pudo descargar se reconoce en cualquier navegador.
import { describe, expect, it } from 'vitest'
import { ClientErrorCode, isScreenLoadError, toApiError } from './apiError'

const BROWSER_MESSAGES = [
  'Failed to fetch dynamically imported module: http://localhost/assets/IncidentListPage-a1b2.js',
  'error loading dynamically imported module: http://localhost/assets/IncidentListPage-a1b2.js',
  'Importing a module script failed.',
  'Unable to preload CSS for /assets/IncidentListPage-a1b2.css',
]

describe('pantalla que no se pudo descargar', () => {
  it.each(BROWSER_MESSAGES)('la reconoce: %s', (message) => {
    expect(isScreenLoadError(new TypeError(message))).toBe(true)
    expect(toApiError(new TypeError(message)).code).toBe(ClientErrorCode.ScreenLoadFailed)
  })

  it('no confunde otras fallas con una pantalla sin descargar', () => {
    expect(isScreenLoadError(new TypeError('Cannot read properties of undefined'))).toBe(false)
    expect(isScreenLoadError('Failed to fetch dynamically imported module')).toBe(false)
    expect(toApiError(new Error('otra cosa')).code).toBe(ClientErrorCode.Unknown)
  })
})
