---
title: "ADR-0055: Edición de roles: rol de la cima bloqueado, nivel único, roles de sistema y sin correo masivo"
description: "Edición de roles: rol de la cima bloqueado, nivel único, roles de sistema y sin correo masivo"
audience: tecnico
area: decisiones
order: 65
---

# ADR-0055: Edición de roles: rol de la cima bloqueado, nivel único, roles de sistema y sin correo masivo

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Con la jerarquía por nivel y los permisos por persona ([ADR-0053](0053-administracion-de-usuarios.md)), faltaba poder crear, editar y eliminar roles desde la pantalla. Había que decidir:

- Quién lo hace.
- Qué pasa con el rol de la cima.
- Si dos roles pueden tener el mismo nivel.
- Cómo se protegen los roles base.
- Qué cambios exigen confirmar la identidad.
- Si se avisa a las personas de un rol cuando este cambia.
- Qué pasa con el historial de un rol eliminado.

## Alternativas evaluadas
Rol de la cima (Super admin):
1. **Bloqueado: no se edita ni se elimina y siempre tiene todos los permisos.**
2. Editable como los demás.

Nivel:
1. **Único por rol (índice `UNIQUE`).**
2. Repetible.

Roles base:
1. **Columna `roles.is_system`; los marcados no se eliminan.**
2. Lista de códigos escrita en el código.

Confirmar cambios:
1. **El código de la aplicación al tocar permisos sensibles, cambiar el nivel o volver más permisiva la política de MFA.**
2. En cualquier cambio.
3. Nunca.

Avisos:
1. **Sin correo a las personas del rol; el cambio queda en el registro.**
2. Un correo a cada persona del rol.

Historial de un rol eliminado:
1. **`admin_actions.target_role_id` con `ON DELETE SET NULL`; el código y el nombre del rol viajan siempre en el detalle.**
2. Borrado lógico de roles.
3. `RESTRICT`, que impediría eliminar cualquier rol con historial.

## Decisión
- **`roles.manage`** permite crear, editar y eliminar roles. Por defecto solo lo tiene el Super admin. Quien lo reciba trabaja con la misma jerarquía de las personas:
  - solo roles por debajo de su nivel;
  - un nivel nuevo también por debajo del suyo;
  - nunca agrega un permiso que no tiene.
- **El rol de la cima está bloqueado** (`ROLE_LOCKED`). Ningún otro rol puede llegar a nivel 100: el máximo es 99.
- **El nivel es único.** Los roles base van de 20 en 20, así que hay espacio para intercalar.
- **Roles base:** los cinco se marcan con `is_system` y no se eliminan (`ROLE_SYSTEM`), aunque sí se editan.
- **Un rol creado** solo se elimina si nadie lo tiene (`ROLE_IN_USE`).
- **El código del rol** se elige al crearlo y no cambia: es la clave estable para migraciones e integraciones.
- **Piden el código de la aplicación:**
  - tocar permisos de usuarios, roles o sistema;
  - cambiar el nivel;
  - quitar la obligación del MFA o aumentar la gracia.

  Endurecer la política no lo pide.
- **Cambios con personas afectadas:** el front pide confirmación indicando a cuántas personas afecta. Los cambios aplican en la siguiente petición de cada persona. Las excepciones por persona que el cambio vuelve innecesarias se quitan.
- **Sin correo masivo.** Un rol puede tener mucha gente y su cambio es de política de la organización, no de una cuenta.
- **Al eliminar un rol,** su historial se conserva sin enlace (`SET NULL`), con el código y el nombre en el detalle.
- **El código de reautenticación viaja como `mfa_code`** en toda la administración (como en la recuperación de contraseña), porque `code` es el identificador del rol.

## Alternativas descartadas
- **Cima editable:** alguien podría quitarse `roles.manage` y nadie podría volver a administrar.
- **Nivel repetible:** dos roles iguales no se podrían administrar entre sí, y el orden de la jerarquía quedaría ambiguo.
- **Lista de códigos en el código:** es una regla de datos; la columna permite marcar otro rol sin tocar código.
- **Confirmar todo:** cambiar el nombre de un rol no justifica pedir el código. **Nunca confirmar:** un cambio de rol escala o reduce el acceso de mucha gente a la vez.
- **Correo a cada persona:** genera ruido y no aporta a la seguridad de cada cuenta. Los cambios que sí son de una cuenta (su rol, sus permisos) se siguen avisando.
- **Borrado lógico:** el código y el nivel quedarían ocupados para siempre. **`RESTRICT`:** ningún rol con historial se podría eliminar.

## Consecuencias
- Una persona que pierde un permiso por un cambio de su rol no recibe correo; el registro muestra quién lo hizo y cuándo.
- Si se quisiera reutilizar el código de un rol eliminado, se puede: el historial anterior lo nombra en su detalle, sin enlace.
