<?php
/**
 * Mensajes WhatsApp (espejo Kommo).
 */
class WhatsappMensajeModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listByConversacion(int $conversacionId, int $limit = 100, int $afterId = 0): array {
        $limit = max(1, min(500, $limit));
        if ($afterId > 0) {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM wa_mensajes
                 WHERE conversacion_id = ? AND id > ?
                 ORDER BY id ASC LIMIT {$limit}"
            );
            $stmt->execute([$conversacionId, $afterId]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM (
                    SELECT * FROM wa_mensajes WHERE conversacion_id = ?
                    ORDER BY id DESC LIMIT {$limit}
                 ) t ORDER BY id ASC"
            );
            $stmt->execute([$conversacionId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByKommoMessageId(string $kommoMessageId): ?array {
        if ($kommoMessageId === '') {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM wa_mensajes WHERE kommo_message_id = ? LIMIT 1');
        $stmt->execute([$kommoMessageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM wa_mensajes WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO wa_mensajes
                (conversacion_id, direccion, tipo, cuerpo, media_url, media_name, kommo_message_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            (int)$data['conversacion_id'],
            $data['direccion'],
            $data['tipo'] ?? 'text',
            $data['cuerpo'] ?? null,
            $data['media_url'] ?? null,
            $data['media_name'] ?? null,
            $data['kommo_message_id'] ?? null,
            $data['status'] ?? 'pendiente_envio',
            $data['created_at'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare('UPDATE wa_mensajes SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }
}
