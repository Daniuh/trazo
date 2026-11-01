// Números para las personas, con el separador de miles de Colombia ("1.234").
// Documentado en docs/frontend/dashboard.md

const integer = new Intl.NumberFormat('es-CO')

export function formatNumber(value: number): string {
  return integer.format(value)
}
