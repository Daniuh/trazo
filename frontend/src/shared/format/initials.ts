// Iniciales de un nombre para el círculo de la persona: primera letra del primer y del último nombre ("Carlos
// Mejía" → "CM"); con un solo nombre, su primera letra. Documentado en docs/frontend/sistema-de-diseno/layout.md

export function initials(name: string): string {
  const words = name.trim().split(/\s+/).filter(Boolean)
  const first = words[0]?.[0] ?? ''
  const last = words.length > 1 ? (words[words.length - 1]?.[0] ?? '') : ''
  return (first + last).toLocaleUpperCase('es-CO')
}
