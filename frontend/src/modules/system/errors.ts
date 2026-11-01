// Mensajes de los errores de la pantalla de sistema. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

const messages: MessageCatalog = {
  MAINTENANCE_ALREADY_ENABLED: 'El modo mantenimiento ya estaba activo. Actualiza el panel para ver su estado.',
  MAINTENANCE_ALREADY_DISABLED: 'El modo mantenimiento ya estaba apagado. Actualiza el panel para ver su estado.',
  NO_FAILED_JOBS: 'No hay jobs fallidos para reintentar.',
  FAILED_JOB_NOT_FOUND: 'Ese job ya no está en la lista: otra persona lo reintentó o lo descartó. Actualiza la lista.',
  SYSTEM_EVENT_NOT_FOUND: 'Ese evento ya no existe: se borró por antigüedad. Actualiza la lista.',
}

export default messages
