// Revisión visual de nombre y correo antes de enviar: solo evita un viaje a la API con algo incompleto. La API
// valida de verdad (incluido que el correo no esté en uso). Documentado en docs/frontend/admin.md
import { fieldMessageFor } from '@/shared/errors'
import { looksLikeEmail } from '@/shared/validation/email'
import type { ProfileDraft } from '../domain/user'

export const NAME_MIN_LENGTH = 2

export function profileErrors(draft: ProfileDraft): Partial<Record<keyof ProfileDraft, string>> {
  const errors: Partial<Record<keyof ProfileDraft, string>> = {}
  const name = draft.name.trim()
  if (!name) errors.name = fieldMessageFor({ code: 'REQUIRED' })
  else if (name.length < NAME_MIN_LENGTH)
    errors.name = fieldMessageFor({ code: 'TOO_SHORT', params: { min: NAME_MIN_LENGTH } })

  if (!draft.email.trim()) errors.email = fieldMessageFor({ code: 'REQUIRED' })
  else if (!looksLikeEmail(draft.email)) errors.email = fieldMessageFor({ code: 'INVALID_EMAIL' })
  return errors
}
