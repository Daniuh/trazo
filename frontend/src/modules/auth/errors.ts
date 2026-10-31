// Mensajes de los errores de sesión. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

const messages: MessageCatalog = {
  // No dice cuál de los dos falló, para no revelar qué correos existen
  INVALID_CREDENTIALS: 'El correo o la contraseña no son correctos. Revísalos e inténtalo de nuevo.',
  ACCOUNT_LOCKED:
    'Por seguridad, pausamos el ingreso con este correo después de varios intentos fallidos. Vuelve a intentarlo en {wait}.',
}

export default messages
