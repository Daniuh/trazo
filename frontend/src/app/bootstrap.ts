// Arranque de la aplicación: Quasar, estado global (Pinia) y router.
// Documentado en docs/frontend/index.md
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import { installQuasar } from './plugins/quasar'
import { router } from './router'

export function bootstrap(selector: string): void {
  const app = createApp(App)

  installQuasar(app)
  app.use(createPinia())
  app.use(router)

  app.mount(selector)
}
