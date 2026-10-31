// Pruebas de la descarga de archivos: el nombre sale de lo que propone el servidor.
// Documentado en docs/frontend/http-y-errores.md
import { describe, expect, it } from 'vitest'
import { filenameFrom } from './saveFile'

describe('nombre del archivo', () => {
  it('usa el que propone el servidor, también codificado', () => {
    expect(filenameFrom('attachment; filename=incidentes-2026-10-25-1430.csv', 'x.csv')).toBe(
      'incidentes-2026-10-25-1430.csv',
    )
    expect(filenameFrom('attachment; filename="a b.csv"', 'x.csv')).toBe('a b.csv')
    expect(filenameFrom("attachment; filename*=UTF-8''informe%20%C3%BAnico.csv", 'x.csv')).toBe('informe único.csv')
  })

  it('usa el de respaldo si no viene ninguno', () => {
    expect(filenameFrom(undefined, 'incidentes.csv')).toBe('incidentes.csv')
  })
})
