// Pruebas de extremo a extremo con Playwright: los flujos críticos en las cuatro vistas de Trazo, contra el stack de Docker.
// Documentado en docs/calidad/estrategia-de-pruebas.md
import { defineConfig, devices } from '@playwright/test'

const VIEWPORTS = {
  celular: { width: 375, height: 812 },
  tablet: { width: 768, height: 1024 },
  portatil: { width: 1280, height: 800 },
  monitor: { width: 1680, height: 1000 },
} as const

export default defineConfig({
  testDir: './e2e',
  globalSetup: './e2e/global-setup.ts',
  // Las pruebas @operacion reinician contenedores reales: solo corren con npm run test:e2e:ops
  grepInvert: process.argv.some((arg) => arg.includes('@operacion')) ? undefined : /@operacion/,
  // Comparten la misma base de datos (y el límite de intentos del login): una prueba a la vez
  workers: 1,
  fullyParallel: false,
  retries: process.env.CI ? 1 : 0,
  // Holgados para el Docker local en Windows, donde cada petición a Laravel tarda un par de segundos
  timeout: 90_000,
  expect: { timeout: 20_000 },
  // En el CI, además, cada falla queda anotada en la línea del PR
  reporter: process.env.CI ? [['list'], ['github']] : 'list',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8088',
    locale: 'es-CO',
    timezoneId: 'America/Bogota',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: Object.entries(VIEWPORTS).map(([name, viewport]) => ({
    name,
    use: {
      ...devices['Desktop Chrome'],
      viewport,
      // En celular y tablet, la app se comporta como en una pantalla táctil
      isMobile: name === 'celular' || name === 'tablet',
      hasTouch: name === 'celular' || name === 'tablet',
    },
  })),
})
