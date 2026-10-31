// Router de la SPA. Cada módulo aporta sus rutas en modules/<módulo>/routes.ts y se registran solas.
// Documentado en docs/frontend/index.md
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

type ModuleRoutes = { default: RouteRecordRaw[] }

const moduleRoutes = Object.values(
  import.meta.glob<ModuleRoutes>('../../modules/*/routes.ts', { eager: true }),
).flatMap((module) => module.default)

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...moduleRoutes,
    // Provisional hasta que exista el dashboard
    { path: '/', name: 'home', component: () => import('../pages/HomePage.vue') },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../pages/NotFoundPage.vue') },
  ],
})
