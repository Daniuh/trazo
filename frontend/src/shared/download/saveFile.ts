// Guarda en el equipo un archivo que llegó por la API (con el token en la cabecera no sirve un enlace directo).
// Documentado en docs/frontend/http-y-errores.md

// Nombre que propone el servidor en Content-Disposition; si no viene, el de respaldo
export function filenameFrom(disposition: string | null | undefined, fallback: string): string {
  const encoded = /filename\*=UTF-8''([^;]+)/i.exec(disposition ?? '')?.[1]
  if (encoded) return decodeURIComponent(encoded)
  return /filename="?([^";]+)"?/i.exec(disposition ?? '')?.[1] ?? fallback
}

export function saveFile(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.append(link)
  link.click()
  link.remove()
  // El navegador ya tomó el archivo; se libera la memoria
  setTimeout(() => URL.revokeObjectURL(url), 0)
}
