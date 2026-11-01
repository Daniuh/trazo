---
title: Flujos
description: Cada flujo de Trazo en carriles por actor, con el paso a paso, los errores que ve la persona y dónde está en el código.
audience: todos
area: flujos
order: 1
---

# Flujos

Cada documento de esta carpeta describe un flujo completo de Trazo: quién hace qué (la persona, el navegador, la API, Redis, la base de datos, la cola, el correo), en qué orden, qué se decide en cada punto y qué pasa cuando algo falla. Están agrupados por área:

| Carpeta | Contenido |
|---|---|
| [acceso](acceso/) | Ingreso, verificación en dos pasos, contraseña y sesión |
| [incidentes](incidentes/) | Crear, editar, estados, asignación, eliminar, reapertura, conversación, listado, exportación y Mis incidentes |
| [resumen](resumen/) | Cifras del dashboard con caché y Personalizar |
| [avisos](avisos/) | Tiempo real, notificaciones, avisos de coordinación y vencidos |
| [administracion](administracion/) | Personas, roles, permisos, estado y restablecer acceso |
| [auditoria-y-sistema](auditoria-y-sistema/) | Auditoría, eventos, salud, acciones de sistema, mantenimiento y jobs fallidos |
| [operacion](operacion/) | Arranque, respaldos, despliegue y el camino de una rama a producción |

## Cómo se lee un flujo

- **Carriles:** cada fila es un actor. Un paso vive en el carril de quien lo hace.
- **Formas:** el inicio va en negro, los pasos en tarjeta, las decisiones en rombo con sus salidas ("Sí", "No" u otra etiqueta), el final correcto en verde y los errores en rojo con la línea punteada.
- **Números:** cada paso lleva un número que coincide con la lista "Paso a paso". Las salidas de error no se numeran: se explican en la tabla "Qué ve la persona en cada error", con el código de la API y el mensaje real de la pantalla.

## Formato del bloque `flujo`

El diagrama no se dibuja a mano: se describe en un bloque de código `flujo` (YAML) y el portal de documentación lo convierte en el dibujo de carriles y en la lista "Paso a paso". En GitHub el bloque se ve como texto, que también se lee de corrido.

```yaml
carriles: [Persona, Navegador, API]
pasos:
  - id: escribe            # identificador único dentro del flujo
    carril: Persona        # uno de los carriles
    tipo: inicio           # inicio | paso | decision | fin | error (paso si se omite)
    texto: Escribe sus datos
    detalle: Explicación que aparece en el paso a paso. Admite `código` y **negrita**.
    va: envia              # siguiente paso (o una lista)
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Datos válidos?
    si: guarda             # salida "Sí"
    no: error-422          # salida "No"
  - id: elige
    carril: API
    tipo: decision
    texto: ¿Qué sigue?
    va:                    # salidas con etiqueta propia
      - { a: completo, etiqueta: Sin MFA }
      - { a: verifica, etiqueta: Con MFA }
```

Reglas que revisa la construcción del portal (si alguna falla, no se publica):

- Cada `id` es único, cada `carril` está en la lista y cada salida apunta a un paso que existe.
- Toda decisión tiene al menos una salida.
- Un texto con dos puntos seguidos de espacio ("Límite: 5") va entre comillas simples; si no, YAML lo toma como otro campo.
- El orden de la lista es el orden de lectura: un paso que apunta hacia atrás se dibuja como vuelta, con línea punteada.

## Cómo se mantiene

Cada flujo se escribe leyendo el código, no de memoria: los límites, tiempos, códigos de error y mensajes salen de la configuración y del catálogo de mensajes. Si un cambio de código altera un flujo, el mismo cambio actualiza su documento. Los enlaces de "Dónde está en el código" los valida el CI.
