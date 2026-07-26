# Handoff — Integración WhatsApp + Kommo (INCOMERCIO)

> **Actualizado 2026-07-26:** la documentación y el código vigentes están en  
> [`whatssap_handoff/`](./whatssap_handoff/README.md) (`HANDOFF.md` + `PREGUNTAS_INTEGRACION.md` + `codigo/`).  
> Este archivo queda como referencia histórica del diseño inicial.

Fecha: 2026-07-24  
Proyecto: `C:\xampp\htdocs\incomercio`  
Estado actual: **CRM listo en modo local/demo**. Tiempo real con Kommo pendiente de número WABA + token.

---

## 1. Objetivo de negocio

1. El asesor escribe WhatsApp **desde** `views/asesor_gestionar_cliente.php` (ficha completa del cliente + softphone + panel WA).
2. Toda conversación usable debe estar **amarrada a `cliente_id`**.
3. Abrir un chat = siempre:

```text
index.php?action=gestionar_cliente&id={cliente_id}&wa={conversacion_id}
```

4. Los números a los que se escribe son **solo los del perfil del cliente** (`telefono`, `celular2`, `cel3`…).
5. Números desconocidos (no están en `clientes`) van a cola `sin_cliente` hasta que un coordinador/admin los empareje.

---

## 2. Arquitectura

```text
WhatsApp (cliente)
       │
       ▼
    Kommo (WABA + inbox)
       │  webhook HTTPS
       ▼
INCOMERCIO  index.php?action=wa_webhook_kommo
       │
       ▼
BD yeimy  (wa_conversaciones / wa_mensajes)
       │
       ▼
Vista asesor_gestionar_cliente.php
  - burbujas izquierda (máx. 10 + contador)
  - panel WA bajo el softphone
  - selector "Escribir a" (números del perfil)
       │  respuesta del asesor
       ▼
wa_enviar  →  API Kommo (si kommoEnabled)  →  WhatsApp
```

Mientras `kommoEnabled() === false`, el CRM guarda mensajes locales con status `pendiente_envio`.

---

## 3. Archivos del módulo (inventario)

| Archivo | Rol |
|---------|-----|
| `config/kommo.php` | Credenciales + `kommoEnabled()` + normalización E.164 |
| `scripts/sql/wa_kommo_schema.sql` | Schema tablas `wa_*` |
| `models/WhatsappConversacionModel.php` | Conversaciones, match teléfono→cliente, listados |
| `models/WhatsappMensajeModel.php` | Mensajes idempotentes por `kommo_message_id` |
| `controllers/WhatsappController.php` | Webhook, listar, enviar, emparejar |
| `assets/css/whatsapp-panel.css` | UI burbujas + panel |
| `assets/js/whatsapp-panel.js` | Polling, selector de número, deep-link |
| `views/asesor_gestionar_cliente.php` | Integra rail + panel bajo softphone |
| `index.php` | Rutas `wa_*` |
| `.cursor/rules/whatsapp-kommo.mdc` | Regla Cursor del módulo |

---

## 4. Base de datos

### 4.1 Crear tablas (si aún no existen)

```powershell
C:\xampp\mysql\bin\mysql.exe -u root yeimy -e "SOURCE C:/xampp/htdocs/incomercio/scripts/sql/wa_kommo_schema.sql"
C:\xampp\mysql\bin\mysql.exe -u root yeimy -e "SHOW TABLES LIKE 'wa_%';"
```

Tablas esperadas:

- `wa_conversaciones`
- `wa_mensajes`
- `wa_asignaciones`
- `wa_usuarios_map`

### 4.2 Campos clave de `wa_conversaciones`

| Campo | Uso |
|-------|-----|
| `cliente_id` | Amarre obligatorio para UI del asesor |
| `telefono_e164` | Número normalizado (`+57...`) |
| `kommo_talk_id` / `kommo_chat_id` | IDs Kommo |
| `asesor_id` | Dueño del chat en INCOMERCIO |
| `estado` | `abierta` / `cerrada` / `sin_cliente` |
| `wa_activo` | `desconocido` / `si` / `no` (aprendido al enviar) |
| `no_leidos` | Badge en burbuja |

