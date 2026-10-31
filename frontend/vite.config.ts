// Configuración de Vite: build de la SPA y servidor de desarrollo detrás del gateway.
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    // El navegador entra por el gateway, así que la recarga en caliente usa su puerto
    hmr: { clientPort: Number(process.env.VITE_HMR_CLIENT_PORT ?? 8088) },
  },
})
