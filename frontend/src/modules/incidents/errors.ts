// Mensajes de los errores de incidentes. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

const messages: MessageCatalog = {
  INCIDENT_FINALIZED: 'Este incidente ya está cerrado o cancelado, así que no se puede modificar.',
  INCIDENT_EDIT_LOCKED:
    'Este incidente ya lo están atendiendo, así que ya no puedes editarlo. Si necesitas cambiar algo, habla con un supervisor.',
  INCIDENT_ALREADY_ASSIGNED:
    'Otra persona tomó este incidente hace un momento. Recarga la página para ver quién lo tiene.',
  INCIDENT_NOT_DELETED: 'Este incidente no está eliminado, así que no hay nada que restaurar.',
  INVALID_STATUS_TRANSITION:
    'Ese cambio de estado no está permitido desde el estado actual. Recarga la página para ver las opciones disponibles.',
  VERSION_CONFLICT:
    'Alguien más guardó cambios en este incidente mientras lo tenías abierto. Recarga para ver la versión actual antes de seguir.',
  INVALID_ASSIGNEE: 'Esta persona no puede recibir incidentes. Elige a alguien de la lista.',
  IDEMPOTENCY_IN_PROGRESS: 'Tu incidente se está creando. Espera unos segundos antes de intentarlo de nuevo.',
  IDEMPOTENCY_KEY_REUSED: 'No pudimos confirmar el envío. Recarga la página e inténtalo de nuevo.',
  INVALID_IDEMPOTENCY_KEY: 'No pudimos procesar el envío. Recarga la página e inténtalo de nuevo.',
}

export default messages
