// Rutas del módulo de sesión. El login va fuera del layout porque se ve sin sesión.
// Documentado en docs/frontend/auth.md
import type { RouteRecordRaw } from 'vue-router'
import { LOGIN_ROUTE } from './application/authGuard'

export const publicRoutes: RouteRecordRaw[] = [
  {
    path: '/ingresar',
    name: LOGIN_ROUTE,
    component: () => import('./presentation/LoginPage.vue'),
    meta: { public: true },
  },
]
