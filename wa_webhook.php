<?php
/**
 * Alias limpio del webhook WhatsApp (Meta Cloud API / fallback Kommo).
 * URL: /emer_cobranza/wa_webhook.php
 */
$_GET['action'] = 'wa_webhook';
$_REQUEST['action'] = 'wa_webhook';
require __DIR__ . '/index.php';
