{{-- Aviso de un cambio hecho por la administración, en texto plano. Documentado en docs/backend/users.md --}}
Hola, {{ $name }}:

{{ $title }} ({{ $happenedAt }}).

{{ $body }}
@if ($next !== '')

{{ $next }}
@endif

Si no esperabas este cambio, habla con la persona que administra Trazo en tu organización.

— Trazo
