# Handoff — Integración WhatsApp + Kommo (emer_cobranza)

Fecha: 2026-07-26  
Proyecto origen: `C:\xampp\htdocs\emer_cobranza`  
Carpeta de este paquete: `whatssap_handoff/`  
Estado: **Envío CRM → WhatsApp funcional**. **Recepción cliente → CRM vía sync API** (poll cada 5 s). Webhook genérico de Kommo **no** entrega eventos WABA.

---

## 1. Objetivo de negocio

1. El asesor escribe WhatsApp desde `views/gestionar_cliente.php` (ficha + softphone + panel WA debajo).
2. Toda conversación usable se amarra a `cliente_id` (`clientes.id_cliente`).
3. Abrir un chat:

```text
index.php?action=gestionar_cliente&id={cliente_id}&wa={conversacion_id}
```

4. Números solo del perfil (`tel1`…`tel10` / aliases `telefono`, `celular2`, `cel3`…).
5. Números desconocidos → cola `sin_cliente` hasta `wa_emparejar` (coord/admin).

---

## 2. Arquitectura real (lo que quedó en producción local)

```text
Cliente WhatsApp
       │
       ▼
Kommo (WhatsApp Business / WABA)
       │
       ├─► WEB HOOKS genérico (CRM leads) ──► NO trae mensajes de chat
       │
       └─► API v4 (token long-lived)
              │
              ├─ POST /api/v4/talks/{talk_id}/send_message   ← envío desde CRM
              ├─ GET  /api/v4/talks/{talk_id}/messages        ← sync entrantes + estados
              ├─ GET  /api/v4/contacts?query={tel}            ← resolver contacto
              └─ GET  /api/v4/talks?filter[contact_id]=…      ← resolver talk_id

CRM emer_cobranza
  wa_conversaciones / wa_mensajes
  views/gestionar_cliente.php → #waPanel + #waBubbleRail
  Polling JS cada 5s → wa_mensajes → sincronizarMensajesKommo()
```

Alias público webhook (validación GET/HEAD + stub POST):

```text
/wa_webhook.php  →  index.php?action=wa_webhook_kommo
```

---

## 3. Inventario de archivos (código en `codigo/`)

| Archivo | Rol |
|---------|-----|
| `codigo/config/kommo.php` | Constantes + `kommoEnabled()` + normalización E.164 |
| `codigo/config/kommo.local.php.example` | Plantilla de secretos (no versionar el real) |
| `codigo/controllers/WhatsappController.php` | Webhook, listar, enviar, sync, emparejar |
| `codigo/models/WhatsappConversacionModel.php` | Conversaciones, match teléfono→cliente |
| `codigo/models/WhatsappMensajeModel.php` | Mensajes idempotentes por `kommo_message_id` |
| `codigo/assets/css/whatsapp-panel.css` | UI panel + burbujas |
| `codigo/assets/js/whatsapp-panel.js` | Polling, selector, deep-link `?wa=` |
| `codigo/sql/004_wa_kommo_schema.sql` | Tablas `wa_*` |
| `codigo/wa_webhook.php` | Alias limpio sin query string |
| `codigo/scripts/*.php` | Probes / sync / verificar token |
| `snippets/index_php_rutas_wa.php` | Rutas a pegar en `index.php` |
| `snippets/gestionar_cliente_panel_wa.php` | Markup panel bajo softphone |

---

## 4. Base de datos

```sql
-- Ejecutar:
SOURCE sql/migrations/004_wa_kommo_schema.sql
-- o el archivo de este paquete:
SOURCE whatssap_handoff/codigo/sql/004_wa_kommo_schema.sql
```

Tablas: `wa_conversaciones`, `wa_mensajes`, `wa_asignaciones`, `wa_usuarios_map`.

Campos clave `wa_conversaciones`:

| Campo | Uso |
|-------|-----|
| `cliente_id` | Amarre UI asesor |
| `telefono_e164` | `+57…` |
| `kommo_talk_id` | ID talk API v4 (obligatorio para envío) |
| `kommo_chat_id` | UUID chat WABA |
| `asesor_id` | Cédula del asesor |
| `estado` | `abierta` / `cerrada` / `sin_cliente` |
| `wa_activo` | `desconocido` / `si` / `no` |
| `no_leidos` | Badge burbuja |

Match teléfono: últimos 10 dígitos en `clientes.tel1`…`tel10`.

---

## 5. Endpoints CRM

| Action | Método | Auth | Descripción |
|--------|--------|------|-------------|
| `wa_webhook_kommo` | GET/HEAD/POST | Público | Health 200; POST stub (formato simplificado) |
| `wa_estado` | GET | sesión | `kommo_enabled`, modo |
| `wa_mis_chats` | GET | asesor+ | Burbujas (limit 10) |
| `wa_conversacion_cliente` | GET | asesor+ | Conv / crea / cambia número |
| `wa_mensajes` | GET | asesor+ | Hilo + **sync Kommo API** |
| `wa_enviar` | POST JSON | asesor+ | Envía a Kommo si hay/resuelve `talk_id` |
| `wa_emparejar` | POST | coord/admin | `sin_cliente` → `cliente_id` |
| `wa_sin_cliente` | GET | coord/admin | Cola |

---

## 6. Credenciales Kommo (cómo se obtuvieron)

1. Subdominio = URL `https://{subdomain}.kommo.com`  
   Ejemplo real: `tecnologiatysbpocom`.
