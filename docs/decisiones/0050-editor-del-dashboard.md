---
title: "ADR-0050: Editor del dashboard con grid-layout-plus, sin sus estilos inyectados y con alternativa de teclado"
description: "Editor del dashboard con grid-layout-plus, sin sus estilos inyectados y con alternativa de teclado"
audience: tecnico
area: decisiones
order: 60
---

# ADR-0050: Editor del dashboard con grid-layout-plus, sin sus estilos inyectados y con alternativa de teclado

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
[ADR-0030](0030-dashboard-personalizable.md) eligió `grid-layout-plus` para el modo "Personalizar" en portátil y monitor. Al construirlo aparecieron cuatro preguntas:

- **Estilos.** La entrada principal del paquete trae su CSS dentro del JavaScript y lo inserta con un `<style>` al cargarse. Una política de seguridad de contenido (CSP) estricta lo bloquea, y además sus colores no son los de Trazo (el destino del arrastre es rojo).
- **Teclado.** Arrastrar y estirar con el mouse no sirve para quien navega con teclado ni para un lector de pantalla.
- **Huecos.** Al mover o quitar un widget pueden quedar espacios vacíos en la grilla.
- **Cuándo se guarda.** Hay que definir si cada cambio se guarda al momento o todo junto al final.

## Alternativas evaluadas
Cómo cargar la librería:
1. La entrada principal, con sus estilos inyectados.
2. **Los componentes sueltos (`es/components/*.vue.mjs`), que no inyectan nada, con los estilos escritos en Trazo a partir de tokens.**
3. Escribir una grilla propia con arrastre.

Teclado:
1. Flechas del teclado sobre el widget enfocado.
2. **Un menú ⋯ por widget con mover arriba, abajo, a la izquierda y a la derecha; más ancho, más angosto, más alto y más bajo; y quitar.**

Huecos:
1. Dejar cada widget exactamente donde se suelta.
2. **Compactación vertical: después de cada cambio los widgets suben hasta donde no choquen.**

Cuándo se guarda:
1. Cada cambio al momento.
2. **Un borrador que se guarda con "Guardar" y se descarta con "Cancelar". "Restaurar original" es la excepción: pide confirmación y se aplica de inmediato.**

## Decisión
- **Componentes sueltos y estilos propios** (alternativa 2). `DashboardEditor.vue` es el único archivo que importa la librería y define las clases `.vgl-*` con tokens: marco punteado, zona de destino en azul translúcido, sombra al arrastrar y manija de la esquina.
- **Menú ⋯ con las mismas acciones del mouse.** Las opciones que chocan con un tope (el borde de la grilla, el ancho máximo) aparecen desactivadas. El nombre del botón incluye la medida actual ("6 columnas de ancho y 2 filas de alto"), porque con teclado no se ve.
- **Compactación vertical**, igual en el arrastre (la hace la librería) y en el menú (la hace `layoutEditing.ts`). Mover arriba o abajo intercambia el lugar con el vecino más cercano que comparte columnas. A los lados el widget se corre una columna y empuja hacia abajo lo que choque.
- **Borrador.** La página avisa antes de salir con cambios sin guardar (`useLeaveGuard`), tanto al cambiar de sección como al cerrar la pestaña. Si la ventana se achica a tablet en pleno modo edición, el borrador se conserva y aparece el aviso para agrandarla.
- **Agregar.** El widget agregado entra al final, con el tamaño y la columna que tenía guardados. Esto matiza [ADR-0048](0048-diseno-del-dashboard-por-persona.md): el back sigue guardando el lugar del oculto, pero al volver a agregarlo ese lugar suele estar ocupado, y ponerlo al final es lo más predecible.

## Alternativas descartadas
- **La entrada principal:** obligaría a abrir la CSP con `'unsafe-inline'` para estilos, solo por esta pantalla. Además los colores quedarían fuera de los tokens.
- **Una grilla propia:** el arrastre con mouse y toque, los topes y la compactación ya están resueltos y probados en la librería. Repetirlos es mucho código para mantener.
- **Flechas del teclado:** chocan con el desplazamiento de la página. Tampoco hay una convención clara para distinguir mover de estirar, y quien no la conoce no la descubre. El menú se explica solo.
- **Dejar los widgets donde se sueltan:** quedan huecos que en tablet y celular no se ven (allí se ordena por lectura), pero que en el computador parecen un error.
- **Guardar cada cambio al momento:** un arrastre a medias quedaría guardado, y no habría forma de arrepentirse de varios cambios a la vez.

## Consecuencias
- La librería se actualiza con cuidado: depende de que sigan existiendo los componentes sueltos y las clases `.vgl-*`. Las pruebas E2E del modo Personalizar avisan si algo cambia.
- La regla de edición (`layoutEditing.ts`) es pura y tiene pruebas unitarias. El back vuelve a validar todo al guardar ([ADR-0048](0048-diseno-del-dashboard-por-persona.md)).
- Un widget que no puede subir porque otro lo tapa deja un hueco arriba. Es el mismo comportamiento que al arrastrar.
- Mientras se personaliza, las cifras siguen al borrador: un widget agregado pide sus datos sin esperar a guardar.
