# WhatsApp directo con Meta Cloud API

La integración conserva las rutas y la interfaz actuales del CRM. El transporte se elige con
`WA_PROVIDER=meta|kommo`; no se envía simultáneamente por ambos proveedores.

## 1. Credenciales

En Meta Business Manager / Developers:

1. Confirma que el número está disponible para Cloud API propia y no bloqueado por el BSP de Kommo.
2. Crea o usa una App de tipo Business con el producto WhatsApp.
3. Crea un System User y asigna la app y el WABA.
4. Genera un token con:
   - `whatsapp_business_messaging`
   - `whatsapp_business_management`
5. Obtén `WABA_ID`, `PHONE_NUMBER_ID` y `APP_SECRET`.
6. Copia `config/meta.local.example.php` como `config/meta.local.php`, completa los valores y
   establece `WA_PROVIDER` en `meta`.

`config/meta.local.php` está excluido de Git. No guardes tokens en archivos versionados.

## 2. Verificar acceso

```bash
php scripts/probe_meta_account.php
php scripts/probe_meta_templates.php
```

Ambas consultas deben responder correctamente:

- `GET /{PHONE_NUMBER_ID}`
- `GET /{WABA_ID}/message_templates`

## 3. Registrar webhook

En Meta Developers → WhatsApp → Configuration:

- Callback URL: `https://TU_DOMINIO/emer_cobranza_watt/wa_webhook.php`
- Verify token: el mismo `META_VERIFY_TOKEN`
- Campo suscrito: `messages`

El endpoint:

- responde el challenge GET de Meta;
- valida `X-Hub-Signature-256` con `META_APP_SECRET`;
- recibe mensajes, archivos y estados `sent`, `delivered`, `read`, `failed`.

La URL debe ser HTTPS pública y estable. Verifica la configuración local:

```bash
php scripts/validar_webhook_meta.php https://TU_DOMINIO/emer_cobranza_watt
```

## 4. Plantillas

Coordinador → WhatsApp permite:

- consultar plantillas y su estado Meta;
- crear plantillas Utility y enviarlas a revisión;
- usar únicamente las que Meta marque `APPROVED`.

Endpoints usados:

- `GET /{WABA_ID}/message_templates`
- `POST /{WABA_ID}/message_templates`
- `POST /{PHONE_NUMBER_ID}/messages` con `type=template`

Meta, no el CRM, decide la aprobación.

## 5. Validación real y corte

Primero ejecuta la prueba explícita con un número autorizado:

```bash
php scripts/validar_envio_plantilla_meta.php \
  --to=573001112233 \
  --template=nombre_interno_meta \
  --lang=es \
  --params="Ana|123456" \
  --confirm
```

Después valida desde `gestionar_cliente.php`:

1. enviar plantilla a un número sin conversación previa;
2. responder desde el celular y comprobar la burbuja;
3. enviar texto libre dentro de las 24 horas;
4. comprobar estados y un adjunto;
5. enviar una campaña pequeña.

Para rollback, cambia únicamente `WA_PROVIDER` a `kommo`. No cambies de proveedor mientras haya
requests de campañas en ejecución.

## Reglas operativas

- Fuera de la ventana de 24 horas solo se permite una plantilla aprobada.
- En Meta no existe `talk_id`; la conversación local se identifica por `telefono_e164`.
- El identificador Meta `wamid` se guarda en `external_message_id`.
- Las burbujas leen la BD local; en modo Meta el webhook reemplaza el pull periódico de Kommo.
