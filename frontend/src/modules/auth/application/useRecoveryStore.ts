// Recuperación de contraseña en curso: el correo, el pase que entregó el código y si la cuenta pide el segundo factor.
// Solo en memoria: si se recarga la página, se empieza de nuevo (el pase es de un solo uso y dura pocos minutos).
// Documentado en docs/frontend/auth.md
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { authApi } from '../infrastructure/authApi'

export const useRecoveryStore = defineStore('password-recovery', () => {
  const email = ref('')
  const ticket = ref<string | null>(null)
  const mfaRequired = ref(false)

  async function request(address: string): Promise<void> {
    await authApi.forgotPassword(address)
    email.value = address
    ticket.value = null
  }

  async function verify(code: string): Promise<void> {
    const result = await authApi.verifyResetCode(email.value, code)
    ticket.value = result.ticket
    mfaRequired.value = result.mfaRequired
  }

  async function reset(input: {
    password: string
    confirmation: string
    mfaCode?: string
    recoveryCode?: string
  }): Promise<void> {
    if (!ticket.value) throw new Error('Sin pase de recuperación')
    await authApi.resetPassword({ email: email.value, ticket: ticket.value, ...input })
    clear()
  }

  function clear(): void {
    email.value = ''
    ticket.value = null
    mfaRequired.value = false
  }

  return { email, ticket, mfaRequired, request, verify, reset, clear }
})
