// Vista de "Mis incidentes" (Kanban, Lista, Calendario o Mi día): va en la URL (?vista=) para compartirla y se
// recuerda en el navegador (localStorage, nada sensible) para la próxima vez. Documentado en docs/frontend/incidents.md
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { DEFAULT_VIEW, parseView, type BoardView } from '../domain/board'

const KEY = 'trazo.my-incidents.view'

function stored(): BoardView | null {
  try {
    return parseView(window.localStorage.getItem(KEY))
  } catch {
    return null
  }
}

export function useBoardView() {
  const route = useRoute()
  const router = useRouter()

  return computed<BoardView>({
    get: () => parseView(route.query.vista) ?? stored() ?? DEFAULT_VIEW,
    set: (view) => {
      try {
        window.localStorage.setItem(KEY, view)
      } catch {
        // Navegación privada o almacenamiento bloqueado: la vista dura solo esta visita
      }
      void router.replace({ query: { ...route.query, vista: view } })
    },
  })
}
