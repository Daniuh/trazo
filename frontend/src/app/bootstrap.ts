// Arranque de la aplicación: Quasar, estado global (Pinia), mensajes de error de cada módulo y router.
// Documentado en docs/frontend/index.md
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import { registerErrorMessages, type MessageCatalog } from '@/shared/errors'
import App from './App.vue'
import { installQuasar } from './plugins/quasar'
import { router } from './router'

// Cada módulo puede traer sus mensajes de error en modules/<módulo>/errors.ts
const moduleMessages = import.meta.glob<{ default: MessageCatalog }>('../modules/*/errors.ts', { eager: true })

export function bootstrap(selector: string): void {
  Object.values(moduleMessages).forEach((module) => registerErrorMessages(module.default))

  const app = createApp(App)

  installQuasar(app)
  // Pinia va antes del router: la guarda de rutas usa el estado de la sesión
  app.use(createPinia())
  app.use(router)

  app.mount(selector)
}
