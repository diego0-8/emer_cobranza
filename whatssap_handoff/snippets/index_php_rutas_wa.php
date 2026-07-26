<?php
/**
 * Snippets a agregar en index.php del CRM (emer_cobranza).
 * NO es un archivo ejecutable solo: son fragmentos a fusionar.
 */

// ---------------------------------------------------------------------------
// 1) En el array $accionesAPI (evitar Content-Type HTML previo):
// ---------------------------------------------------------------------------
/*
    'wa_webhook_kommo',
    'wa_mis_chats',
    'wa_mensajes',
    'wa_enviar',
    'wa_conversacion_cliente',
    'wa_emparejar',
    'wa_sin_cliente',
    'wa_estado',
*/

// ---------------------------------------------------------------------------
// 2) Require del controller (junto a los otros controllers):
// ---------------------------------------------------------------------------
// require_once __DIR__ . '/controllers/WhatsappController.php';

// ---------------------------------------------------------------------------
// 3) Acciones públicas (sin sesión) — para validación GET/HEAD de Kommo:
// ---------------------------------------------------------------------------
// $accionesPublicas = ['login', 'process_login', 'wa_webhook_kommo'];

// ---------------------------------------------------------------------------
// 4) Router (antes del default del switch principal):
// ---------------------------------------------------------------------------
/*
    case 'wa_webhook_kommo':
        $wa = new WhatsappController($pdo);
        $wa->webhookKommo();
        break;
    case 'wa_estado':
        $wa = new WhatsappController($pdo);
        $wa->estado();
        break;
    case 'wa_mis_chats':
        $wa = new WhatsappController($pdo);
        $wa->misChats();
        break;
    case 'wa_conversacion_cliente':
        $wa = new WhatsappController($pdo);
        $wa->conversacionCliente();
        break;
    case 'wa_mensajes':
        $wa = new WhatsappController($pdo);
        $wa->mensajes();
        break;
    case 'wa_enviar':
        $wa = new WhatsappController($pdo);
        $wa->enviar();
        break;
    case 'wa_sin_cliente':
        $wa = new WhatsappController($pdo);
        $wa->sinCliente();
        break;
    case 'wa_emparejar':
        $wa = new WhatsappController($pdo);
        $wa->emparejar();
        break;
*/
