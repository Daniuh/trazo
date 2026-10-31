// Rutas del dashboard: es la página de inicio, dentro del layout y con carga diferida.
// Documentado en docs/frontend/dashboard.md
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  { path: '', name: 'home', component: () => import('./presentation/DashboardPage.vue') },
]

export default routes
