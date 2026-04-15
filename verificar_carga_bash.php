<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/GestionModel.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $gestionModel = new GestionModel($pdo);

    echo "Verificacion de carga bash\n";
    echo "===========================\n\n";

    $sqlTablas = "
        SELECT
            (SELECT COUNT(*) FROM historial_gestion) AS total_historial,
            (SELECT COUNT(*) FROM canales_autorizados_gestion) AS total_canales,
            (SELECT COUNT(*) FROM actividades_productos) AS total_actividades
    ";
    $resumen = $pdo->query($sqlTablas)->fetch();

    echo "Totales actuales:\n";
    echo "- historial_gestion: " . (int) $resumen['total_historial'] . "\n";
    echo "- canales_autorizados_gestion: " . (int) $resumen['total_canales'] . "\n";
    echo "- actividades_productos: " . (int) $resumen['total_actividades'] . "\n\n";

    $sqlGestiones = "
        SELECT
            hg.id,
            hg.fecha_gestion,
            hg.tipo_gestion,
            hg.resultado,
            c.cedula,
            c.nombre AS cliente_nombre,
            u.nombre_completo AS asesor_nombre,
            ce.nombre_cargue AS base_nombre,
            hg.numero_obligacion,
            hg.telefono_contacto
        FROM historial_gestion hg
        INNER JOIN asignaciones_clientes ac ON hg.asignacion_id = ac.id
        INNER JOIN clientes c ON ac.cliente_id = c.id
        INNER JOIN usuarios u ON ac.asesor_id = u.id
        LEFT JOIN cargas_excel ce ON c.carga_excel_id = ce.id
        ORDER BY hg.fecha_gestion DESC, hg.id DESC
        LIMIT 10
    ";

    $ultimasGestiones = $pdo->query($sqlGestiones)->fetchAll();

    echo "Ultimas 10 gestiones (deben estar de la mas reciente a la mas antigua):\n";
    foreach ($ultimasGestiones as $gestion) {
        echo "- [" . $gestion['fecha_gestion'] . "] "
            . $gestion['asesor_nombre'] . " | "
            . $gestion['cedula'] . " | "
            . ($gestion['base_nombre'] ?: 'SIN_BASE') . " | "
            . ($gestion['numero_obligacion'] ?: 'SIN_FACTURA') . " | "
            . $gestion['tipo_gestion'] . " / " . ($gestion['resultado'] ?: 'SIN_RESULTADO') . "\n";
    }

    echo "\nChequeo de orden del historial:\n";
    $ordenCorrecto = true;
    $anterior = null;
    foreach ($ultimasGestiones as $gestion) {
        $actual = strtotime($gestion['fecha_gestion']);
        if ($anterior !== null && $actual > $anterior) {
            $ordenCorrecto = false;
            break;
        }
        $anterior = $actual;
    }
    echo $ordenCorrecto
        ? "- OK: el orden es descendente por fecha_gestion.\n"
        : "- ERROR: se detecto un desorden en fecha_gestion.\n";

    echo "\nChequeo de integridad para la vista del asesor:\n";
    if (!empty($ultimasGestiones)) {
        $cedula = $ultimasGestiones[0]['cedula'];
        $stmtCliente = $pdo->prepare("SELECT id FROM clientes WHERE cedula = ? ORDER BY id DESC LIMIT 1");
        $stmtCliente->execute([$cedula]);
        $cliente = $stmtCliente->fetch();

        if ($cliente) {
            $historial = $gestionModel->getGestionByAsesorAndCliente(0, (int) $cliente['id']);
            echo "- Cliente de muestra: " . $cedula . "\n";
            echo "- Registros visibles desde getGestionByAsesorAndCliente(): " . count($historial) . "\n";
            if (!empty($historial)) {
                echo "- Primera fecha visible en historial: " . $historial[0]['fecha_gestion'] . "\n";
            }
        } else {
            echo "- No se encontro cliente de muestra para validar historial.\n";
        }
    } else {
        echo "- No hay gestiones para validar.\n";
    }

    echo "\nVerificacion finalizada.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error en la verificacion: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
