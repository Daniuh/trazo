// El buzón de las pruebas E2E: lee de Mailpit el último código de recuperación que llegó a una cuenta, como haría la
// persona en su correo. El correo sale por la cola, así que espera a que llegue. Documentado en docs/calidad/estrategia-de-pruebas.md
import { expect, type APIRequestContext } from '@playwright/test'

const MAILPIT = process.env.E2E_MAILPIT_URL ?? 'http://localhost:8025'
const SUBJECT = 'Tu código para recuperar la contraseña de Trazo'

interface Summary {
  ID: string
  Subject: string
  Created: string
}

// El código del correo más reciente enviado después de `since`
export async function resetCodeFor(request: APIRequestContext, email: string, since: Date): Promise<string> {
  let code = ''
  await expect
    .poll(
      async () => {
        const search = await request.get(`${MAILPIT}/api/v1/search`, { params: { query: `to:${email}`, limit: '5' } })
        const { messages } = (await search.json()) as { messages: Summary[] }
        const latest = messages.find((message) => message.Subject === SUBJECT && new Date(message.Created) >= since)
        if (!latest) return ''

        const message = (await (await request.get(`${MAILPIT}/api/v1/message/${latest.ID}`)).json()) as { Text: string }
        code = /\b(\d{6})\b/.exec(message.Text)?.[1] ?? ''
        return code
      },
      { message: `Código de recuperación para ${email}`, timeout: 30_000 },
    )
    .not.toBe('')

  return code
}
