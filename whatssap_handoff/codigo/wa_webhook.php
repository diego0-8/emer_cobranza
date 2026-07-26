<?php
/**
 * Alias limpio del webhook WhatsApp/Kommo (sin query string).
 * URL: /emer_cobranza/wa_webhook.php
 */
$_GET['action'] = 'wa_webhook_kommo';
$_REQUEST['action'] = 'wa_webhook_kommo';
require __DIR__ . '/index.php';
