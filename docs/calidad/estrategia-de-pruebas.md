---
title: Estrategia de pruebas
description: Qué se prueba, con qué herramientas y cuándo.
audience: tecnico
area: calidad
order: 2
---

# Estrategia de pruebas

## Backend

Las pruebas usan Pest y corren dentro del contenedor contra MySQL real, en la base `trazo_test`. Nunca se usa SQLite: FULLTEXT, las restricciones CHECK y las claves foráneas se comportan distinto y darían falsa confianza.

| Carpeta | Tipo | Qué cubre |
|---|---|---|
| `tests/Arch` | Arquitectura | Separación de capas por módulo, tipos estrictos, sin funciones de depuración ni lectura de entorno fuera de `config/` |
| `tests/Unit` | Unitarias | Reglas de dominio, sin base de datos |
| `tests/Feature` | API | Endpoints completos: respuestas, errores, permisos y consultas |

Una prueba de entorno garantiza que las pruebas corren en `testing` y contra `trazo_test`, para que nunca toquen la base de desarrollo. Las variables de pruebas se fuerzan en `phpunit.xml` porque, dentro del contenedor, las variables del propio contenedor tendrían prioridad.

### Análisis estático y formato

- **Larastan** en nivel 8 sobre `app/`, `bootstrap/app.php` y `routes/`.
- **Pint** con el preset de Laravel más tipos estrictos y comparaciones estrictas.

### Cuándo se ejecuta

| Momento | Qué corre |
|---|---|
| Antes de cada commit | Formato (Pint) sobre los archivos PHP modificados |
| Antes de fusionar a `dev` | `composer check`: formato, análisis estático y todas las pruebas |
| Antes de fusionar a `dev` y en el CI | `composer coverage`: las pruebas con PCOV y `tests/coverage-check.php`, que exige **90 % en las capas Domain y Application** y **80 % en general**. Hoy: 97,7 % y 97,0 % |
| En el CI (cuando el repositorio esté publicado) | Todo lo anterior en cada Pull Request, dentro del mismo stack de Docker. Ver [Integración continua](../operacion/integracion-continua.md). |

## Frontend

| Herramienta | Qué revisa |
|---|---|
| **Vitest** | Pruebas unitarias y de componentes, junto a cada archivo (`*.spec.ts`) |
| **vue-tsc** | Tipos en TypeScript estricto (incluye `noUncheckedIndexedAccess`) |
| **ESLint** | Reglas de Vue y TypeScript; `v-html` y `eval` prohibidos |
| **Stylelint** | Ningún color, función de color ni `@media` fuera de `design/` |
| **Prettier** | Formato uniforme |

`npm run check` ejecuta tipos, lint y pruebas. Antes de cada commit, Lefthook corre ESLint, Prettier y Stylelint sobre los archivos modificados.

**Cobertura.** `npm run test:coverage` exige **90 % en dominio y aplicación** de cada módulo (umbral en `vite.config.ts`); hoy está en 96,4 %. Vitest prueba la lógica: reglas puras, criterios en la URL y los composables que conectan cada pantalla con la API, con la API simulada. Las pantallas (`.vue`) las prueba la E2E en las cuatro vistas, con el navegador real, así que el front no tiene una meta "general" en Vitest ([ADR-0060](../decisiones/0060-pasada-de-calidad.md)). Los composables que usan la URL, `onMounted` o un store se montan con `withSetup` (`shared/testing/withSetup.ts`): router en memoria, Pinia y desmontaje automático al terminar cada prueba.

### De extremo a extremo (Playwright)

Los flujos críticos se prueban en un navegador real contra el stack de Docker completo, **en las cuatro vistas**: celular (375 × 812, táctil), tablet (768 × 1024, táctil), portátil (1280 × 800) y monitor (1680 × 1000). Cada flujo corre una vez por vista.