2. **Centro de integraciones → + Crear integración** (privada/custom).
3. Pestaña **Llaves y alcances**:
   - Generar **clave secreta**
   - Generar **token de larga duración** (JWT)
   - Copiar **ID de la integración**
4. Guardar en `config/kommo.local.php` (gitignored).
5. Scopes útiles del token: `crm`, `send_external_messages`, `list_external_messages`, …

`kommoEnabled()` = subdomain + token no vacíos.

---

## 7. Flujo de envío (CRM → WhatsApp)

1. Panel `wa_enviar` con `cliente_id`, `telefono`, `texto`.
2. Si no hay `kommo_talk_id`:
   - Busca contacto Kommo `GET /contacts?query={últimos10}`
   - Lista talks `GET /talks?filter[contact_id]=…`
   - Prefiere `origin=waba` / `in_work`
   - Guarda `kommo_talk_id` + `kommo_chat_id`
3. `POST /api/v4/talks/{talk_id}/send_message` `{ "text": "..." }` → HTTP **202** + `id`.
4. Status local: `enviado` → sync puede pasar a `delivered` / `error_envio`.

Si no hay contacto/talk en Kommo: el cliente debe escribir primero al Business o abrir chat en Kommo.

Regla Meta: fuera de ventana 24 h hace falta **plantilla** aprobada ([docs Kommo](https://support.kommo.com/docs/troubleshoot-whatsapp-message-errors)).

---

## 8. Flujo de recepción (WhatsApp → CRM) — crítico

### Hallazgo
El botón **WEB HOOKS** del Centro de integraciones valida URL (GET/HEAD 200) pero **no empuja mensajes WABA** a nuestro PHP.  
Prueba: Kommo talk 102 tenía 12 mensajes; CRM solo 4; faltaban todos los entrantes.

### Solución implementada
`WhatsappController::sincronizarMensajesKommo()` en cada `wa_mensajes`:

- `GET /api/v4/talks/{talk_id}/messages?limit=100`
- Inserta faltantes por `kommo_message_id` (único)
- Actualiza `delivery_status` → `enviado` / `delivered` / `error_envio`
- Actualiza preview de conversación

El JS hace poll cada 5 s → los entrantes aparecen sin webhook de chats.

### Scripts de verificación

```powershell
php scripts/probar_sincronizacion_kommo.php 102
php scripts/ejecutar_sincronizacion_kommo.php 102
```

---

## 9. Ngrok / URL pública

```powershell
ngrok http 80
```

URL webhook limpia:

```text
https://{subdominio-ngrok}.ngrok-free.app/emer_cobranza/wa_webhook.php
```

Inspector: `http://127.0.0.1:4040`  
Importante: GET/HEAD deben responder **200** (health), no 400.

---

## 10. UI asesor

- `#waBubbleRail`: hasta 10 chats + `+N`
- `#waPanel` bajo softphone: selector **Escribir a**, badge WA, hilo, compose
- Deep-link: `?wa={conversacion_id}`
- Banner: **Modo demo** / **Kommo en vivo**

---

## 11. Limitaciones actuales

| Tema | Estado |
|------|--------|
| Envío texto libre (ventana 24 h) | OK vía API talks |
| Sync entrantes por API | OK (poll 5 s) |
| Webhook tiempo real WABA | Pendiente (Chats API / channel) |
| Plantillas HSM / campañas | Fuera de alcance |
| Media avanzada | Parcial (guarda `media_url` si viene) |
| `channel_id` / `channel_secret` Chats API | No usados aún |

---

## 12. Cómo instalar este paquete en otro CRM gemelo

1. Copiar `codigo/*` a las rutas equivalentes del proyecto.
2. Fusionar `snippets/index_php_rutas_wa.php` en `index.php`.
3. Insertar markup de `snippets/gestionar_cliente_panel_wa.php` bajo softphone.
4. Ejecutar SQL `004_wa_kommo_schema.sql`.
5. Crear `config/kommo.local.php` desde el `.example`.
6. Añadir `config/kommo.local.php` al `.gitignore`.
7. Ngrok + URL en Kommo (opcional si solo usas sync API).
8. Verificar: `php scripts/verificar_kommo_token.php`.

---

## 13. Seguridad

- No commitear `kommo.local.php` ni tokens.
- Regenerar token/clave si se filtraron en chat/capturas.
- Webhook público: secret opcional `X-WA-Webhook-Secret`.
- Asesor solo ve chats de su `asesor_id` (+ `cliente_id`).

---

## 14. Referencias

- [Send message to conversation](https://developers.kommo.com/reference/send-message-to-conversation)
- [Get conversation messages](https://developers.kommo.com/reference/get-conversation-messages)
- [Get talks](https://developers.kommo.com/reference/get-talks)
- [WhatsApp errors / 24h window](https://support.kommo.com/docs/troubleshoot-whatsapp-message-errors)
- [Chats API add-on](https://developers.kommo.com/reference/chats-api-add-on)

---

## 15. Resumen ejecutivo

El CRM ya envía a WhatsApp real (API talks) y refleja el hilo completo sincronizando mensajes desde Kommo cada 5 s. El webhook genérico de Kommo sirve para health-check; **no** es la fuente de mensajes WABA. Para push en tiempo real haría falta canal Chats API + webhook de chats (ver `PREGUNTAS_INTEGRACION.md`).
