---
title: "ADR-0019: Cifrado en tránsito y en reposo"
description: "Cifrado en tránsito y en reposo"
audience: tecnico
area: decisiones
order: 29
---

# ADR-0019: Cifrado en tránsito y en reposo

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Los datos deben protegerse sin romper funciones obligatorias, como la búsqueda por texto.

## Alternativas evaluadas
- Cifrado a nivel de aplicación en los campos sensibles.
- Cifrado en reposo del almacenamiento de la base de datos.

## Decisión
- **En tránsito:** TLS en el despliegue, con HSTS. El dominio `.dev` exige HTTPS siempre.
- **En reposo:** cifrado de tablas de InnoDB.
- **A nivel de aplicación:** solo para los secretos que deben poder leerse de vuelta, como el secreto TOTP.
- **Secretos de configuración:** únicamente en variables de entorno, nunca en el repositorio.
- **Logs:** sin contraseñas, tokens ni datos personales completos.
- **En local:** HTTP, porque `localhost` ya es un contexto seguro para el navegador.

## Alternativas descartadas
- **Cifrar título y descripción a nivel de aplicación:** el texto cifrado no se puede indexar, y la búsqueda obligatoria dejaría de funcionar.

## Consecuencias
- Los datos quedan protegidos ante un robo del disco o de los respaldos, y la búsqueda sigue funcionando.