| Flujo | Qué comprueba |
|---|---|
| Login | Mensaje claro con datos incorrectos; sin sesión lleva al login y vuelve a la página pedida; cerrar sesión |
| Incidentes | Crear, ver el detalle, cambiar el estado y encontrarlo con la búsqueda del listado |
| Filtros | Llegan desde la URL, se ven como etiquetas y se quitan |
| Permisos | El solicitante no ve los filtros de gestión |
| Dashboard | Cada rol ve su resumen (general o personal, con sus widgets); "Vencidos" lleva al listado filtrado; la tendencia cambia de período |
| MFA | Con una cuenta propia (agente3): la primera vez se configura desde la pantalla (QR, clave y 8 códigos de recuperación); las siguientes, un código incorrecto avisa los intentos que quedan y el correcto deja entrar. Mi cuenta muestra "Activa". Todo pasa por axe |
| Usuarios | Con el Super admin, en las cuatro vistas: crear una cuenta (la bienvenida llega a Mailpit y no lleva contraseña), concederle un permiso adicional y desactivarla. En portátil, dar el rol Administrador pide el código de la aplicación y se escribe con el "teléfono" de las pruebas. En tablet, el Administrador ve al Super admin sin ningún botón. Cada ejecución crea sus propias cuentas y las deja desactivadas |
| Roles | Con el Super admin, en las cuatro vistas: crear un rol copiando los permisos de otro, editarlo y eliminarlo. Cada vista usa su propio nivel (51 a 54) y, antes de empezar, borra por la API los roles `e2e_` que haya dejado una corrida fallida (`authHeaderOf`). En portátil, relajar la verificación en dos pasos pide el código. En tablet, el rol Super admin se ve sin campos y con el aviso de por qué |
| Tiempo real | Con dos personas a la vez: una cambia algo por la API y la otra lo ve sin recargar. En las cuatro vistas, el listado muestra "Hay 1 incidente con cambios de otras personas" sin mover las filas y "Actualizar" lo trae. En portátil, el detalle se actualiza solo con el nuevo estado y su historial. En monitor, a quien le asignan un incidente le llega "Te asignaron INC-…" y "Abrir" lo lleva al detalle. En tablet, el menú muestra "Auditoría" en cuanto el Super admin le concede el permiso (y la prueba lo quita al final). Cada prueba espera a que la pestaña se suscriba antes de provocar el cambio. En portátil, el formulario de edición avisa "Otra persona cambió este incidente" sin borrar lo escrito. En monitor, al desactivar una cuenta su pantalla vuelve sola al login, sin recargar (la prueba la reactiva al terminar) |
| Exportación | En las cuatro vistas, el supervisor crea un incidente por la API, filtra el listado por su código, exporta (botón en portátil y monitor, menú ⋯ en tablet y celular), confirma y recibe un CSV con BOM, punto y coma, el encabezado y exactamente esa fila. En celular y portátil, un agente no ve la opción |
| Sistema | En las cuatro vistas, se provoca un ingreso bloqueado por la API y el Super admin ve el panel de salud, encuentra el evento (con la cuenta enmascarada), lo abre y lo marca como revisado. En portátil, "Limpiar caché de datos" pide el código de la aplicación. En monitor, con dos personas: el Super admin activa el mantenimiento, ve el aviso fijo y sigue trabajando; la administradora ve "Trazo está en mantenimiento" y vuelve a entrar con "Reintentar" cuando se desactiva (la prueba lo desactiva siempre al terminar, aunque falle) |
| Auditoría | En las cuatro vistas, el supervisor crea un incidente por la API, lo encuentra en la auditoría por su código (en tablet y celular, desde el panel de filtros), abre el registro con su IP y navegador y llega al incidente con "Abrir incidente". En tablet y monitor, el Administrador entra directo a la pestaña de administración, sin selector, y el Super admin cambia entre las dos pestañas |
| Contraseña | Con una cuenta propia (solicitante3). En celular y monitor, la recuperación completa: el código se lee de Mailpit, se escribe el código del MFA si la cuenta ya lo tiene y se entra con la contraseña nueva. En tablet y portátil, el cambio desde Mi cuenta (en tablet, también el error de la contraseña actual). Siempre termina con la contraseña de demostración |
| Personalizar | En portátil y monitor: quitar con el menú ⋯, agregar, guardar y restaurar el original; aviso al salir con cambios sin guardar y al cancelar. En tablet y celular no hay botón y el ícono de información explica dónde hacerlo |

