// Avisos de tiempo real y a qué canales se suscribe cada persona. Los canales repiten la regla de la API solo para no
// pedir suscripciones que serían rechazadas; quien decide es el back. Documentado en docs/frontend/realtime.md

export type IncidentChange = 'created' | 'updated' | 'deleted' | 'restored'

export interface RealtimeEvents {
  // Un incidente cambió: solo su id; la pantalla vuelve a consultar la API
  incident: { id: string; change: IncidentChange }
  // Te asignaron un incidente
  assigned: { id: string }
  // Tu sesión cambió (rol, permisos, estado o sesiones cerradas)
  session: Record<string, never>
  // La conexión volvió después de cortarse: pudo perderse algún aviso
  reconnected: Record<string, never>
}

export const INCIDENTS_ALL = 'incidents.all'
export const INCIDENTS_UNASSIGNED = 'incidents.unassigned'

export function channelsFor(userId: string, can: (permission: string) => boolean): string[] {
  return [
    `users.${userId}`,
    ...(can('incidents.view.any') ? [INCIDENTS_ALL] : []),
    ...(can('incidents.view.unassigned') ? [INCIDENTS_UNASSIGNED] : []),
  ]
}
