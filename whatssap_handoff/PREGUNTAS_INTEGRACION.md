# Preguntas para cerrar / endurecer la integración WhatsApp + Kommo

Usar este checklist con el **admin de Kommo**, el **proveedor WhatsApp Business** o el **siguiente desarrollador**.

---

## A. Credenciales y cuenta

1. ¿Cuál es el subdominio exacto de la cuenta? (`https://______.kommo.com`)
2. ¿El token long-lived actual sigue vigente? ¿Fecha de expiración (`exp` del JWT)?
3. ¿Qué plan de Kommo tienen (Trial / Pro / Enterprise) y qué **cuota de Chats API / mensajes externos** queda?
4. ¿Debemos regenerar **clave secreta** y **token** porque se compartieron en capturas/chat?
5. ¿Existe `channel_id` + `channel_secret` de un canal Chats API propio, o solo usan la app **WhatsApp Business** instalada desde el marketplace?

---

## B. WhatsApp Business / Meta

6. ¿Cuál es el número WABA en E.164? (`+57…`) → llenar `KOMMO_WABA_PHONE_E164`.
7. ¿La ventana de servicio de 24 h está abierta para los clientes de prueba?
8. ¿Hay **plantillas** (HSM) aprobadas en Meta para iniciar conversación fuera de ventana?
9. ¿Dónde se envían plantillas desde Kommo (UI / bot / API) y hay endpoint documentado para nuestro CRM?
10. ¿El número de prueba del cliente (`3208748605`, etc.) está opt-in / no bloqueado por calidad?

---

## C. Webhooks (tiempo real)

11. El **WEB HOOKS** del Centro de integraciones: ¿qué eventos exactamente dispara? (¿incluye mensajes de chat WABA?)
12. Confirmado en pruebas: **no** llegó POST de mensajes al CRM. ¿Cuál es la URL/webhook correcta para **mensajes entrantes de WhatsApp**?
13. ¿Hay que registrar webhook en el **canal WhatsApp** / Chats API (no el webhook genérico de leads)?
14. ¿Payload real de un mensaje entrante (JSON de ejemplo) que Kommo enviaría?
15. ¿Firma/HMAC requerida? ¿Header `X-Signature` / secret del canal?
16. ¿Prefieren seguir con **sync por API cada N segundos** (actual) o invertir en webhook push?

---

## D. Envío y talks

17. ¿Se puede **crear** un talk/chat WABA desde API sin que el cliente escriba primero?
18. Si no: ¿el flujo oficial es “cliente escribe → talk existe → CRM responde”?
19. ¿El endpoint `POST /api/v4/talks/{talk_id}/send_message` es el definitivo para su cuenta?
20. Ante HTTP 202: ¿cómo consultamos fallo posterior de Meta (error 3108 ventana, etc.) además de `delivery_status`?
21. ¿Hay rate limits / paquetes de mensajes extras contratados?

---

## E. Mapeo CRM ↔ Kommo

22. ¿Cómo mapear `asesor_cedula` del CRM a usuario Kommo? (`wa_usuarios_map` / `kommo_user_id`)
23. ¿Los contactos Kommo se crean solo por WhatsApp inbound o también deben crearse desde el CRM?
24. ¿Un cliente con varios `tel1…tel10` debe tener un talk por número (como ahora) o un solo chat?
25. ¿Quién empareja `sin_cliente`? ¿UI coord o solo API `wa_emparejar`?

---

## F. Producto / UX

26. ¿El panel debe mostrar media (imagen/audio) o solo texto en v1?
27. ¿Intervalo de poll aceptable (hoy 5 s) vs costo/cuota API?
28. ¿Necesitan cola `sin_cliente` visible en navbar de coordinador?
29. ¿Deep-link desde notificaciones externas a `gestionar_cliente&id=&wa=`?

---

## G. Infraestructura

30. ¿Ngrok free es solo para demo? ¿URL HTTPS de producción definitiva?
31. Al cambiar URL Ngrok: ¿quién actualiza Kommo?
32. ¿Hay ambiente staging separado de `tecnologiatysbpocom`?

---

## H. Respuestas ya conocidas (no preguntar de nuevo)

| Tema | Respuesta actual |
|------|------------------|
| Subdominio | `tecnologiatysbpocom` |
| Envío texto con talk existente | Funciona (202 + delivered) |
| Webhook genérico | Solo valida URL; no inserta chats |
| Sync entrantes | `GET .../talks/{id}/messages` en poll |
| Teléfonos CRM | `clientes.tel1`…`tel10` |
| Vista UI | `gestionar_cliente.php` bajo softphone |
| Alias webhook | `/wa_webhook.php` |

---

## I. Mensaje listo para enviar al proveedor Kommo

> Hola, tenemos integración privada API v4 con scopes `send_external_messages` y `list_external_messages`.  
> El envío con `POST /api/v4/talks/{talk_id}/send_message` funciona y `delivery_status=delivered`.  
> El WEB HOOKS del Centro de integraciones **no** nos entrega POSTs de mensajes WhatsApp Business.  
> ¿Cuál es el mecanismo oficial para recibir mensajes entrantes WABA en tiempo real (webhook de canal / Chats API)?  
> ¿Pueden compartir un ejemplo de payload y cómo obtener `channel_id` / `channel_secret` / `scope_id` si aplica?  
> Mientras tanto sincronizamos con `GET /api/v4/talks/{talk_id}/messages`.
