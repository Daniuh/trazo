// Revisión visual de nombres, apellidos y correo antes de enviar: solo evita un viaje a la API con algo incompleto. La
// API valida de verdad (incluido que el correo no esté en uso). Documentado en docs/frontend/admin.md
import { fieldMessageFor } from '@/shared/errors'
import { looksLikeEmail } from '@/shared/validation/email'
import type { ProfileDraft } from '../domain/user'

export const NAME_PART_MAX_LENGTH = 80

export function profileErrors(draft: ProfileDraft): Partial<Record<keyof ProfileDraft, string>> {
  const errors: Partial<Record<keyof ProfileDraft, string>> = {}
  if (!draft.givenNames.trim()) errors.givenNames = fieldMessageFor({ code: 'REQUIRED' })
  if (!draft.familyNames.trim()) errors.familyNames = fieldMessageFor({ code: 'REQUIRED' })

  if (!draft.email.trim()) errors.email = fieldMessageFor({ code: 'REQUIRED' })
  else if (!looksLikeEmail(draft.email)) errors.email = fieldMessageFor({ code: 'INVALID_EMAIL' })
  return errors
}

// Lo que se envía: sin espacios al inicio, al final ni repetidos (la API hace lo mismo)
export function cleanProfile(draft: ProfileDraft): ProfileDraft {
  const clean = (text: string) => text.trim().replace(/\s+/g, ' ')
  return { givenNames: clean(draft.givenNames), familyNames: clean(draft.familyNames), email: draft.email.trim() }
}
