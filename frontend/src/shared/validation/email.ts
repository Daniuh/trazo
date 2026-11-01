// Revisión visual de un correo: solo evita un viaje a la API con algo que claramente no es un correo. La API valida
// de verdad. Documentado en docs/frontend/http-y-errores.md

const EMAIL_SHAPE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export function looksLikeEmail(value: string): boolean {
  return EMAIL_SHAPE.test(value.trim())
}
