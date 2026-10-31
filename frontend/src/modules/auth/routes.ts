// Rutas del módulo de sesión. El login, el segundo paso del MFA, su configuración y el aviso de sin conexión van fuera
// del layout porque se ven sin sesión (o con un ingreso a medias). Mi cuenta va dentro. Documentado en docs/frontend/auth.md
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
    path: '/ingresar/verificar',
    name: 'mfa-verify',
    component: () => import('./presentation/mfa/MfaVerifyPage.vue'),
    meta: { public: true },
  },
  {
    path: '/configurar-mfa',
    name: 'mfa-setup',
    component: () => import('./presentation/mfa/MfaSetupPage.vue'),
    meta: { public: true },
  },
  {
    path: '/sin-conexion',
    name: CONNECTION_ROUTE,
    component: () => import('./presentation/ConnectionErrorPage.vue'),
    meta: { public: true },
  },
]

const routes: RouteRecordRaw[] = [
  { path: 'mi-cuenta', name: 'account', component: () => import('./presentation/account/AccountPage.vue') },
]

export default routes
