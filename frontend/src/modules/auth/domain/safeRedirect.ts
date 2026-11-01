// Después del login se vuelve a la página que se estaba visitando, pero solo si es una ruta interna.
// Evita que un enlace manipulado (?volver=https://sitio-falso) saque a la persona de Trazo.
// Documentado en docs/frontend/auth.md

export function safeRedirect(target: unknown): string | null {
  if (typeof target !== 'string') return null

  // "/algo" sí; "//sitio" o "/\sitio" no, porque el navegador los trata como otro dominio
  const isInternal = target.startsWith('/') && !target.startsWith('//') && !target.startsWith('/\\')

  return isInternal ? target : null
}
