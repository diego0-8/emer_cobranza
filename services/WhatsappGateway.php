<?php

interface WhatsappGateway {
    public function isEnabled(): bool;

    /** @return array{ok:bool,external_message_id?:string,error?:string,invalid_number?:bool} */
    public function sendText(string $toE164, string $text): array;

    /** @return array{ok:bool,external_message_id?:string,error?:string,invalid_number?:bool} */
    public function sendTemplate(
        string $toE164,
        string $templateName,
        string $language,
        array $params = []
    ): array;

    public function listTemplates(): array;

    public function createTemplate(array $definition): array;

    /** @return array{ok:bool,body?:string,content_type?:string,error?:string} */
    public function downloadMedia(string $mediaId): array;
}
