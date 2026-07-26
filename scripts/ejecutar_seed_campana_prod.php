<?php
/**
 * Asegura esquema de campañas + seed producción:
 * - Crea campaña "Emermedica Cobranza"
 * - Asigna TODOS los coordinadores activos
 * - Asigna TODOS los asesores activos
 * - Vincula TODAS las bases activas
 * - Desactiva asignaciones_cordinador legacy
 *
 * Uso: php scripts/ejecutar_seed_campana_prod.php
 */
require_once __DIR__ . '/../config.php';

function runSqlFile(PDO $pdo, string $path): void {
    if (!is_readable($path)) {
        throw new RuntimeException("No se puede leer: $path");
    }
    $sql = file_get_contents($path);
    // Quitar comentarios de línea -- al inicio (opcional; PDO exec acepta multi-statement en MySQL)
    $pdo->exec($sql);
}

function q(PDO $pdo, string $sql) {
    return $pdo->query($sql)->fetchColumn();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== Seed Campaña Producción ===\n";
    echo "BD: " . DB_NAME . " @ " . DB_HOST . "\n\n";

    $dir = __DIR__ . '/../sql/migrations';

    echo "1) Esquema (001)... ";
    runSqlFile($pdo, $dir . '/001_campanas_schema.sql');
    echo "OK\n";

    echo "2) Seed todos (003)... ";
    runSqlFile($pdo, $dir . '/003_campanas_seed_todos_prod.sql');
    echo "OK\n\n";

    $campana = $pdo->query("SELECT * FROM campanas WHERE nombre = 'Emermedica Cobranza' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$campana) {
        throw new RuntimeException('Campaña no creada');
    }
    $id = (int)$campana['id_campana'];

    echo "--- Resumen ---\n";
    echo "Campaña: #{$id} {$campana['nombre']} ({$campana['estado']})\n";
    echo "Coordinadores activos en campaña: " . q($pdo, "SELECT COUNT(*) FROM campana_coordinadores WHERE campana_id=$id AND estado='activo'") . "\n";
    echo "Asesores activos en campaña: " . q($pdo, "SELECT COUNT(*) FROM campana_asesores WHERE campana_id=$id AND estado='activo'") . "\n";
    echo "Bases activas vinculadas: " . q($pdo, "SELECT COUNT(*) FROM base_clientes WHERE campana_id=$id AND estado='activo'") . "\n";
    echo "Legacy asignaciones_cordinador activas: " . q($pdo, "SELECT COUNT(*) FROM asignaciones_cordinador WHERE estado='activo'") . "\n";

    echo "\nCoordinadores:\n";
    $coords = $pdo->query("
        SELECT u.cedula, u.usuario, u.nombre
        FROM campana_coordinadores cc
        JOIN usuarios u ON u.cedula = cc.coordinador_cedula
        WHERE cc.campana_id = $id AND cc.estado = 'activo'
        ORDER BY u.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($coords as $c) {
        echo "  - {$c['usuario']} | {$c['nombre']} ({$c['cedula']})\n";
    }

    echo "\nAsesores (muestra):\n";
    $ases = $pdo->query("
        SELECT u.cedula, u.usuario, u.nombre
        FROM campana_asesores ca
        JOIN usuarios u ON u.cedula = ca.asesor_cedula
        WHERE ca.campana_id = $id AND ca.estado = 'activo'
        ORDER BY u.nombre
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ases as $a) {
        echo "  - {$a['usuario']} | {$a['nombre']}\n";
    }

    echo "\nSeed completado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
