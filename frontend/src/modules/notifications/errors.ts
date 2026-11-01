// Mensajes de los errores del centro de notificaciones. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

const messages: MessageCatalog = {
  NOTIFICATION_NOT_FOUND: 'Esa notificación ya no existe: se borró por antigüedad. Actualiza la lista.',
}

export default messages
