// Rutas del módulo de sesión. Van fuera del layout porque se ven sin sesión: el login y el aviso de sin conexión.
// Documentado en docs/frontend/auth.md
import type { RouteRecordRaw } from 'vue-router'
import { CONNECTION_ROUTE, LOGIN_ROUTE } from './application/authGuard'

export const publicRoutes: RouteRecordRaw[] = [
  {
    path: '/ingresar',
    name: LOGIN_ROUTE,
    component: () => import('./presentation/LoginPage.vue'),
    meta: { public: true },
  },
  {
    path: '/sin-conexion',
    name: CONNECTION_ROUTE,
    component: () => import('./presentation/ConnectionErrorPage.vue'),
    meta: { public: true },
  },
]