### 4.3 Reglas de match teléfono → cliente

1. Se normaliza a E.164 con `kommoNormalizePhoneE164()` (prefijo CO `57`).
2. Se busca en `clientes.telefono`, `celular2`, `cel3`…`cel9` (últimos 10 dígitos).
3. Si hay match → `estado=abierta`, `cliente_id` seteado; si el cliente tiene `asesor_id`, se asigna.
4. Si no hay match → `estado=sin_cliente` (no abre ficha de gestión).

---

## 5. Endpoints INCOMERCIO

| Action | Método | Auth | Descripción |
|--------|--------|------|-------------|
| `wa_webhook_kommo` | POST | Público (firma) | Recibe mensajes desde Kommo |
| `wa_mis_chats` | GET | asesor+ | Burbujas (limit 10) + total |
| `wa_mensajes` | GET | asesor+ | Hilo de una conversación |
| `wa_enviar` | POST | asesor+ | Enviar texto (local o Kommo) |
| `wa_conversacion_cliente` | GET | asesor+ | Conv del cliente / crea local / cambia número |
| `wa_emparejar` | POST | coord/admin | Une chat `sin_cliente` → `cliente_id` |
| `wa_sin_cliente` | GET | coord/admin | Lista cola sin cliente |
| `wa_estado` | GET | sesión | `kommo_enabled`, teléfono WABA |

Ejemplos:

```text
GET  index.php?action=wa_mis_chats&limit=10
GET  index.php?action=wa_conversacion_cliente&cliente_id=123&telefono=3001234567
GET  index.php?action=wa_mensajes&conversacion_id=5&cliente_id=123
POST index.php?action=wa_enviar
     {"conversacion_id":5,"cliente_id":123,"texto":"Hola"}
POST index.php?action=wa_webhook_kommo
     {"phone":"3001234567","text":"Hola","direction":"in","kommo_message_id":"abc"}
POST index.php?action=wa_emparejar
     {"conversacion_id":9,"cliente_id":123,"asesor_id":4}
```

---

## 6. UI en la vista de gestión

Ubicación: `views/asesor_gestionar_cliente.php`

1. **Rail izquierdo** (`#waBubbleRail`): hasta 10 chats del asesor + burbuja `+N`.
2. Click burbuja → navega a `gestionar_cliente&id=&wa=` (nunca chat suelto).
3. **Panel bajo softphone** (`#waPanel`):
   - Selector **Escribir a** con números del perfil.
   - Indicador `wa_activo` (`?` / `WA` / `X`).
   - Hilo de mensajes + caja de envío.
4. Polling cada 5 s (`whatsapp-panel.js`).

### Importante sobre “¿qué número tiene WhatsApp?”

WhatsApp Cloud API / Kommo **no permiten** consultar de antemano si un número tiene WhatsApp activo.  
Se descubre:

- cuando el cliente escribe primero, o  
- al enviar y recibir error/entrega (`wa_activo` pasa a `si` o `no`).

---

## 7. Qué debes pedir a Kommo / proveedor (checklist)

Antes de producción, el proveedor debe entregar:

1. Cuenta Kommo con **WhatsApp Business** conectado (WABA Meta).
2. **Integración privada** (o OAuth) con token **long-lived** API v4.
3. Scopes suficientes: chats / contacts / leads (y envío de mensajes).
4. Registro de **canal** Chats API (si aplica): `channel_id` + `channel_secret` + bot ids.
5. Confirmación de que pueden mandar webhooks a tu URL **HTTPS pública**.
6. Confirmación del endpoint exacto de **envío** (hoy el CRM intenta `POST /api/v4/talks/{talk_id}/send_message`; ajustar si el proveedor indica otro).
7. (Opcional) mapa usuario Kommo → `usuarios.id` para `wa_usuarios_map`.

---

