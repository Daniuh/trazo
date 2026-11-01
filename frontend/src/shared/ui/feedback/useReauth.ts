// Reautenticación en la pantalla: intenta el cambio sin código y, si la API lo pide, abre el diálogo del código
// (AppCodeDialog) y lo reintenta con él. Así el front no decide qué cambios son sensibles: lo decide la API.
// Documentado en docs/frontend/http-y-errores.md
import { reactive, watch } from 'vue'
import { needsCode } from '@/shared/http'

export interface ReauthTexts {
  title: string
  message: string
  confirmLabel: string
  danger?: boolean
}

type CodeAction = (code: string) => Promise<void>

export function useReauth() {
  const dialog = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: '',
    danger: false,
    // Se reemplaza al abrir el diálogo
    action: (async () => {}) as CodeAction,
  })

  /**
   * Devuelve true si el cambio se hizo; false si la persona cerró el diálogo sin confirmar. Cualquier otro error sigue.
   * Con "askFirst", el diálogo se abre de una vez (el cambio siempre lo pide, p. ej. restablecer el MFA).
   */
  async function run(
    attempt: (code?: string) => Promise<unknown>,
    texts: ReauthTexts,
    askFirst = false,
  ): Promise<boolean> {
    if (!askFirst) {
      try {
        await attempt()
        return true
      } catch (raw) {
        if (!needsCode(raw)) throw raw
      }
    }

    return new Promise((resolve) => {
      let done = false
      Object.assign(dialog, {
        ...texts,
        danger: texts.danger ?? false,
        open: true,
        action: async (code: string) => {
          await attempt(code)
          done = true
          resolve(true)
        },
      })
      // Si se cierra sin confirmar, el cambio no se hizo
      const stop = watch(
        () => dialog.open,
        (open) => {
          if (open) return
          stop()
          if (!done) resolve(false)
        },
      )
    })
  }

  return { dialog, run }
}
