// Configuración visual: estilos de Quasar, estilos globales de Trazo y plugins usados.
// Los íconos internos de Quasar usan su juego en SVG, empaquetado con la app (sin fuentes externas).
import 'quasar/src/css/index.sass'
import '@/design/theme/global.scss'
import { Dialog, Notify, Quasar } from 'quasar'
import iconSet from 'quasar/icon-set/svg-material-icons'
import langEs from 'quasar/lang/es'
import type { App } from 'vue'

export function installQuasar(app: App): void {
  app.use(Quasar, {
    lang: langEs,
    iconSet,
    plugins: { Dialog, Notify },
  })
}
