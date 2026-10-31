// Configuración de Quasar: idioma español y solo los plugins que la app usa.
import 'quasar/src/css/index.sass'
import { Dialog, Notify, Quasar } from 'quasar'
import langEs from 'quasar/lang/es'
import type { App } from 'vue'

export function installQuasar(app: App): void {
  app.use(Quasar, {
    lang: langEs,
    plugins: { Dialog, Notify },
  })
}
