// Router de la SPA. Cada módulo aporta sus rutas en modules/<módulo>/routes.ts y se registran solas:
// las de "default" van dentro del layout; las de "publicRoutes" (p. ej. el login) van fuera.
// Documentado en docs/frontend/index.md
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { installAuthGuard } from '@/modules/auth/application/authGuard'

declare module 'vue-router' {
  interface RouteMeta {
    // Se puede ver sin sesión
    public?: boolean
  }
}

type ModuleRoutes = { default?: RouteRecordRaw[]; publicRoutes?: RouteRecordRaw[] }

const modules = Object.values(import.meta.glob<ModuleRoutes>('../../modules/*/routes.ts', { eager: true }))

const shellRoutes: RouteRecordRaw[] = [
  ...modules.flatMap((module) => module.default ?? []),
  ...(import.meta.env.DEV
    ? [{ path: 'guia-visual', name: 'design-guide', component: () => import('../pages/DesignGuidePage.vue') }]
    : []),
  { path: ':pathMatch(.*)*', name: 'not-found', component: () => import('../pages/NotFoundPage.vue') },
]

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...modules.flatMap((module) => module.publicRoutes ?? []),
    { path: '/', component: () => import('../layout/AppShell.vue'), children: shellRoutes },
  ],
})

installAuthGuard(router)
