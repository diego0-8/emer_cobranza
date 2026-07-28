<?php
/**
 * WhatsApp Cloud API (Meta).
 * Los secretos reales deben vivir en meta.local.php.
 */
$__metaLocal = __DIR__ . '/meta.local.php';
if (is_file($__metaLocal)) {
    require_once $__metaLocal;
}

if (!defined('WA_PROVIDER')) {
    define('WA_PROVIDER', 'kommo');
}
if (!defined('META_API_VERSION')) {
    define('META_API_VERSION', 'v23.0');
}
if (!defined('META_WABA_ID')) {
    define('META_WABA_ID', '');
}
if (!defined('META_PHONE_NUMBER_ID')) {
    define('META_PHONE_NUMBER_ID', '');
}
if (!defined('META_ACCESS_TOKEN')) {
    define('META_ACCESS_TOKEN', '');
}
if (!defined('META_APP_SECRET')) {
    define('META_APP_SECRET', '');
}
if (!defined('META_VERIFY_TOKEN')) {
    define('META_VERIFY_TOKEN', '');
}

function metaEnabled(): bool {
    return META_WABA_ID !== ''
        && META_PHONE_NUMBER_ID !== ''
        && META_ACCESS_TOKEN !== '';
}

function waProvider(): string {
    $provider = strtolower(trim((string)WA_PROVIDER));
    return $provider === 'meta' ? 'meta' : 'kommo';
}

function metaGraphBaseUrl(): string {
    return 'https://graph.facebook.com/' . rawurlencode(META_API_VERSION);
}
