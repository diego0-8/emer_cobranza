# whatssap_handoff — Paquete de integración WhatsApp + Kommo

Esta carpeta concentra **todo el código** del módulo WhatsApp/Kommo de `emer_cobranza` y la documentación para el siguiente agente/dev.

## Contenido

| Ruta | Descripción |
|------|-------------|
| [HANDOFF.md](./HANDOFF.md) | Arquitectura, flujos, endpoints, instalación, limitaciones |
| [PREGUNTAS_INTEGRACION.md](./PREGUNTAS_INTEGRACION.md) | Checklist de preguntas al proveedor / siguientes pasos |
| [codigo/](./codigo/) | Código fuente completo del módulo (sin secretos reales) |
| [snippets/](./snippets/) | Fragmentos a fusionar en `index.php` y `gestionar_cliente.php` |

## Código incluido (`codigo/`)

```text
codigo/
  config/kommo.php
  config/kommo.local.php.example
  controllers/WhatsappController.php
  models/WhatsappConversacionModel.php
  models/WhatsappMensajeModel.php
  assets/css/whatsapp-panel.css
  assets/js/whatsapp-panel.js
  sql/004_wa_kommo_schema.sql
  wa_webhook.php
  scripts/  (probes y sync)
  .gitignore.snippet
```

## Importante

- **No** se incluyó `kommo.local.php` real (tokens). Usar el `.example`.
- El handoff anterior en la raíz del repo (`HANDOFF_WHATSAPP_KOMMO.md`) queda histórico; **esta carpeta es la fuente actualizada** tras las pruebas con Ngrok + API talks + sync de mensajes.
