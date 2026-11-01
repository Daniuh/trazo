// El buzón de las pruebas E2E: lee de Mailpit el último correo con cierto asunto que llegó a una cuenta, como haría la
// persona en su correo (p. ej. el código de recuperación o la bienvenida). Los correos salen por la cola, así que espera
// a que lleguen. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, type APIRequestContext } from '@playwright/test'

const MAILPIT = process.env.E2E_MAILPIT_URL ?? 'http://localhost:8025'
const RESET_SUBJECT = 'Tu código para recuperar la contraseña de Trazo'

interface Summary {
  ID: string
  Subject: string
  Created: string
}

// El texto del correo más reciente con ese asunto, enviado después de `since`
export async function mailTextFor(
  request: APIRequestContext,
  email: string,
  subject: string,
  since: Date,
): Promise<string> {
  let text = ''
  await expect
    .poll(
      async () => {
        const search = await request.get(`${MAILPIT}/api/v1/search`, { params: { query: `to:${email}`, limit: '5' } })
        const { messages } = (await search.json()) as { messages: Summary[] }
        const latest = messages.find((message) => message.Subject === subject && new Date(message.Created) >= since)
        if (!latest) return ''

        const message = (await (await request.get(`${MAILPIT}/api/v1/message/${latest.ID}`)).json()) as { Text: string }
        text = message.Text
        return text
      },
      { message: `Correo "${subject}" para ${email}`, timeout: 30_000 },
    )
    .not.toBe('')

  return text
}

// El código de recuperación del correo más reciente enviado después de `since`
export async function resetCodeFor(request: APIRequestContext, email: string, since: Date): Promise<string> {
  const text = await mailTextFor(request, email, RESET_SUBJECT, since)
  return /\b(\d{6})\b/.exec(text)?.[1] ?? ''
}