## 8. Paso a paso: conectar Kommo (cuando tengas token y número)

### Paso 1 — HTTPS público

El webhook debe ser alcanzable desde internet, por ejemplo:

```text
https://TU_DOMINIO/incomercio/index.php?action=wa_webhook_kommo
```

En local XAMPP el webhook **no** recibirá eventos reales de Kommo sin túnel (ngrok/cloudflared) o despliegue.

### Paso 2 — Rellenar `config/kommo.php`

Editar constantes:

| Constante | Qué pegar |
|-----------|-----------|
| `KOMMO_SUBDOMAIN` | `xxx` de `https://xxx.kommo.com` |
| `KOMMO_LONG_LIVED_TOKEN` | Bearer token API v4 |
| `KOMMO_ACCOUNT_ID` | ID cuenta (`GET /api/v4/account`) |
| `KOMMO_CHANNEL_ID` | ID canal WhatsApp |
| `KOMMO_CHANNEL_SECRET` | Secret firma webhooks |
| `KOMMO_SCOPE_ID` | Scope de la URL webhook Kommo |
| `KOMMO_WEBHOOK_URL` | URL HTTPS de arriba |
| `KOMMO_WABA_PHONE_E164` | Número Business `+57...` |
| `KOMMO_BOT_ID` / `KOMMO_AMOJO_BOT_ID` | Si el canal usa bot |
| `KOMMO_INCOMERCIO_WEBHOOK_SECRET` | (Opcional) secret propio; header `X-WA-Webhook-Secret` |
| `KOMMO_PHONE_COUNTRY_CODE` | `57` (Colombia) |

`kommoEnabled()` pasa a `true` solo si hay:

- `KOMMO_SUBDOMAIN`
- `KOMMO_LONG_LIVED_TOKEN`
- `KOMMO_CHANNEL_ID`
- `KOMMO_CHANNEL_SECRET`

### Paso 3 — Registrar webhook en Kommo

En el panel / soporte del canal, configurar la URL:

```text
https://TU_DOMINIO/incomercio/index.php?action=wa_webhook_kommo
```

Eventos mínimos: mensajes entrantes y salientes (y statuses si están).

### Paso 4 — Probar webhook (simulación)

Sin Kommo aún, puedes simular un inbound (sesión no requerida):

```powershell
curl -X POST "http://localhost/incomercio/index.php?action=wa_webhook_kommo" `
  -H "Content-Type: application/json" `
  -d "{\"phone\":\"3001234567\",\"text\":\"Hola prueba\",\"direction\":\"in\",\"kommo_message_id\":\"test-001\"}"
```

- Si el teléfono existe en un cliente → conversación `abierta` con `cliente_id`.
- Si no existe → `sin_cliente`.

### Paso 5 — Probar UI asesor

1. Login como asesor.
2. Abrir `index.php?action=gestionar_cliente&id={ID_CLIENTE}`.
3. Verificar burbujas + panel WA + selector de números del perfil.
4. Enviar mensaje de prueba (quedará `pendiente_envio` si Kommo no está enabled).

### Paso 6 — Emparejar cola sin cliente (coord/admin)

```text
POST index.php?action=wa_emparejar
{"conversacion_id":9,"cliente_id":123}
```

Luego el asesor abre el chat solo vía `gestionar_cliente&id=123&wa=9`.

### Paso 7 — Producción con Kommo vivo

1. Credenciales llenas → banner “Kommo en vivo”.
2. Mensaje real entrante por WhatsApp → aparece en mirror BD → burbuja.
3. Respuesta del asesor → `wa_enviar` → API Kommo → WhatsApp.
4. Si Kommo rechaza número inválido → `wa_activo=no`.
5. Si entrega OK → `wa_activo=si`.

---

## 9. Flujos de asignación de chats

Orden aplicado hoy:

