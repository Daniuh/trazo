{{-- Bienvenida a una cuenta nueva, en texto plano. Documentado en docs/backend/users.md --}}
Hola, {{ $name }}:

Te crearon una cuenta en Trazo con el rol {{ $role }}.

Para ingresar, primero crea tu contraseña:

1. Entra a {{ $url }}
2. Escribe tu correo ({{ $email }}) y pide el código.
3. Escribe el código que te llegará a este correo y elige tu contraseña.

Después podrás ingresar con tu correo y esa contraseña. La primera vez, Trazo te pedirá configurar la verificación en dos pasos con una aplicación de autenticación en tu teléfono.

Nadie de Trazo te pedirá tu contraseña ni tus códigos.

— Trazo
