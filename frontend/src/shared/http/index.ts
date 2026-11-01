// API pública del módulo HTTP compartido.
export { ApiError, ClientErrorCode, isScreenLoadError, needsCode, toApiError } from './apiError'
export { maintenanceNotice } from './maintenance'
export type { ApiErrorDetails, ApiErrorKind, ErrorParams, FieldError } from './apiError'
export { createHttpClient, http } from './client'
export { httpEvents } from './events'
export { setSocketIdSource } from './socketId'
export { tokenStore } from './tokenStore'
