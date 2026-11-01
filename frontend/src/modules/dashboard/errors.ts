// Mensajes de los errores del dashboard. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

const messages: MessageCatalog = {
  WIDGET_NOT_AVAILABLE:
    'Uno de los widgets ya no está disponible para tu cuenta. Recarga la página para ver tu dashboard actual.',
  WIDGET_DUPLICATED: 'Un widget quedó repetido en el diseño. Recarga la página y vuelve a acomodarlo.',
  WIDGET_OUT_OF_GRID:
    'Un widget quedó fuera del espacio del dashboard. Muévelo dentro de la grilla e inténtalo de nuevo.',
  WIDGET_SIZE_OUT_OF_RANGE:
    'Un widget quedó más grande o más pequeño de lo permitido. Ajusta su tamaño e inténtalo de nuevo.',
  WIDGETS_OVERLAP: 'Dos widgets quedaron uno encima del otro. Sepáralos e inténtalo de nuevo.',
}

export default messages
