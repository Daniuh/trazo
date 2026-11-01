// Variables de entorno que la SPA puede leer en tiempo de build.
/// <reference types="vite/client" />

interface ImportMetaEnv {
  // Por defecto /api/v1: la API comparte origen con la SPA detrás del gateway
  readonly VITE_API_BASE_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
