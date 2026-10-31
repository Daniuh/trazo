{{-- Código para recuperar la contraseña, en texto plano. Documentado en docs/backend/auth.md --}}
Hola, {{ $name }}:

Tu código para recuperar la contraseña de Trazo es:

{{ $code }}

Vence en {{ $minutes }} minutos y sirve una sola vez. Nadie de Trazo te lo va a pedir: no lo compartas.

Si no pediste recuperar tu contraseña, ignora este correo. Tu contraseña sigue siendo la misma.

— Trazo