- **Accesibilidad:** el login, el formulario, el detalle, el listado, el dashboard y su modo Personalizar pasan por axe-core con las reglas WCAG 2.1 A y AA. Cualquier problema hace fallar la prueba.
- **Sesión:** salvo en las pruebas del propio login, la sesión se abre por la API una sola vez por usuario y vista. Así se respeta el límite real de 5 intentos por minuto sin desactivarlo para las pruebas.
- **Límites de frecuencia:** la API acepta 3 códigos de recuperación cada 15 minutos y 5 cambios por minuto por persona, así que cada flujo de contraseña corre solo en dos vistas. Al empezar, la suite corre `trazo:reset-limits` (`e2e/global-setup.ts`). Ese comando, solo de desarrollo, vacía la base de Redis de los contadores de seguridad sin tocar la caché ni el mantenimiento, y así la suite se puede repetir seguida. Contra otro servidor (`E2E_BASE_URL`) no hace nada.
- **Accesibilidad de todas las pantallas** (`accessibility.spec.ts`):
  - Recorre cada ruta (públicas y con sesión) en las cuatro vistas con axe, y falla si la consola reporta una violación de la CSP.
  - En portátil y monitor, recorre con el teclado:
    - "Saltar al contenido" es lo primero y lleva al contenido.
    - Se crea un incidente sin el ratón.
    - Un diálogo se cierra con Escape y el foco vuelve al botón que lo abrió.
- **Pruebas de operación** (`operations.spec.ts`, etiqueta `@operacion`): reiniciar workers y el tiempo real desde el panel de sistema contra los contenedores reales. Comprueban que Docker los levanta otra vez, que el panel vuelve a "Bien" y que la pestaña se reconecta sola. No corren en la suite normal porque reinician servicios compartidos: `npm run test:e2e:ops`.
- **Correo:** `e2e/mailpit.ts` lee de la API de Mailpit el último correo con cierto asunto (`mailTextFor`; la recuperación usa `resetCodeFor`) (`E2E_MAILPIT_URL`, por defecto `http://localhost:8025`), esperando a que la cola lo envíe.
- **Datos:** se usan los usuarios demo, y cada incidente creado lleva un título único, así que las pruebas se pueden repetir sobre la misma base.
- **Cómo correrlas:** con el stack arriba, `npm run test:e2e` en `frontend/` (o `npx playwright test --project=tablet` para una sola vista). La primera vez hace falta `npx playwright install chromium`. `E2E_BASE_URL` cambia la dirección (por defecto, `http://localhost:8088`).
- **Tiempos de espera holgados:** en Docker sobre Windows cada petición a Laravel tarda un par de segundos, porque el código se lee desde una carpeta montada. En el CI, sobre Linux, no pasa.

## Dónde está en el código

- [`backend/tests/`](../../backend/tests/) y [`backend/phpunit.xml`](../../backend/phpunit.xml)
- [`backend/phpstan.neon`](../../backend/phpstan.neon) y [`backend/pint.json`](../../backend/pint.json)
- [`frontend/eslint.config.js`](../../frontend/eslint.config.js) y [`frontend/stylelint.config.js`](../../frontend/stylelint.config.js)
- E2E: [`frontend/playwright.config.ts`](../../frontend/playwright.config.ts) y [`frontend/e2e/`](../../frontend/e2e/) (ayudas en `support.ts`)
- [`lefthook.yml`](../../lefthook.yml)
- Decisiones: [ADR-0031](../decisiones/0031-estrategia-de-pruebas.md), [ADR-0032](../decisiones/0032-calidad-estatica.md) y [ADR-0046](../decisiones/0046-pruebas-e2e.md).