1. Match teléfono → `cliente_id`.
2. Si el cliente tiene `asesor_id` en BD → asignar ese asesor.
3. Si no → conversación abierta sin asesor (o queda para asignación manual).
4. Coordinador puede `wa_emparejar` y/u opcionalmente setear `asesor_id`.
5. (Futuro) mapear `kommo_user_id` con `wa_usuarios_map` si Kommo asigna responsables allá.

---

## 10. Seguridad

- Webhook público: validar `KOMMO_INCOMERCIO_WEBHOOK_SECRET` y/o firma `KOMMO_CHANNEL_SECRET`.
- Resto de endpoints: sesión + rol.
- Asesor solo ve/responde chats con su `asesor_id` y `cliente_id`.
- No loguear tokens ni passwords en `logs/error.log`.
- No commitear `config/kommo.php` con secretos reales a repos públicos.

---

## 11. Prueba local sin Kommo (seed SQL)

```sql
-- Reemplaza ID_CLIENTE e ID_ASESOR por valores reales
INSERT INTO wa_conversaciones
  (cliente_id, telefono_e164, asesor_id, estado, wa_activo, no_leidos, ultimo_mensaje_at, ultimo_preview)
VALUES
  (ID_CLIENTE, '+573001112233', ID_ASESOR, 'abierta', 'desconocido', 1, NOW(), 'Hola demo');

SET @cid = LAST_INSERT_ID();

INSERT INTO wa_mensajes (conversacion_id, direccion, tipo, cuerpo, status)
VALUES (@cid, 'in', 'text', 'Hola demo', 'recibido');
```

Abrir:

```text
index.php?action=gestionar_cliente&id=ID_CLIENTE&wa=@cid
```

---

## 12. Verificación técnica

```powershell
php -l C:\xampp\htdocs\incomercio\config\kommo.php
php -l C:\xampp\htdocs\incomercio\controllers\WhatsappController.php
php -l C:\xampp\htdocs\incomercio\models\WhatsappConversacionModel.php
php -l C:\xampp\htdocs\incomercio\models\WhatsappMensajeModel.php
php -l C:\xampp\htdocs\incomercio\index.php
php -l C:\xampp\htdocs\incomercio\views\asesor_gestionar_cliente.php

C:\xampp\mysql\bin\mysql.exe -u root yeimy -e "SHOW TABLES LIKE 'wa_%';"
```

En el navegador (Ctrl+F5):

- Softphone carga.
- Panel WA visible bajo softphone.
- Selector “Escribir a” lista números del perfil.
- Burbujas cargan desde `wa_mis_chats`.

---

## 13. Limitaciones / fuera de alcance actual

- Conexión Meta/WABA (la hace Kommo/proveedor).
- Plantillas HSM / campañas masivas.
- Media avanzada (audio/imagen) más allá de guardar/mostrar URL.
- Verificación previa “¿tiene WhatsApp?” (no existe en Cloud API oficial).
- App móvil nativa.

---

## 14. Orden recomendado para el siguiente agente/dev

1. Leer este handoff y `config/kommo.php`.
2. Confirmar tablas `wa_*` en `yeimy`.
3. Probar UI en modo demo (sin token).
4. Cuando el proveedor entregue credenciales: llenar `kommo.php` + HTTPS + webhook.
5. Ajustar payload del webhook / endpoint de envío al contrato real de Kommo (si difiere del stub).
6. Probar: inbound real → mirror → burbuja → abrir ficha → responder → entrega WhatsApp.
7. No commit/push de secretos.

---

## 15. Contactos / referencias externas

- Kommo WhatsApp Business: https://www.kommo.com/integrations/whatsapp-business/
- Kommo Talks API: https://developers.kommo.com/reference/get-talks
- Kommo Chat webhooks: https://developers.kommo.com/reference/receiving-chat-webhooks
- Meta: Cloud API no incluye endpoint vigente para “check if number has WhatsApp” antes de enviar.

---

**Resumen:** el CRM ya tiene espejo de chats, UI, amarre a `cliente_id` y selector de números del perfil. Para tiempo real solo falta pegar credenciales Kommo + webhook HTTPS y validar el contrato exacto de envío/recepción con el proveedor.
