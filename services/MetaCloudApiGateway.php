<?php
require_once __DIR__ . '/WhatsappGateway.php';
require_once __DIR__ . '/../config/meta.php';

class MetaCloudApiGateway implements WhatsappGateway {
    public function isEnabled(): bool {
        return metaEnabled();
    }

    public function sendText(string $toE164, string $text): array {
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->digits($toE164),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ]);
    }

    public function sendTemplate(
        string $toE164,
        string $templateName,
        string $language,
        array $params = []
    ): array {
        $template = [
            'name' => $templateName,
            'language' => ['code' => $language ?: 'es'],
        ];
        if ($params !== []) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => array_map(static function ($value) {
                    return ['type' => 'text', 'text' => (string)$value];
                }, array_values($params)),
            ]];
        }
        return $this->sendMessage([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->digits($toE164),
            'type' => 'template',
            'template' => $template,
        ]);
    }

    public function listTemplates(): array {
        if (!$this->isEnabled()) {
            return [];
        }
        $response = $this->request(
            'GET',
            '/' . rawurlencode(META_WABA_ID) . '/message_templates?limit=100&fields=id,name,language,status,category,components'
        );
        if (!$response['ok']) {
            return [];
        }
        $out = [];
        foreach (($response['json']['data'] ?? []) as $template) {
            if (!is_array($template)) {
                continue;
            }
            $body = '';
            foreach (($template['components'] ?? []) as $component) {
                if (strtoupper((string)($component['type'] ?? '')) === 'BODY') {
                    $body = (string)($component['text'] ?? '');
                    break;
                }
            }
            $variables = [];
            if (preg_match_all('/\{\{(\d+)\}\}/', $body, $matches)) {
                $variables = array_values(array_unique($matches[1]));
            }
            $out[] = [
                'id' => (string)($template['id'] ?? $template['name'] ?? ''),
                'external_id' => $template['id'] ?? null,
                'name' => (string)($template['name'] ?? ''),
                'language' => (string)($template['language'] ?? 'es'),
                'status' => strtolower((string)($template['status'] ?? '')),
                'type' => 'waba',
                'category' => (string)($template['category'] ?? ''),
                'body' => $body,
                'variables' => $variables,
            ];
        }
        return $out;
    }

    public function createTemplate(array $definition): array {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'error' => 'Meta Cloud API no está configurada'];
        }
        $name = strtolower(trim((string)($definition['name'] ?? '')));
        $name = preg_replace('/[^a-z0-9_]+/', '_', $name) ?: '';
        $body = trim((string)($definition['body'] ?? ''));
        if ($name === '' || $body === '') {
            return ['ok' => false, 'error' => 'Nombre y cuerpo son requeridos'];
        }
        $payload = [
            'name' => $name,
            'language' => (string)($definition['language'] ?? 'es'),
            'category' => strtoupper((string)($definition['category'] ?? 'UTILITY')),
            'components' => [[
                'type' => 'BODY',
                'text' => $body,
            ]],
        ];
        $examples = $definition['examples'] ?? [];
        if (is_array($examples) && $examples !== []) {
            $payload['components'][0]['example'] = [
                'body_text' => [array_values(array_map('strval', $examples))],
            ];
        }
        $response = $this->request(
            'POST',
            '/' . rawurlencode(META_WABA_ID) . '/message_templates',
            $payload
        );
        return $response['ok']
            ? ['ok' => true, 'template' => $response['json']]
            : ['ok' => false, 'error' => $this->errorText($response)];
    }

    public function downloadMedia(string $mediaId): array {
        $meta = $this->request('GET', '/' . rawurlencode($mediaId));
        $url = (string)($meta['json']['url'] ?? '');
        if (!$meta['ok'] || $url === '') {
            return ['ok' => false, 'error' => $this->errorText($meta)];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . META_ACCESS_TOKEN],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => $error ?: "Meta media HTTP {$code}"];
        }
        return ['ok' => true, 'body' => $body, 'content_type' => $contentType];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        if (META_APP_SECRET === '') {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, META_APP_SECRET);
        return $signature !== '' && hash_equals($expected, $signature);
    }

    public function accountProbe(): array {
        $phone = $this->request(
            'GET',
            '/' . rawurlencode(META_PHONE_NUMBER_ID) . '?fields=id,display_phone_number,verified_name,quality_rating'
        );
        $templates = $this->request(
            'GET',
            '/' . rawurlencode(META_WABA_ID) . '/message_templates?limit=1'
        );
        return [
            'ok' => $phone['ok'] && $templates['ok'],
            'phone' => $phone,
            'templates' => $templates,
        ];
    }

    private function sendMessage(array $payload): array {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'error' => 'Meta Cloud API no está configurada'];
        }
        $response = $this->request(
            'POST',
            '/' . rawurlencode(META_PHONE_NUMBER_ID) . '/messages',
            $payload
        );
        if (!$response['ok']) {
            $error = $this->errorText($response);
            return [
                'ok' => false,
                'error' => $error,
                'invalid_number' => stripos($error, 'phone number') !== false
                    || stripos($error, 'recipient') !== false,
            ];
        }
        return [
            'ok' => true,
            'external_message_id' => (string)($response['json']['messages'][0]['id'] ?? ''),
        ];
    }

    /** @return array{ok:bool,code:int,body:string,json:array} */
    private function request(string $method, string $path, ?array $payload = null): array {
        $url = metaGraphBaseUrl() . $path;
        $headers = [
            'Authorization: Bearer ' . META_ACCESS_TOKEN,
            'Accept: application/json',
        ];
        $ch = curl_init($url);
        $options = [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($payload !== null) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $options[CURLOPT_POSTFIELDS] = $json;
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $body = $body === false ? '' : (string)$body;
        $decoded = json_decode($body, true);
        $json = is_array($decoded) ? $decoded : [];
        if ($body === '' && $curlError !== '') {
            $json = ['error' => ['message' => $curlError]];
        }
        return [
            'ok' => $code >= 200 && $code < 300,
            'code' => $code,
            'body' => $body,
            'json' => $json,
        ];
    }

    private function errorText(array $response): string {
        $message = (string)($response['json']['error']['message'] ?? $response['body'] ?? '');
        $code = (int)($response['code'] ?? 0);
        return "Meta HTTP {$code}: " . ($message !== '' ? $message : 'Error desconocido');
    }

    private function digits(string $phone): string {
        return (string)preg_replace('/\D+/', '', $phone);
    }
}
