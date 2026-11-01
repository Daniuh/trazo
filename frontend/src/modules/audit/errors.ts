// Mensajes de los errores de la auditoría. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

const messages: MessageCatalog = {
  INVALID_CURSOR: 'No pudimos seguir cargando la lista. Vuelve a cargarla desde el principio.',
  INCIDENT_CODE_FORMAT: 'Escribe el código del incidente, por ejemplo INC-000123 o solo 123.',
}

export default messages
