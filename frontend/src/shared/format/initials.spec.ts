// Iniciales para el círculo de la persona.
import { describe, expect, it } from 'vitest'
import { initials } from './initials'

describe('initials', () => {
  it('toma la primera letra del primer y del último nombre', () => {
    expect(initials('Carlos Mejía')).toBe('CM')
    expect(initials('  laura   gómez  ríos ')).toBe('LR')
    expect(initials('Ana')).toBe('A')
    expect(initials('')).toBe('')
  })
})
