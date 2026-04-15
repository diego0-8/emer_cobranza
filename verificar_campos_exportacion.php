<?php
// Script de verificación de columnas requeridas para el CSV del coordinador.
// Ejecuta en CLI: php verificar_campos_exportacion.php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

// Solo CLI
if (php_sapi_name() !== 'cli') {
    echo "Este script se debe ejecutar desde CLI.\n";
    exit(1);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$required = [
    'historial_gestion' => [
        'id',
        'asignacion_id',
        'fecha_gestion',
        'tipo_gestion',
        'resultado',
        'comentarios',
        'forma_contacto',
        'telefono_contacto',
        'numero_obligacion',
        'monto_obligacion',
        'fecha_acuerdo',
        'monto_acuerdo',
    ],
    'asignaciones_clientes' => ['id', 'asesor_id', 'cliente_id'],
    'clientes' => ['id', 'nombre', 'cedula', 'telefono', 'celular2', 'cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'carga_excel_id'],
    'cargas_excel' => ['id', 'nombre_cargue'],
    'usuarios' => ['id', 'nombre_completo'],
    'facturas' => ['cliente_id', 'franja', 'telefono2', 'telefono3'],
    'canales_autorizados_gestion' => ['historial_gestion_id', 'canal_autorizado'],
];

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
} catch (Exception $e) {
    echo "Error conectando a BD: " . $e->getMessage() . "\n";
    exit(1);
}

$missingByTable = [];
$missingTables = [];

foreach ($required as $table => $cols) {
    $stmtTable = $pdo->prepare(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.tables
         WHERE table_schema = ? AND table_name = ?"
    );
    $stmtTable->execute([DB_NAME, $table]);
    $exists = (int)($stmtTable->fetch()['cnt'] ?? 0) > 0;

    if (!$exists) {
        $missingTables[] = $table;
        continue;
    }

    $stmtCols = $pdo->prepare(
        "SELECT column_name
         FROM information_schema.columns
         WHERE table_schema = ? AND table_name = ?"
    );
    $stmtCols->execute([DB_NAME, $table]);
    $existingCols = array_flip(array_column($stmtCols->fetchAll(), 'column_name'));

    $missingCols = [];
    foreach ($cols as $c) {
        if (!isset($existingCols[$c])) {
            $missingCols[] = $c;
        }
    }

    if (!empty($missingCols)) {
        $missingByTable[$table] = $missingCols;
    }
}

if (empty($missingTables) && empty($missingByTable)) {
    echo "OK: Todas las columnas requeridas existen en la BD.\n";
    exit(0);
}

if (!empty($missingTables)) {
    echo "Tablas faltantes:\n";
    foreach ($missingTables as $t) {
        echo "- {$t}\n";
    }
    echo "\n";
}

if (!empty($missingByTable)) {
    echo "Columnas faltantes:\n";
    foreach ($missingByTable as $table => $cols) {
        echo "- {$table}:\n";
        foreach ($cols as $c) {
            echo "  - {$c}\n";
        }
    }
}

exit(0);

