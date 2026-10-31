// Mensajes para los errores generales de la API y del cliente. Lenguaje sencillo y con un siguiente paso.
// Los mensajes de negocio los registra cada módulo. Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from './catalog'

export const generalMessages: MessageCatalog = {
  MALFORMED_REQUEST: 'No pudimos procesar la solicitud. Recarga la página e inténtalo de nuevo.',
  UNAUTHENTICATED: 'Tu sesión terminó. Inicia sesión de nuevo para continuar.',
  FORBIDDEN_ACTION:
    'No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador.',
  RESOURCE_NOT_FOUND: 'No encontramos lo que buscas. Puede que se haya eliminado o que no tengas acceso.',
  METHOD_NOT_ALLOWED: 'Esta acción no está disponible. Recarga la página e inténtalo de nuevo.',
  VALIDATION_FAILED: 'Hay datos por corregir. Revisa los campos marcados.',
  TOO_MANY_REQUESTS: 'Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo.',
  SERVICE_UNAVAILABLE: 'Trazo está en mantenimiento. Vuelve a intentarlo en unos minutos.',
  INTERNAL_ERROR:
    'No pudimos completar la acción por un problema de nuestro lado. Inténtalo de nuevo; si se repite, comparte este código con soporte: {traceId}.',
  NETWORK_ERROR: 'No pudimos conectarnos. Revisa tu conexión a internet y vuelve a intentarlo.',
  TIMEOUT: 'El servidor tardó demasiado en responder. Vuelve a intentarlo en unos segundos.',
  SERVER_UNREACHABLE:
    'El servidor de Trazo no está respondiendo en este momento. Vuelve a intentarlo en unos minutos; si sigue igual, avisa a soporte.',
  REQUEST_CANCELED: 'La acción se canceló antes de terminar.',
  UNKNOWN_ERROR: 'No pudimos completar la acción. Si se repite, comparte este código con soporte: {traceId}.',
}
