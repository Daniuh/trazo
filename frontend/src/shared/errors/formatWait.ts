// Convierte una espera en segundos (Retry-After) en texto natural: "30 segundos", "5 minutos", "1 hora".
// Documentado en docs/frontend/http-y-errores.md

const MINUTE = 60
const HOUR = 3600

const plural = (value: number, singular: string, many: string) => `${value} ${value === 1 ? singular : many}`

export function formatWait(seconds: number | null): string {
  if (seconds === null || seconds <= 0) return 'unos segundos'
  if (seconds < MINUTE) return plural(seconds, 'segundo', 'segundos')
  if (seconds < HOUR) return plural(Math.ceil(seconds / MINUTE), 'minuto', 'minutos')

  return plural(Math.ceil(seconds / HOUR), 'hora', 'horas')
}
