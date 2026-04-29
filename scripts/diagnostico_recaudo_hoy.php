<?php
/**
 * Diagnóstico de recaudo de hoy (acuerdos).
 *
 * Uso:
 *   php scripts/diagnostico_recaudo_hoy.php
 *   php scripts/diagnostico_recaudo_hoy.php --asesor=1000809496
 *
 * Nota: imprime datos para depurar inconsistencias entre "recaudo hoy" y lo esperado.
 */

require_once __DIR__ . '/../config.php';

function argValue(string $name): ?string {
    global $argv;
    foreach ($argv as $a) {
        if (strpos($a, "--{$name}=") === 0) return substr($a, strlen("--{$name}="));
    }
    return null;
}

$asesor = argValue('asesor'); // asesor_cedula

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Error conectando a DB: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Diagnóstico de fecha/hora de MySQL (causa frecuente de "hoy" = 0 o sumas raras)
$diag = $pdo->query("SELECT CURDATE() AS curdate, NOW() AS now_dt, @@session.time_zone AS tz")->fetch() ?: [];
echo "=== MySQL time ===" . PHP_EOL;
echo "curdate: " . ($diag['curdate'] ?? '') . PHP_EOL;
echo "now:     " . ($diag['now_dt'] ?? '') . PHP_EOL;
echo "tz:      " . ($diag['tz'] ?? '') . PHP_EOL;
echo PHP_EOL;

// 1) Total global de recaudo hoy por fecha_pago
$sqlTotal = "
    SELECT
        COUNT(*) AS n,
        COALESCE(SUM(a.valor_acuerdo), 0) AS suma
    FROM acuerdos a
    WHERE a.fecha_pago = CURDATE()
";
$rowTotal = $pdo->query($sqlTotal)->fetch() ?: ['n' => 0, 'suma' => 0];
echo "=== Acuerdos con fecha_pago = hoy (global) ===" . PHP_EOL;
echo "registros: " . (int)$rowTotal['n'] . PHP_EOL;
echo "suma:      " . (float)$rowTotal['suma'] . PHP_EOL;
echo PHP_EOL;

// 2) Total por asesor (via join a historial_gestiones)
$sqlPorAsesor = "
    SELECT
        hg.asesor_cedula,
        COUNT(*) AS n,
        COALESCE(SUM(a.valor_acuerdo), 0) AS suma
    FROM acuerdos a
    INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
    WHERE a.fecha_pago = CURDATE()
    GROUP BY hg.asesor_cedula
    ORDER BY suma DESC
";
$rowsAsesor = $pdo->query($sqlPorAsesor)->fetchAll() ?: [];
echo "=== Recaudo hoy por asesor (join acuerdos->historial_gestiones) ===" . PHP_EOL;
foreach ($rowsAsesor as $r) {
    $ced = (string)($r['asesor_cedula'] ?? '');
    // Evitar exponer toda la cédula en consola si se comparte el output
    $masked = $ced === '' ? '' : (substr($ced, 0, 3) . str_repeat('*', max(0, strlen($ced) - 5)) . substr($ced, -2));
    echo "- asesor: {$masked}  n=" . (int)$r['n'] . "  suma=" . (float)$r['suma'] . PHP_EOL;
}
echo PHP_EOL;

// 3) Si se pidió un asesor, listar el detalle que compone el total
if ($asesor !== null && $asesor !== '') {
    echo "=== Detalle para asesor_cedula={$asesor} (hoy por fecha_pago) ===" . PHP_EOL;
    $stmt = $pdo->prepare("
        SELECT
            a.id_acuerdo,
            a.gestion_id,
            a.valor_acuerdo,
            a.fecha_pago,
            hg.fecha_creacion AS gestion_fecha_creacion,
            hg.asesor_cedula
        FROM acuerdos a
        INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
        WHERE a.fecha_pago = CURDATE()
          AND hg.asesor_cedula = ?
        ORDER BY a.valor_acuerdo DESC, a.id_acuerdo DESC
        LIMIT 200
    ");
    $stmt->execute([(string)$asesor]);
    $rows = $stmt->fetchAll() ?: [];
    $sum = 0.0;
    foreach ($rows as $r) {
        $sum += (float)($r['valor_acuerdo'] ?? 0);
        echo json_encode([
            'id_acuerdo' => (int)($r['id_acuerdo'] ?? 0),
            'gestion_id' => (int)($r['gestion_id'] ?? 0),
            'valor_acuerdo' => (float)($r['valor_acuerdo'] ?? 0),
            'fecha_pago' => (string)($r['fecha_pago'] ?? ''),
            'gestion_fecha_creacion' => (string)($r['gestion_fecha_creacion'] ?? ''),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
    echo "suma_detalle: {$sum}" . PHP_EOL;
    echo "registros_detalle: " . count($rows) . PHP_EOL;
    echo PHP_EOL;

    // 4) Chequeo: ¿hay valores sospechosos (muy pequeños o muy grandes) que expliquen '87'?
    $stmt2 = $pdo->prepare("
        SELECT
            MIN(a.valor_acuerdo) AS min_val,
            MAX(a.valor_acuerdo) AS max_val,
            AVG(a.valor_acuerdo) AS avg_val
        FROM acuerdos a
        INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
        WHERE a.fecha_pago = CURDATE()
          AND hg.asesor_cedula = ?
    ");
    $stmt2->execute([(string)$asesor]);
    $stats = $stmt2->fetch() ?: [];
    echo "=== Stats valor_acuerdo (asesor, hoy) ===" . PHP_EOL;
    echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

