// Configuración de Vite y Vitest: Quasar como plugin, alias @ y servidor de desarrollo detrás del gateway.
// Documentado en docs/frontend/index.md
import { fileURLToPath, URL } from 'node:url'
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

const fromRoot = (path: string) => fileURLToPath(new URL(path, import.meta.url))

export default defineConfig({
  plugins: [
    vue({ template: { transformAssetUrls } }),
    // Las variables de Quasar salen de los tokens de diseño, nunca de valores sueltos
    quasar({ sassVariables: fromRoot('./src/design/theme/quasar-variables.scss') }),
  ],
  resolve: {
    alias: { '@': fromRoot('./src') },
  },
  // Se preempaquetan al arrancar: así las rutas diferidas no obligan a Vite a reoptimizar en caliente
  optimizeDeps: {
    include: [
      'vue',
      'vue-router',
      'pinia',
      'quasar',
      'quasar/lang/es',
      'axios',
      'laravel-echo',
      'pusher-js',
      'grid-layout-plus/es/components/grid-layout.vue.mjs',
      'grid-layout-plus/es/components/grid-item.vue.mjs',
      'qrcode',
    ],
  },
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    // El navegador entra por el gateway, así que la recarga en caliente usa su puerto
    hmr: { clientPort: Number(process.env.VITE_HMR_CLIENT_PORT ?? 8088) },
    // En Docker sobre Windows o macOS los archivos montados no avisan cambios: se revisan por sondeo
    watch: { usePolling: process.env.VITE_USE_POLLING === 'true', interval: 300 },
    // Compila al arrancar el layout y cada pantalla (con su SCSS): la primera visita a una ruta ya no espera a Vite.
    // Solo afecta al desarrollo; el build de producción no lo usa
    warmup: {
      clientFiles: ['./src/main.ts', './src/app/layout/AppShell.vue', './src/modules/*/presentation/**/*Page.vue'],
    },
  },
  test: {
    environment: 'jsdom',
    include: ['src/**/*.spec.ts'],
    coverage: {
      provider: 'v8',
      include: ['src/**/*.{ts,vue}'],
      exclude: ['src/**/*.spec.ts', 'src/main.ts', 'src/shared/testing/**'],
      // La meta aprobada (90 %) aplica a dominio y aplicación de cada módulo. Las pantallas (.vue) se prueban con la
      // E2E en las cuatro vistas, que Vitest no mide. Documentado en docs/calidad/estrategia-de-pruebas.md
      thresholds: {
        'src/modules/*/{domain,application}/**': { statements: 90, lines: 90 },
      },
    },
  },
})
