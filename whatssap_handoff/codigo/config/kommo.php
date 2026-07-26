<?php
/**
 * Plantilla config/kommo.php — copiar a config/kommo.php del proyecto.
 * Secretos reales van en config/kommo.local.php (gitignored).
 */
$__kommoLocal = __DIR__ . '/kommo.local.php';
if (is_file($__kommoLocal)) {
    require_once $__kommoLocal;
}

if (!defined('KOMMO_SUBDOMAIN')) {
    define('KOMMO_SUBDOMAIN', ''); // ej: tecnologiatysbpocom
}
if (!defined('KOMMO_LONG_LIVED_TOKEN')) {
    define('KOMMO_LONG_LIVED_TOKEN', '');
}
if (!defined('KOMMO_INTEGRATION_ID')) {
    define('KOMMO_INTEGRATION_ID', '');
}
if (!defined('KOMMO_INTEGRATION_SECRET')) {
    define('KOMMO_INTEGRATION_SECRET', '');
}
if (!defined('KOMMO_ACCOUNT_ID')) {
    define('KOMMO_ACCOUNT_ID', '');
}
if (!defined('KOMMO_CHANNEL_ID')) {
    define('KOMMO_CHANNEL_ID', '');
}
if (!defined('KOMMO_CHANNEL_SECRET')) {
    define('KOMMO_CHANNEL_SECRET', '');
}
if (!defined('KOMMO_SCOPE_ID')) {
    define('KOMMO_SCOPE_ID', '');
}
if (!defined('KOMMO_WEBHOOK_URL')) {
    define('KOMMO_WEBHOOK_URL', ''); // https://TU_NGROK/proyecto/wa_webhook.php
}
if (!defined('KOMMO_WABA_PHONE_E164')) {
    define('KOMMO_WABA_PHONE_E164', '');
}
if (!defined('KOMMO_BOT_ID')) {
    define('KOMMO_BOT_ID', '');
}
if (!defined('KOMMO_AMOJO_BOT_ID')) {
    define('KOMMO_AMOJO_BOT_ID', '');
}
if (!defined('KOMMO_INCOMERCIO_WEBHOOK_SECRET')) {
    define('KOMMO_INCOMERCIO_WEBHOOK_SECRET', '');
}
if (!defined('KOMMO_WEBHOOK_SECRET')) {
    define('KOMMO_WEBHOOK_SECRET', KOMMO_INCOMERCIO_WEBHOOK_SECRET);
}
if (!defined('KOMMO_PHONE_COUNTRY_CODE')) {
    define('KOMMO_PHONE_COUNTRY_CODE', '57');
}

function kommoEnabled(): bool {
    return KOMMO_SUBDOMAIN !== '' && KOMMO_LONG_LIVED_TOKEN !== '';
}

function kommoNormalizePhoneE164(?string $raw): ?string {
    if ($raw === null) {
        return null;
    }
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === null || $digits === '') {
        return null;
    }
    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    }
    $cc = KOMMO_PHONE_COUNTRY_CODE;
    if (strpos($digits, $cc) === 0 && strlen($digits) >= 12) {
        return '+' . $digits;
    }
    if (strlen($digits) === 10 && $digits[0] === '3') {
        return '+' . $cc . $digits;
    }
    if (strlen($digits) > 10) {
        $last10 = substr($digits, -10);
        if ($last10[0] === '3') {
            return '+' . $cc . $last10;
        }
    }
    if (strlen($digits) >= 8) {
        return '+' . $cc . $digits;
    }
    return null;
}

function kommoPhoneLast10(?string $e164OrRaw): string {
    $digits = preg_replace('/\D+/', '', (string)$e164OrRaw);
    if ($digits === null || $digits === '') {
        return '';
    }
    return strlen($digits) > 10 ? substr($digits, -10) : $digits;
}

function kommoApiBaseUrl(): string {
    $sub = trim(KOMMO_SUBDOMAIN);
    if ($sub === '') {
        return '';
    }
    return 'https://' . $sub . '.kommo.com';
}
