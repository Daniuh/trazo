// Textos y colores de la pantalla de sistema: nombre y explicación de cada componente, su medición en palabras
// ("Responde en 4,7 ms", "Última señal hace 20 s"), estados, severidades y las claves del contexto de un evento.
// Documentado en docs/frontend/system.md
import type { Tone } from '@/shared/ui/tones'
import { formatNumber } from '@/shared/format/numbers'
import type { ComponentCode, EventStatus, HealthComponent, HealthStatus, Severity } from '../domain/system'

export const COMPONENTS: Record<ComponentCode, { title: string; help: string }> = {
  database: { title: 'Base de datos', help: 'MySQL: guarda incidentes, personas y registros.' },
  redis: { title: 'Redis y caché', help: 'Guarda la caché, la cola de trabajos y los límites de frecuencia.' },
  queue: {
    title: 'Cola de trabajos',
    help: 'Correos y avisos en tiempo real esperan aquí hasta que un worker los procesa.',
  },
  worker: { title: 'Workers', help: 'Procesan la cola. Cada minuto dejan una señal de vida.' },
  scheduler: { title: 'Tareas programadas', help: 'Revisan la salud cada minuto y hacen la limpieza diaria.' },
  realtime: { title: 'Tiempo real', help: 'Reverb: avisa a las pantallas abiertas de los cambios.' },
  storage: { title: 'Almacenamiento', help: 'Espacio libre del disco donde la app guarda archivos y registros.' },
}

export const HEALTH_LABEL: Record<HealthStatus, string> = { ok: 'Bien', warning: 'Atención', failing: 'Falla' }
export const HEALTH_TONE: Record<HealthStatus, Tone> = { ok: 'green', warning: 'orange', failing: 'danger' }

export const SEVERITY_LABEL: Record<Severity, string> = {
  critical: 'Crítica',
  warning: 'Atención',
  info: 'Informativa',
}
export const SEVERITY_TONE: Record<Severity, Tone> = { critical: 'danger', warning: 'orange', info: 'blue' }

export const EVENT_STATUS_LABEL: Record<EventStatus, string> = {
  open: 'Abierto',
  acknowledged: 'Revisado',
  resolved: 'Resuelto',
}
export const EVENT_STATUS_TONE: Record<EventStatus, Tone> = { open: 'blue', acknowledged: 'neutral', resolved: 'green' }

// Botón para pasar a cada estado
export const STATUS_ACTION: Record<EventStatus, string> = {
  open: 'Reabrir',
  acknowledged: 'Marcar como revisado',
  resolved: 'Marcar como resuelto',
}

export function formatSeconds(seconds: number): string {
  if (seconds < 60) return `${seconds} s`
  if (seconds < 3600) return `${Math.floor(seconds / 60)} min`
  return `${Math.floor(seconds / 3600)} h`
}

export function formatBytes(bytes: number): string {
  const gigabytes = bytes / 1024 ** 3
  return gigabytes >= 1
    ? `${formatNumber(Math.round(gigabytes))} GB`
    : `${formatNumber(Math.round(bytes / 1024 ** 2))} MB`
}

const milliseconds = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 })

// La medición de cada componente, en una o dos líneas
export function componentDetails(component: HealthComponent): string[] {
  switch (component.code) {
    case 'database':
    case 'redis':
      return [
        typeof component.latencyMs === 'number'
          ? `Responde en ${milliseconds.format(component.latencyMs)} ms`
          : 'No responde',
      ]
    case 'queue':
      return [
        `Pendientes: ${formatNumber(component.pending ?? 0)}` +
          (typeof component.oldestSeconds === 'number'
            ? ` · el más antiguo espera ${formatSeconds(component.oldestSeconds)}`
            : ''),
        `Fallidos: ${formatNumber(component.failed ?? 0)}`,
      ]
    case 'worker':
    case 'scheduler':
      return [
        typeof component.secondsAgo === 'number'
          ? `Última señal hace ${formatSeconds(component.secondsAgo)}`
          : 'Sin señal todavía (recién arrancado)',
      ]
    case 'realtime':
      return [component.status === 'ok' ? 'Acepta conexiones' : 'No responde']
    case 'storage': {
      const free = component.freeBytes ?? 0
      const total = component.totalBytes ?? 0
      const percent = total > 0 ? Math.round((free / total) * 100) : 0
      return [`Libre: ${formatBytes(free)} de ${formatBytes(total)} (${percent} %)`]
    }
  }
}

// Claves conocidas del contexto de un evento; las demás se muestran tal cual
const CONTEXT_LABELS: Record<string, string> = {
  account: 'Cuenta',
  ip: 'IP',
  failures: 'Intentos fallidos',
  lock_seconds: 'Bloqueo (segundos)',
  user_id: 'Persona (id)',
  with_recovery_code: 'Con código de recuperación',
  service: 'Servicio',
  reason: 'Motivo',
  status: 'Estado HTTP',
  seconds_ago: 'Segundos sin señal',
  pending: 'Pendientes',
  oldest_seconds: 'Espera del más antiguo (segundos)',
  job: 'Job',
  queue: 'Cola',
  exception: 'Excepción',
  file: 'Archivo',
  line: 'Línea',
  method: 'Método',
  path: 'Ruta',
  last_path: 'Última ruta',
}

export function contextRows(context: Record<string, unknown> | null): { label: string; value: string }[] {
  return Object.entries(context ?? {}).map(([key, value]) => ({
    label: CONTEXT_LABELS[key] ?? key,
    value: typeof value === 'boolean' ? (value ? 'Sí' : 'No') : String(value ?? '—'),
  }))
}
