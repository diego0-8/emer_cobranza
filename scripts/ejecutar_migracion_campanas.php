<?php
/**
 * Ejecuta migraciones SQL de campañas (001 + 002).
 * Uso: php scripts/ejecutar_migracion_campanas.php
 */
require_once __DIR__ . '/../config.php';

function ejecutarSqlFile(PDO $pdo, string $path): void {
    if (!is_readable($path)) {
        throw new RuntimeException("No se puede leer: $path");
    }
    $sql = file_get_contents($path);
    $pdo->exec($sql);
}

function contar(PDO $pdo, string $sql): int {
    return (int) $pdo->query($sql)->fetchColumn();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== Migración Campañas ===\n";
    echo "BD: " . DB_NAME . "\n\n";

    $dir = __DIR__ . '/../sql/migrations';
    $files = [
        $dir . '/001_campanas_schema.sql',
        $dir . '/002_campanas_seed_emermedica.sql',
    ];

    foreach ($files as $file) {
        echo "Ejecutando: " . basename($file) . " ... ";
        ejecutarSqlFile($pdo, $file);
        echo "OK\n";
    }

    echo "\n--- Resumen ---\n";
    echo "Campañas: " . contar($pdo, "SELECT COUNT(*) FROM campanas") . "\n";

    $campana = $pdo->query("SELECT id_campana, nombre, estado FROM campanas WHERE nombre = 'Emermedica Cobranza' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($campana) {
        $id = (int) $campana['id_campana'];
        echo "Campaña seed: #{$id} {$campana['nombre']} ({$campana['estado']})\n";
        echo "Coordinadores: " . contar($pdo, "SELECT COUNT(*) FROM campana_coordinadores WHERE campana_id = $id AND estado = 'activo'") . "\n";
        echo "Asesores en campaña: " . contar($pdo, "SELECT COUNT(*) FROM campana_asesores WHERE campana_id = $id AND estado = 'activo'") . "\n";
        echo "Bases vinculadas: " . contar($pdo, "SELECT COUNT(*) FROM base_clientes WHERE campana_id = $id AND estado = 'activo'") . "\n";
        echo "Registros auditoría: " . contar($pdo, "SELECT COUNT(*) FROM auditoria_coordinadores WHERE campana_id = $id") . "\n";
    }

    echo "\nMigración completada.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
