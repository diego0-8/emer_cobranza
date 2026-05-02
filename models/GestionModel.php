<?php
/**
 * Modelo de gestiones adaptado al dump `emermedica_cobranza.sql`.
 *
 * Tablas relevantes:
 * - `historial_gestiones`
 * - `acuerdos`
 * - `obligaciones`
 * - `clientes`
 * - `base_clientes`
 * - `usuarios`
 */
class GestionModel {
    private $pdo;
    private ?bool $hasNormCols = null;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function hasNormColumns(): bool {
        if ($this->hasNormCols !== null) return $this->hasNormCols;
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) AS c
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'historial_gestiones'
                  AND COLUMN_NAME IN ('tipo_contacto_norm','resultado_contacto_norm','forma_contacto_norm','razon_especifica_norm')
            ");
            $stmt->execute();
            $c = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            $this->hasNormCols = ($c >= 4);
        } catch (Throwable $e) {
            $this->hasNormCols = false;
        }
        return $this->hasNormCols;
    }

    private function norm(string $v): string {
        $v = trim($v);
        if ($v === '') return '';
        $v = mb_strtolower($v, 'UTF-8');
        $v = str_replace([' ', '-', '/'], '_', $v);
        $v = preg_replace('/_+/', '_', $v);
        return trim($v, '_');
    }

    private function rangoFechasPeriodo($periodo): array {
        $periodo = strtolower(trim((string)$periodo));
        $hoy = new DateTime('today');

        if (in_array($periodo, ['dia', 'hoy'], true)) {
            $inicio = (clone $hoy)->format('Y-m-d 00:00:00');
            $fin = (clone $hoy)->format('Y-m-d 23:59:59');
            return [$inicio, $fin];
        }

        if (in_array($periodo, ['semana', '7dias', '7_dias'], true)) {
            $inicioDt = (clone $hoy)->modify('-6 days');
            $inicio = $inicioDt->format('Y-m-d 00:00:00');
            $fin = (clone $hoy)->format('Y-m-d 23:59:59');
            return [$inicio, $fin];
        }

        if (in_array($periodo, ['mes', 'mensual'], true)) {
            $inicioDt = new DateTime(date('Y-m-01 00:00:00'));
            $finDt = new DateTime(date('Y-m-t 23:59:59'));
            return [$inicioDt->format('Y-m-d H:i:s'), $finDt->format('Y-m-d H:i:s')];
        }

        // Default: día
        $inicio = (clone $hoy)->format('Y-m-d 00:00:00');
        $fin = (clone $hoy)->format('Y-m-d 23:59:59');
        return [$inicio, $fin];
    }

    private function limpiarObservacionesLegacy($texto) {
        $texto = (string)$texto;
        if ($texto === '') return $texto;

        $lineas = preg_split("/\\r\\n|\\r|\\n/", $texto);
        if (!is_array($lineas)) return $texto;

        $filtradas = [];
        foreach ($lineas as $linea) {
            $trim = ltrim((string)$linea);
            if ($trim === '') {
                $filtradas[] = $linea;
                continue;
            }
            // Ocultar metadatos del migrador: "legacy_xxx: valor"
            if (stripos($trim, 'legacy_') === 0) {
                continue;
            }
            $filtradas[] = $linea;
        }

        // Limpiar líneas vacías repetidas al final
        while (!empty($filtradas) && trim((string)end($filtradas)) === '') {
            array_pop($filtradas);
        }

        return implode("\n", $filtradas);
    }

    public function crearGestion($data) {
        $asesorCedula = (string)($data['asesor_cedula'] ?? ($data['asesor_id'] ?? ''));
        $clienteId = (int)($data['cliente_id'] ?? 0);
        $obligacionId = (int)($data['obligacion_id'] ?? 0);
        $tipoContacto = trim((string)($data['tipo_contacto'] ?? ($data['tipo_contacto_arbol'] ?? '')));
        $resultadoContacto = trim((string)($data['resultado_contacto'] ?? ($data['resultado'] ?? '')));
        $razonEspecifica = trim((string)($data['razon_especifica'] ?? ''));
        $formaContacto = trim((string)($data['forma_contacto'] ?? ''));
        $telefonoContacto = trim((string)($data['telefono_contacto'] ?? ''));
        $fechaCreacion = trim((string)($data['fecha_creacion'] ?? ($data['fecha_gestion'] ?? '')));

        // Compatibilidad con payload legacy: `tipo_gestion = nivel1|nivel2`
        if ($tipoContacto === '' && !empty($data['tipo_gestion'])) {
            $legacyTipo = (string)$data['tipo_gestion'];
            if (strpos($legacyTipo, '|') !== false) {
                [$nivel1, $nivel2] = array_pad(explode('|', $legacyTipo, 2), 2, '');
                $tipoContacto = trim($nivel1);
                if ($resultadoContacto === '') $resultadoContacto = trim($nivel2);
            } else {
                $tipoContacto = $legacyTipo;
            }
        }

        if ($clienteId === 0 && !empty($data['cedula_cliente']) && !empty($data['base_id'])) {
            $stmt = $this->pdo->prepare("SELECT id_cliente FROM clientes WHERE cedula = ? AND base_id = ? LIMIT 1");
            $stmt->execute([(string)$data['cedula_cliente'], (int)$data['base_id']]);
            $clienteId = (int)($stmt->fetch(PDO::FETCH_ASSOC)['id_cliente'] ?? 0);
        }

        if ($obligacionId === 0 && !empty($data['numero_obligacion']) && $clienteId) {
            $stmt = $this->pdo->prepare("SELECT id_obligacion FROM obligaciones WHERE numero_factura = ? AND cliente_id = ? LIMIT 1");
            $stmt->execute([(string)$data['numero_obligacion'], $clienteId]);
            $obligacionId = (int)($stmt->fetch(PDO::FETCH_ASSOC)['id_obligacion'] ?? 0);
        }

        if ($asesorCedula === '' || $clienteId <= 0 || $obligacionId <= 0) {
            throw new Exception('No fue posible crear la gestión: faltan asesor/cliente/obligación.');
        }

        if ($tipoContacto === '' || $resultadoContacto === '') {
            throw new Exception('No fue posible crear la gestión: faltan tipo_contacto/resultado_contacto.');
        }

        if ($razonEspecifica === '') {
            $razonEspecifica = '-';
        }

        $telefonoContacto = trim($telefonoContacto);
        if ($telefonoContacto === '') {
            $telefonoContacto = '0000000000';
        }
        if (strlen($telefonoContacto) > 10) {
            $telefonoContacto = substr($telefonoContacto, -10);
        }

        $formaContacto = trim($formaContacto);
        if ($formaContacto === '') {
            $formaContacto = 'llamada';
        }
        if (strlen($formaContacto) > 10) {
            $formaContacto = substr($formaContacto, 0, 10);
        }

        $trunc100 = function ($s) {
            if (function_exists('mb_substr')) {
                return mb_substr($s, 0, 100, 'UTF-8');
            }
            return substr($s, 0, 100);
        };
        $tipoContacto = $trunc100($tipoContacto);
        $resultadoContacto = $trunc100($resultadoContacto);
        $razonEspecifica = $trunc100($razonEspecifica);

        // Validar/normalizar fecha (si se envía). Si es inválida, dejar que la BD use CURRENT_TIMESTAMP.
        if ($fechaCreacion !== '') {
            $ts = strtotime($fechaCreacion);
            if ($ts === false) {
                $fechaCreacion = '';
            } else {
                $fechaCreacion = date('Y-m-d H:i:s', $ts);
            }
        }

        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            if ($fechaCreacion !== '') {
                if ($this->hasNormColumns()) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO historial_gestiones (
                            asesor_cedula, cliente_id, obligacion_id,
                            telefono_contacto, forma_contacto, tipo_contacto,
                            resultado_contacto, razon_especifica, observaciones,
                            forma_contacto_norm, tipo_contacto_norm, resultado_contacto_norm, razon_especifica_norm,
                            llamada_telefonica, email, sms, correo_fisico, whatsap,
                            fecha_creacion, duracion_segundos
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'no', 'no', 'no', 'no', 'no', ?, ?)
                    ");
                } else {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO historial_gestiones (
                            asesor_cedula, cliente_id, obligacion_id,
                            telefono_contacto, forma_contacto, tipo_contacto,
                            resultado_contacto, razon_especifica, observaciones,
                            llamada_telefonica, email, sms, correo_fisico, whatsap,
                            fecha_creacion, duracion_segundos
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'no', 'no', 'no', 'no', 'no', ?, ?)
                    ");
                }

                $params = [
                    $asesorCedula,
                    $clienteId,
                    $obligacionId,
                    $telefonoContacto,
                    $formaContacto,
                    $tipoContacto,
                    $resultadoContacto,
                    $razonEspecifica,
                    (string)($data['comentarios'] ?? ($data['observaciones'] ?? '')),
                ];
                if ($this->hasNormColumns()) {
                    $params[] = $this->norm($formaContacto);
                    $params[] = $this->norm($tipoContacto);
                    $params[] = $this->norm($resultadoContacto);
                    $params[] = $this->norm($razonEspecifica);
                }
                $params[] = $fechaCreacion;
                $params[] = (int)($data['duracion_llamada'] ?? ($data['duracion_segundos'] ?? 0));
                $stmt->execute($params);
            } else {
                if ($this->hasNormColumns()) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO historial_gestiones (
                            asesor_cedula, cliente_id, obligacion_id,
                            telefono_contacto, forma_contacto, tipo_contacto,
                            resultado_contacto, razon_especifica, observaciones,
                            forma_contacto_norm, tipo_contacto_norm, resultado_contacto_norm, razon_especifica_norm,
                            llamada_telefonica, email, sms, correo_fisico, whatsap,
                            duracion_segundos
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'no', 'no', 'no', 'no', 'no', ?)
                    ");
                } else {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO historial_gestiones (
                            asesor_cedula, cliente_id, obligacion_id,
                            telefono_contacto, forma_contacto, tipo_contacto,
                            resultado_contacto, razon_especifica, observaciones,
                            llamada_telefonica, email, sms, correo_fisico, whatsap,
                            duracion_segundos
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'no', 'no', 'no', 'no', 'no', ?)
                    ");
                }

                $params = [
                    $asesorCedula,
                    $clienteId,
                    $obligacionId,
                    $telefonoContacto,
                    $formaContacto,
                    $tipoContacto,
                    $resultadoContacto,
                    $razonEspecifica,
                    (string)($data['comentarios'] ?? ($data['observaciones'] ?? '')),
                ];
                if ($this->hasNormColumns()) {
                    $params[] = $this->norm($formaContacto);
                    $params[] = $this->norm($tipoContacto);
                    $params[] = $this->norm($resultadoContacto);
                    $params[] = $this->norm($razonEspecifica);
                }
                $params[] = (int)($data['duracion_llamada'] ?? ($data['duracion_segundos'] ?? 0));
                $stmt->execute($params);
            }

            $gestionId = (int)$this->pdo->lastInsertId();

            // Marcar cliente como gestionado dentro de cualquier tarea activa del asesor.
            try {
                require_once __DIR__ . '/TareaModel.php';
                (new TareaModel($this->pdo))->marcarClienteGestionadoEnTareas($asesorCedula, $clienteId);
            } catch (Throwable $e) {}

            if (!empty($data['monto_acuerdo']) || !empty($data['fecha_acuerdo'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO acuerdos (gestion_id, tipo_acuerdo, valor_acuerdo, fecha_pago)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $gestionId,
                    'total',
                    (float)($data['monto_acuerdo'] ?? 0),
                    $data['fecha_acuerdo'] ? (string)$data['fecha_acuerdo'] : null
                ]);
            }

            if ($ownTransaction) {
                $this->pdo->commit();
            }
            return $gestionId;
        } catch (Throwable $e) {
            if ($ownTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function guardarCanalesAutorizados($historialGestionId, $canales) {
        $flags = [
            'llamada_telefonica' => 'no',
            'email' => 'no',
            'sms' => 'no',
            'correo_fisico' => 'no',
            'whatsap' => 'no',
        ];

        foreach ((array)$canales as $canal) {
            $c = strtolower(trim((string)$canal));
            if (in_array($c, ['llamada', 'llamada_telefonica', 'telefono', 'telefonica'], true)) $flags['llamada_telefonica'] = 'si';
            if (in_array($c, ['correo', 'correo_electronico', 'email'], true)) $flags['email'] = 'si';
            if ($c === 'sms') $flags['sms'] = 'si';
            if ($c === 'correo_fisico') $flags['correo_fisico'] = 'si';
            if (in_array($c, ['whatsapp', 'wa'], true)) $flags['whatsap'] = 'si';
        }

        $stmt = $this->pdo->prepare("
            UPDATE historial_gestiones
            SET llamada_telefonica = ?, email = ?, sms = ?, correo_fisico = ?, whatsap = ?
            WHERE id_gestion = ?
        ");
        return $stmt->execute([
            $flags['llamada_telefonica'],
            $flags['email'],
            $flags['sms'],
            $flags['correo_fisico'],
            $flags['whatsap'],
            (int)$historialGestionId
        ]);
    }

    public function getGestionByAsesorAndCliente($asesorId, $clienteId) {
        $stmt = $this->pdo->prepare("
            SELECT hg.*,
                   u.nombre as asesor_nombre,
                   c.base_id as carga_excel_id,
                   b.nombre as nombre_base,
                   o.numero_factura as numero_obligacion,
                   o.saldo as monto_obligacion,
                   o.numero_contrato as producto_gestionado,
                   a.tipo_acuerdo,
                   a.valor_acuerdo as monto_acuerdo,
                   a.fecha_pago as fecha_acuerdo
            FROM historial_gestiones hg
            LEFT JOIN acuerdos a ON a.gestion_id = hg.id_gestion
            JOIN usuarios u ON hg.asesor_cedula = u.cedula
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            JOIN base_clientes b ON c.base_id = b.id_base
            JOIN obligaciones o ON hg.obligacion_id = o.id_obligacion
            WHERE hg.cliente_id = ?
            ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC
        ");
        $stmt->execute([(int)$clienteId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['id'] = $r['id_gestion'];
            $r['fecha_gestion'] = $r['fecha_creacion'];
            $r['tipo_gestion'] = $r['tipo_contacto'];
            $r['resultado'] = $r['resultado_contacto'];
            $r['observaciones'] = $this->limpiarObservacionesLegacy($r['observaciones'] ?? '');
            $r['comentarios'] = $r['observaciones'];

            // Campos que la vista usa para el árbol (compatibilidad):
            $r['tipo_contacto_arbol_codigo'] = $r['tipo_contacto'] ?? null;
            $r['resultado_contacto_codigo'] = $r['resultado_contacto'] ?? null;
            $r['razon_especifica_codigo'] = $r['razon_especifica'] ?? null;

            $canales = [];
            if (($r['llamada_telefonica'] ?? 'no') === 'si') $canales[] = 'llamada';
            if (($r['email'] ?? 'no') === 'si') $canales[] = 'correo_electronico';
            if (($r['sms'] ?? 'no') === 'si') $canales[] = 'sms';
            if (($r['correo_fisico'] ?? 'no') === 'si') $canales[] = 'correo_fisico';
            if (($r['whatsap'] ?? 'no') === 'si') $canales[] = 'whatsapp';
            $r['canales_autorizados'] = $canales;
        }

        return $rows;
    }

    public function getGestionById(int $gestionId): ?array {
        if ($gestionId <= 0) return null;

        $stmt = $this->pdo->prepare("
            SELECT hg.*,
                   u.nombre as asesor_nombre,
                   c.cedula as cliente_cedula,
                   c.nombre as cliente_nombre,
                   c.base_id as carga_excel_id,
                   b.nombre as nombre_base,
                   o.numero_factura as numero_obligacion,
                   o.saldo as monto_obligacion,
                   o.numero_contrato as producto_gestionado,
                   a.tipo_acuerdo,
                   a.valor_acuerdo as monto_acuerdo,
                   a.fecha_pago as fecha_acuerdo
            FROM historial_gestiones hg
            LEFT JOIN acuerdos a ON a.gestion_id = hg.id_gestion
            JOIN usuarios u ON hg.asesor_cedula = u.cedula
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            JOIN base_clientes b ON c.base_id = b.id_base
            JOIN obligaciones o ON hg.obligacion_id = o.id_obligacion
            WHERE hg.id_gestion = ?
            ORDER BY a.id_acuerdos DESC
            LIMIT 1
        ");
        $stmt->execute([(int)$gestionId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;

        $r['id'] = $r['id_gestion'];
        $r['fecha_gestion'] = $r['fecha_creacion'];
        $r['tipo_gestion'] = $r['tipo_contacto'];
        $r['resultado'] = $r['resultado_contacto'];
        $r['observaciones'] = $this->limpiarObservacionesLegacy($r['observaciones'] ?? '');
        $r['comentarios'] = $r['observaciones'];

        $canales = [];
        if (($r['llamada_telefonica'] ?? 'no') === 'si') $canales[] = 'llamada';
        if (($r['email'] ?? 'no') === 'si') $canales[] = 'correo_electronico';
        if (($r['sms'] ?? 'no') === 'si') $canales[] = 'sms';
        if (($r['correo_fisico'] ?? 'no') === 'si') $canales[] = 'correo_fisico';
        if (($r['whatsap'] ?? 'no') === 'si') $canales[] = 'whatsapp';
        $r['canales_autorizados'] = $canales;

        return $r;
    }

    /**
     * Historial completo por cédula (todas las bases / todos los asesores).
     * Útil cuando el mismo documento existe en varias bases (clientes.id_cliente distinto por base).
     */
    public function getGestionesByCedula($cedula) {
        $cedula = trim((string)$cedula);
        if ($cedula === '') return [];

        $stmt = $this->pdo->prepare("
            SELECT hg.*,
                   u.nombre as asesor_nombre,
                   c.base_id as carga_excel_id,
                   b.nombre as nombre_base,
                   o.numero_factura as numero_obligacion,
                   o.saldo as monto_obligacion,
                   o.numero_contrato as producto_gestionado,
                   a.tipo_acuerdo,
                   a.valor_acuerdo as monto_acuerdo,
                   a.fecha_pago as fecha_acuerdo
            FROM historial_gestiones hg
            LEFT JOIN acuerdos a ON a.gestion_id = hg.id_gestion
            JOIN usuarios u ON hg.asesor_cedula = u.cedula
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            JOIN base_clientes b ON c.base_id = b.id_base
            JOIN obligaciones o ON hg.obligacion_id = o.id_obligacion
            WHERE c.cedula = ?
            ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC
        ");
        $stmt->execute([$cedula]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $r['id'] = $r['id_gestion'];
            $r['fecha_gestion'] = $r['fecha_creacion'];
            $r['tipo_gestion'] = $r['tipo_contacto'];
            $r['resultado'] = $r['resultado_contacto'];
            $r['observaciones'] = $this->limpiarObservacionesLegacy($r['observaciones'] ?? '');
            $r['comentarios'] = $r['observaciones'];

            // Campos que la vista usa para el árbol (compatibilidad):
            $r['tipo_contacto_arbol_codigo'] = $r['tipo_contacto'] ?? null;
            $r['resultado_contacto_codigo'] = $r['resultado_contacto'] ?? null;
            $r['razon_especifica_codigo'] = $r['razon_especifica'] ?? null;

            $canales = [];
            if (($r['llamada_telefonica'] ?? 'no') === 'si') $canales[] = 'llamada';
            if (($r['email'] ?? 'no') === 'si') $canales[] = 'correo_electronico';
            if (($r['sms'] ?? 'no') === 'si') $canales[] = 'sms';
            if (($r['correo_fisico'] ?? 'no') === 'si') $canales[] = 'correo_fisico';
            if (($r['whatsap'] ?? 'no') === 'si') $canales[] = 'whatsapp';
            $r['canales_autorizados'] = $canales;
        }

        return $rows;
    }

    public function getGestionByAsesor($asesorId) {
        $stmt = $this->pdo->prepare("
            SELECT hg.*, c.nombre as cliente_nombre, c.id_cliente as cliente_id
            FROM historial_gestiones hg
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            WHERE hg.asesor_cedula = ?
            ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC
        ");
        $stmt->execute([(string)$asesorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['id'] = $r['id_gestion'];
            $r['fecha_gestion'] = $r['fecha_creacion'];
        }
        return $rows;
    }

    public function getGestionesHoy($asesorId) {
        // #region agent log d54ef5 gestiones hoy
        try {
            @file_put_contents(__DIR__ . '/../debug-d54ef5.log', json_encode([
                'sessionId' => 'd54ef5',
                'runId' => 'pre',
                'hypothesisId' => 'H1',
                'location' => 'models/GestionModel.php:getGestionesHoy:entry',
                'message' => 'enter',
                'data' => [
                    'asesorIdLen' => strlen((string)$asesorId),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion

        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM historial_gestiones WHERE asesor_cedula = ? AND DATE(fecha_creacion) = CURDATE()");
        $stmt->execute([(string)$asesorId]);
        $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // #region agent log d54ef5 gestiones hoy diag db time
        try {
            $diag = $this->pdo->query("SELECT CURDATE() AS curdate, NOW() AS now_dt, @@session.time_zone AS tz")->fetch(PDO::FETCH_ASSOC) ?: [];
            @file_put_contents(__DIR__ . '/../debug-d54ef5.log', json_encode([
                'sessionId' => 'd54ef5',
                'runId' => 'pre',
                'hypothesisId' => 'H1',
                'location' => 'models/GestionModel.php:getGestionesHoy:result',
                'message' => 'count',
                'data' => [
                    'total' => $total,
                    'dbCurdate' => (string)($diag['curdate'] ?? ''),
                    'dbNow' => (string)($diag['now_dt'] ?? ''),
                    'dbTz' => (string)($diag['tz'] ?? ''),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion

        return $total;
    }

    /**
     * Cantidad de gestiones del asesor en el mes calendario actual (historial_gestiones.fecha_creacion).
     */
    public function getGestionesMesActual($asesorId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND YEAR(fecha_creacion) = YEAR(CURDATE())
              AND MONTH(fecha_creacion) = MONTH(CURDATE())
        ");
        $stmt->execute([(string)$asesorId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getContactosEfectivosHoy($asesorId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM historial_gestiones WHERE asesor_cedula = ? AND DATE(fecha_creacion) = CURDATE() AND resultado_contacto <> ''");
        $stmt->execute([(string)$asesorId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getAcuerdosHoy($asesorId) {
        // #region agent log d54ef5 acuerdos hoy
        try {
            @file_put_contents(__DIR__ . '/../debug-d54ef5.log', json_encode([
                'sessionId' => 'd54ef5',
                'runId' => 'pre',
                'hypothesisId' => 'H1',
                'location' => 'models/GestionModel.php:getAcuerdosHoy:entry',
                'message' => 'enter',
                'data' => [
                    'asesorIdLen' => strlen((string)$asesorId),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM acuerdos a
            JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ? AND DATE(hg.fecha_creacion) = CURDATE()
        ");
        $stmt->execute([(string)$asesorId]);
        $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // #region agent log d54ef5 acuerdos hoy result
        try {
            @file_put_contents(__DIR__ . '/../debug-d54ef5.log', json_encode([
                'sessionId' => 'd54ef5',
                'runId' => 'pre',
                'hypothesisId' => 'H1',
                'location' => 'models/GestionModel.php:getAcuerdosHoy:result',
                'message' => 'count',
                'data' => [
                    'total' => $total,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion

        return $total;
    }

    /**
     * Suma valor_acuerdo de los acuerdos del asesor creados hoy.
     *
     * Nota: en esta app "Acuerdos hoy" se calcula por DATE(hg.fecha_creacion)=CURDATE().
     * Para mantener consistencia del dashboard, "Recaudo hoy" usa el mismo criterio.
     */
    public function getSumaValorAcuerdosAsesorDia($asesorCedula): float {
        // #region agent log b7eaa7 getSumaValorAcuerdosAsesorDia entry
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H0','location'=>'models/GestionModel.php:getSumaValorAcuerdosAsesorDia:entry','message'=>'enter','data'=>['asesorCedulaLen'=>strlen((string)$asesorCedula),'phpDate'=>date('Y-m-d'),'phpNow'=>date('Y-m-d H:i:s'),'phpTz'=>date_default_timezone_get() ?: ''],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        // #region agent log b7eaa7 mysql clock + tz
        try {
            $stmtClock = $this->pdo->query("SELECT CURDATE() AS curdate, NOW() AS now_ts, @@session.time_zone AS session_tz, @@global.time_zone AS global_tz");
            $clk = $stmtClock ? ($stmtClock->fetch(PDO::FETCH_ASSOC) ?: []) : [];
            @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H2','location'=>'models/GestionModel.php:getSumaValorAcuerdosAsesorDia:clock','message'=>'mysql_clock','data'=>['curdate'=>(string)($clk['curdate']??''),'now_ts'=>(string)($clk['now_ts']??''),'session_tz'=>(string)($clk['session_tz']??''),'global_tz'=>(string)($clk['global_tz']??'')],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion

        // #region agent log b7eaa7 compare fecha_pago vs fecha_creacion
        try {
            $stmtCmp = $this->pdo->prepare("
                SELECT
                    SUM(CASE WHEN a.fecha_pago = CURDATE() THEN 1 ELSE 0 END) AS cnt_pago_hoy,
                    COALESCE(SUM(CASE WHEN a.fecha_pago = CURDATE() THEN a.valor_acuerdo ELSE 0 END), 0) AS sum_pago_hoy,
                    SUM(CASE WHEN DATE(hg.fecha_creacion) = CURDATE() THEN 1 ELSE 0 END) AS cnt_creacion_hoy,
                    COALESCE(SUM(CASE WHEN DATE(hg.fecha_creacion) = CURDATE() THEN a.valor_acuerdo ELSE 0 END), 0) AS sum_creacion_hoy,
                    SUM(CASE WHEN a.fecha_pago IS NULL THEN 1 ELSE 0 END) AS cnt_pago_null
                FROM acuerdos a
                INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
                WHERE hg.asesor_cedula = ?
            ");
            $stmtCmp->execute([(string)$asesorCedula]);
            $cmp = $stmtCmp->fetch(PDO::FETCH_ASSOC) ?: [];
            @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H1','location'=>'models/GestionModel.php:getSumaValorAcuerdosAsesorDia:compare','message'=>'counts_sums','data'=>['cnt_pago_hoy'=>(int)($cmp['cnt_pago_hoy']??0),'sum_pago_hoy'=>(float)($cmp['sum_pago_hoy']??0),'cnt_creacion_hoy'=>(int)($cmp['cnt_creacion_hoy']??0),'sum_creacion_hoy'=>(float)($cmp['sum_creacion_hoy']??0),'cnt_pago_null'=>(int)($cmp['cnt_pago_null']??0)],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion

        $sql = "
            SELECT COALESCE(SUM(a.valor_acuerdo), 0) AS total
            FROM acuerdos a
            INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND DATE(hg.fecha_creacion) = CURDATE()
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$asesorCedula]);
        $total = (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // #region agent log b7eaa7 getSumaValorAcuerdosAsesorDia result
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H1','location'=>'models/GestionModel.php:getSumaValorAcuerdosAsesorDia:result','message'=>'total','data'=>['total'=>$total],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        return $total;
    }

    /**
     * Suma valor_acuerdo de los acuerdos del asesor en el mes calendario actual (por fecha_pago).
     */
    public function getSumaValorAcuerdosAsesorMes($asesorCedula): float {
        $sql = "
            SELECT COALESCE(SUM(a.valor_acuerdo), 0) AS total
            FROM acuerdos a
            INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND a.fecha_pago IS NOT NULL
              AND YEAR(a.fecha_pago) = YEAR(CURDATE())
              AND MONTH(a.fecha_pago) = MONTH(CURDATE())
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$asesorCedula]);
        return (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    /**
     * Completa campos que espera CoordinadorController::formatearFilaGestionCSV().
     */
    private function aplicarCompatExportacionGestion(array $row): array {
        $labels = [];
        if (($row['llamada_telefonica'] ?? 'no') === 'si') {
            $labels[] = 'LLAMADA TELEFONICA';
        }
        if (($row['email'] ?? 'no') === 'si') {
            $labels[] = 'CORREO ELECTRONICO';
        }
        if (($row['sms'] ?? 'no') === 'si') {
            $labels[] = 'SMS';
        }
        if (($row['correo_fisico'] ?? 'no') === 'si') {
            $labels[] = 'CORREO FISICO';
        }
        if (($row['whatsap'] ?? 'no') === 'si') {
            $labels[] = 'WHATSAPP';
        }
        $row['canales_autorizados_texto'] = implode(', ', $labels);
        return $row;
    }

    /**
     * Historial para CSV (coordinador): une historial_gestiones, clientes, obligaciones, base, acuerdo más reciente.
     *
     * @param string|null $asesorId Cédula del asesor (mismo criterio que usuarios.cedula).
     */
    public function getHistorialCompletoParaExportacion($asesorId = null, $inicio = null, $fin = null, $filtros = []) {
        if ($asesorId === null || $asesorId === '' || $inicio === null || $inicio === '' || $fin === null || $fin === '') {
            return [];
        }

        $sql = "
            SELECT
                hg.id_gestion,
                hg.fecha_creacion AS fecha_gestion,
                u.nombre AS asesor_nombre,
                c.cedula AS cedula,
                c.nombre AS cliente_nombre,
                b.nombre AS base_datos_nombre,
                hg.telefono_contacto,
                o.numero_factura AS obligacion_texto,
                o.franja AS franja_cliente,
                hg.forma_contacto,
                hg.tipo_contacto AS tipo_gestion,
                hg.resultado_contacto AS resultado,
                hg.resultado_contacto AS tipificacion_2_nivel,
                hg.razon_especifica AS tipificacion_3_nivel,
                hg.observaciones AS comentarios,
                (SELECT a2.fecha_pago FROM acuerdos a2 WHERE a2.gestion_id = hg.id_gestion ORDER BY a2.id_acuerdos DESC LIMIT 1) AS fecha_acuerdo,
                (SELECT a2.valor_acuerdo FROM acuerdos a2 WHERE a2.gestion_id = hg.id_gestion ORDER BY a2.id_acuerdos DESC LIMIT 1) AS monto_acuerdo,
                c.tel1 AS telefono,
                c.tel2 AS celular2,
                c.tel3 AS cel3,
                c.tel4 AS cel4,
                c.tel5 AS cel5,
                c.tel6 AS cel6,
                c.tel7 AS cel7,
                c.tel8 AS cel8,
                c.tel9 AS cel9,
                c.tel10 AS cel10,
                hg.llamada_telefonica,
                hg.email,
                hg.sms,
                hg.correo_fisico,
                hg.whatsap
            FROM historial_gestiones hg
            INNER JOIN usuarios u ON hg.asesor_cedula = u.cedula
            INNER JOIN clientes c ON hg.cliente_id = c.id_cliente
            INNER JOIN base_clientes b ON c.base_id = b.id_base
            INNER JOIN obligaciones o ON hg.obligacion_id = o.id_obligacion
            WHERE hg.asesor_cedula = ?
              AND DATE(hg.fecha_creacion) BETWEEN ? AND ?
            ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $r = $this->aplicarCompatExportacionGestion($r);
        }
        unset($r);

        return $rows;
    }

    /**
     * Asesores con al menos una gestión en el rango (opcionalmente limitados al equipo de un coordinador).
     *
     * @return array<int, array{id: string, cedula: string, nombre_completo: string}>
     */
    public function getAsesoresConGestionesEnPeriodo($inicio, $fin, $coordinadorCedula = null) {
        $sql = "
            SELECT DISTINCT u.cedula AS id, u.cedula, u.nombre AS nombre_completo
            FROM historial_gestiones hg
            INNER JOIN usuarios u ON u.cedula = hg.asesor_cedula
            WHERE DATE(hg.fecha_creacion) BETWEEN ? AND ?
        ";
        $params = [(string)$inicio, (string)$fin];

        if ($coordinadorCedula !== null && $coordinadorCedula !== '') {
            $sql .= "
              AND EXISTS (
                SELECT 1 FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = hg.asesor_cedula
                  AND ac.cordinador_cedula = ?
                  AND ac.estado = 'activo'
              )
            ";
            $params[] = (string)$coordinadorCedula;
        }

        $sql .= " ORDER BY u.nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Gestiones del equipo de un coordinador en un rango de fechas, con filtros opcionales.
     *
     * @param string|null $asesorId Cédula del asesor o null.
     */
    public function getGestionFiltrada($coordinadorCedula, $fechaInicio, $fechaFin, $asesorId = null, $resultado = null, $tipoGestion = null) {
        if ($coordinadorCedula === null || $coordinadorCedula === '' || $fechaInicio === '' || $fechaFin === '') {
            return [];
        }

        $sql = "
            SELECT
                hg.id_gestion,
                hg.fecha_creacion AS fecha_gestion,
                u.nombre AS asesor_nombre,
                c.cedula AS cedula,
                c.nombre AS cliente_nombre,
                b.nombre AS base_datos_nombre,
                hg.telefono_contacto,
                o.numero_factura AS obligacion_texto,
                o.franja AS franja_cliente,
                hg.forma_contacto,
                hg.tipo_contacto AS tipo_gestion,
                hg.resultado_contacto AS resultado,
                hg.resultado_contacto AS tipificacion_2_nivel,
                hg.razon_especifica AS tipificacion_3_nivel,
                hg.observaciones AS comentarios,
                (SELECT a2.fecha_pago FROM acuerdos a2 WHERE a2.gestion_id = hg.id_gestion ORDER BY a2.id_acuerdos DESC LIMIT 1) AS fecha_acuerdo,
                (SELECT a2.valor_acuerdo FROM acuerdos a2 WHERE a2.gestion_id = hg.id_gestion ORDER BY a2.id_acuerdos DESC LIMIT 1) AS monto_acuerdo,
                c.tel1 AS telefono,
                c.tel2 AS celular2,
                c.tel3 AS cel3,
                c.tel4 AS cel4,
                c.tel5 AS cel5,
                c.tel6 AS cel6,
                c.tel7 AS cel7,
                c.tel8 AS cel8,
                c.tel9 AS cel9,
                c.tel10 AS cel10,
                hg.llamada_telefonica,
                hg.email,
                hg.sms,
                hg.correo_fisico,
                hg.whatsap
            FROM historial_gestiones hg
            INNER JOIN usuarios u ON hg.asesor_cedula = u.cedula
            INNER JOIN clientes c ON hg.cliente_id = c.id_cliente
            INNER JOIN base_clientes b ON c.base_id = b.id_base
            INNER JOIN obligaciones o ON hg.obligacion_id = o.id_obligacion
            WHERE EXISTS (
                SELECT 1 FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = hg.asesor_cedula
                  AND ac.cordinador_cedula = ?
                  AND ac.estado = 'activo'
            )
            AND DATE(hg.fecha_creacion) BETWEEN ? AND ?
        ";

        $params = [(string)$coordinadorCedula, (string)$fechaInicio, (string)$fechaFin];

        if ($asesorId !== null && $asesorId !== '') {
            $sql .= " AND hg.asesor_cedula = ?";
            $params[] = (string)$asesorId;
        }
        if ($resultado !== null && $resultado !== '') {
            $sql .= " AND hg.resultado_contacto LIKE ?";
            $params[] = '%' . $resultado . '%';
        }
        if ($tipoGestion !== null && $tipoGestion !== '') {
            $sql .= " AND hg.tipo_contacto LIKE ?";
            $params[] = '%' . $tipoGestion . '%';
        }

        $sql .= " ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $r = $this->aplicarCompatExportacionGestion($r);
        }
        unset($r);

        return $rows;
    }

    // Métodos usados por controladores/reportes (dashboard asesor).
    public function getMetricasDashboard($asesorId, $periodo = 'dia') {
        [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS gestiones,
                SUM(CASE WHEN COALESCE(hg.resultado_contacto,'') <> '' THEN 1 ELSE 0 END) AS contactos_efectivos
            FROM historial_gestiones hg
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $this->pdo->prepare("
            SELECT
                COUNT(*) AS acuerdos,
                COALESCE(SUM(a.valor_acuerdo), 0) AS recaudo
            FROM acuerdos a
            JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
        ");
        $stmt2->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'periodo' => (string)$periodo,
            'inicio' => (string)$inicio,
            'fin' => (string)$fin,
            'gestiones' => (int)($row['gestiones'] ?? 0),
            'contactos_efectivos' => (int)($row['contactos_efectivos'] ?? 0),
            'acuerdos' => (int)($row2['acuerdos'] ?? 0),
            'recaudo' => (float)($row2['recaudo'] ?? 0),
        ];
    }

    public function getTipificacionesPorResultado($asesorId, $periodo = 'dia') {
        [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(hg.resultado_contacto), ''), 'No especificado') AS resultado,
                COUNT(*) AS cantidad
            FROM historial_gestiones hg
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
            GROUP BY resultado
            ORDER BY cantidad DESC
            LIMIT 20
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getGestionesUltimosDias($asesorId, $dias = 7) {
        $dias = max(1, min(60, (int)$dias));
        $inicioDt = new DateTime('today');
        $inicioDt->modify('-' . ($dias - 1) . ' days');
        $inicio = $inicioDt->format('Y-m-d 00:00:00');
        $fin = (new DateTime('today'))->format('Y-m-d 23:59:59');

        $stmt = $this->pdo->prepare("
            SELECT DATE(hg.fecha_creacion) AS fecha, COUNT(*) AS cantidad
            FROM historial_gestiones hg
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
            GROUP BY DATE(hg.fecha_creacion)
            ORDER BY DATE(hg.fecha_creacion) ASC
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $r) {
            $map[(string)($r['fecha'] ?? '')] = (int)($r['cantidad'] ?? 0);
        }
        $out = [];
        $cursor = clone $inicioDt;
        for ($i = 0; $i < $dias; $i++) {
            $key = $cursor->format('Y-m-d');
            $out[] = ['fecha' => $key, 'cantidad' => (int)($map[$key] ?? 0)];
            $cursor->modify('+1 day');
        }
        return $out;
    }

    public function getClientesConSeguimiento($asesorId) { return []; }

    public function getUltimasGestiones($asesorId, $limit = 5) {
        $limit = max(1, min(50, (int)$limit));
        $stmt = $this->pdo->prepare("
            SELECT
                hg.*,
                c.nombre AS cliente_nombre,
                c.cedula AS cliente_cedula,
                b.nombre AS nombre_base
            FROM historial_gestiones hg
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE hg.asesor_cedula = ?
            ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC
            LIMIT {$limit}
        ");
        $stmt->execute([(string)$asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getLlamadasPendientesHoy($asesorId) {
        $stmt = $this->pdo->prepare("
            SELECT
                c.id_cliente,
                c.nombre AS cliente_nombre,
                c.cedula AS cliente_cedula,
                c.tel1 AS telefono,
                b.nombre AS nombre_base,
                hg.fecha_creacion AS fecha_gestion,
                hg.resultado_contacto,
                hg.razon_especifica
            FROM historial_gestiones hg
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE hg.asesor_cedula = ?
              AND DATE(hg.fecha_creacion) = CURDATE()
              AND (hg.resultado_contacto = 'volver_llamar' OR hg.resultado_contacto LIKE '%VOLVER A LLAMAR%')
            ORDER BY hg.fecha_creacion DESC
            LIMIT 50
        ");
        $stmt->execute([(string)$asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotalLlamadasPendientesHoy($asesorId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS total
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND DATE(fecha_creacion) = CURDATE()
              AND (resultado_contacto = 'volver_llamar' OR resultado_contacto LIKE '%VOLVER A LLAMAR%')
        ");
        $stmt->execute([(string)$asesorId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getClientesGestionados($asesorId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT cliente_id) AS total
            FROM historial_gestiones
            WHERE asesor_cedula = ?
        ");
        $stmt->execute([(string)$asesorId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getTotalRecaudado($asesorId) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(a.valor_acuerdo), 0) AS total
            FROM acuerdos a
            JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
        ");
        $stmt->execute([(string)$asesorId]);
        return (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getMetricasSemana($asesorId) {
        [$inicio, $fin] = $this->rangoFechasPeriodo('semana');
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS gestiones_semana,
                SUM(CASE WHEN COALESCE(resultado_contacto,'') <> '' THEN 1 ELSE 0 END) AS contactos_efectivos_semana
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND fecha_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $this->pdo->prepare("
            SELECT COUNT(*) AS acuerdos_semana
            FROM acuerdos a
            JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
        ");
        $stmt2->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'gestiones_semana' => (int)($row['gestiones_semana'] ?? 0),
            'contactos_efectivos_semana' => (int)($row['contactos_efectivos_semana'] ?? 0),
            'acuerdos_semana' => (int)($row2['acuerdos_semana'] ?? 0),
        ];
    }

    public function getMetricasMes($asesorId) {
        [$inicio, $fin] = $this->rangoFechasPeriodo('mes');
        $stmt = $this->pdo->prepare("
            SELECT
                SUM(CASE WHEN COALESCE(resultado_contacto,'') <> '' THEN 1 ELSE 0 END) AS contactos_efectivos_mes
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND fecha_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $this->pdo->prepare("
            SELECT COUNT(*) AS acuerdos_mes
            FROM acuerdos a
            JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
        ");
        $stmt2->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'contactos_efectivos_mes' => (int)($row['contactos_efectivos_mes'] ?? 0),
            'acuerdos_mes' => (int)($row2['acuerdos_mes'] ?? 0),
        ];
    }
    public function getMetricasEquipoConFechas($coordinadorId, $inicio, $fin) {
        $inicio = (string)$inicio;
        $fin = (string)$fin;
        // Normalizar a rangos completos (incluye horas si no vienen).
        if (strlen($inicio) <= 10) $inicio .= ' 00:00:00';
        if (strlen($fin) <= 10) $fin .= ' 23:59:59';
        $tsI = strtotime($inicio);
        $tsF = strtotime($fin);
        if ($tsI === false || $tsF === false) {
            [$inicio, $fin] = $this->rangoFechasPeriodo('mes');
        } else {
            $inicio = date('Y-m-d H:i:s', $tsI);
            $fin = date('Y-m-d H:i:s', $tsF);
        }

        return $this->calcularMetricasEquipoRango((string)$coordinadorId, $inicio, $fin);
    }

    public function getMetricasEquipo($coordinadorId, $periodo = 'total') {
        $periodo = strtolower(trim((string)$periodo));
        if ($periodo === 'total') {
            $inicio = '1970-01-01 00:00:00';
            $fin = '2099-12-31 23:59:59';
        } else {
            [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);
        }
        return $this->calcularMetricasEquipoRango((string)$coordinadorId, (string)$inicio, (string)$fin);
    }

    private function calcularMetricasEquipoRango(string $coordinadorCedula, string $inicio, string $fin): array {
        // Total asesores activos del equipo
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT ac.asesor_cedula) AS total
            FROM asignaciones_cordinador ac
            WHERE ac.cordinador_cedula = ?
              AND ac.estado = 'activo'
        ");
        $stmt->execute([$coordinadorCedula]);
        $totalAsesores = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Total clientes en bases del coordinador (se apoya en base_clientes.total_clientes)
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(b.total_clientes), 0) AS total
            FROM base_clientes b
            WHERE b.creado_por = ?
        ");
        $stmt->execute([$coordinadorCedula]);
        $totalClientes = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Gestiones / contactos efectivos / duracion promedio (en rango) para asesores del equipo
        $stmt = $this->pdo->prepare($this->hasNormColumns() ? "
            SELECT
                COUNT(*) AS total_gestiones,
                SUM(CASE WHEN hg.tipo_contacto_norm IN ('contacto_exitoso','contacto_tercero') THEN 1 ELSE 0 END) AS contactos_efectivos,
                COALESCE(AVG(NULLIF(hg.duracion_segundos, 0)), 0) AS tmo
            FROM historial_gestiones hg
            WHERE hg.fecha_creacion BETWEEN ? AND ?
              AND EXISTS (
                SELECT 1
                FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = hg.asesor_cedula
                  AND ac.cordinador_cedula = ?
                  AND ac.estado = 'activo'
              )
        " : "
            SELECT
                COUNT(*) AS total_gestiones,
                SUM(CASE
                    WHEN LOWER(REPLACE(TRIM(hg.tipo_contacto), ' ', '_')) IN ('contacto_exitoso','contacto_tercero')
                      OR UPPER(TRIM(hg.tipo_contacto)) IN ('CONTACTO EXITOSO','CONTACTO CON TERCERO')
                    THEN 1 ELSE 0 END) AS contactos_efectivos,
                COALESCE(AVG(NULLIF(hg.duracion_segundos, 0)), 0) AS tmo
            FROM historial_gestiones hg
            WHERE hg.fecha_creacion BETWEEN ? AND ?
              AND EXISTS (
                SELECT 1
                FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = hg.asesor_cedula
                  AND ac.cordinador_cedula = ?
                  AND ac.estado = 'activo'
              )
        ");
        $stmt->execute([(string)$inicio, (string)$fin, $coordinadorCedula]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalGestiones = (int)($row['total_gestiones'] ?? 0);
        $contactosEfectivos = (int)($row['contactos_efectivos'] ?? 0);
        $tmo = (float)($row['tmo'] ?? 0);

        // Acuerdos y monto en rango (por fecha_creacion de la gestión)
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS acuerdos,
                COALESCE(SUM(a.valor_acuerdo), 0) AS total_ventas_monto
            FROM acuerdos a
            JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.fecha_creacion BETWEEN ? AND ?
              AND EXISTS (
                SELECT 1
                FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = hg.asesor_cedula
                  AND ac.cordinador_cedula = ?
                  AND ac.estado = 'activo'
              )
        ");
        $stmt->execute([(string)$inicio, (string)$fin, $coordinadorCedula]);
        $row2 = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $acuerdos = (int)($row2['acuerdos'] ?? 0);
        $totalVentasMonto = (float)($row2['total_ventas_monto'] ?? 0);

        $tasaConversion = $totalGestiones > 0 ? round(($acuerdos / $totalGestiones) * 100, 1) : 0.0;
        $tasaContactoEfectivo = $totalGestiones > 0 ? round(($contactosEfectivos / $totalGestiones) * 100, 1) : 0.0;
        $promedioVenta = $acuerdos > 0 ? round($totalVentasMonto / $acuerdos, 2) : 0.0;

        return [
            'total_asesores' => $totalAsesores,
            'total_clientes' => $totalClientes,
            'total_gestiones' => $totalGestiones,
            'ventas_exitosas' => $acuerdos,
            'tasa_conversion' => $tasaConversion,
            'tasa_contacto_efectivo' => $tasaContactoEfectivo,
            'tiempo_promedio_conversacion' => (int) round($tmo),
            'total_ventas_monto' => $totalVentasMonto,
            'promedio_venta' => $promedioVenta,
            'inicio' => $inicio,
            'fin' => $fin,
        ];
    }

    public function getMetricasAsesor($asesorId, $periodo = 'total', $inicio = null, $fin = null) {
        if ($inicio !== null && $fin !== null && (string)$inicio !== '' && (string)$fin !== '') {
            $i = (string)$inicio;
            $f = (string)$fin;
            if (strlen($i) <= 10) $i .= ' 00:00:00';
            if (strlen($f) <= 10) $f .= ' 23:59:59';
            $tsI = strtotime($i);
            $tsF = strtotime($f);
            if ($tsI !== false && $tsF !== false) {
                $inicioR = date('Y-m-d H:i:s', $tsI);
                $finR = date('Y-m-d H:i:s', $tsF);
            } else {
                [$inicioR, $finR] = $this->rangoFechasPeriodo($periodo);
            }
        } else {
            $periodo = strtolower(trim((string)$periodo));
            if ($periodo === 'total') {
                $inicioR = '1970-01-01 00:00:00';
                $finR = '2099-12-31 23:59:59';
            } else {
                [$inicioR, $finR] = $this->rangoFechasPeriodo($periodo);
            }
        }

        $stmt = $this->pdo->prepare($this->hasNormColumns() ? "
            SELECT
                COUNT(*) AS total_gestiones,
                SUM(CASE
                    WHEN tipo_contacto_norm IN ('contacto_exitoso','contacto_tercero')
                    THEN 1 ELSE 0 END) AS contactos_efectivos,
                COUNT(DISTINCT cliente_id) AS total_clientes,
                COALESCE(AVG(NULLIF(duracion_segundos, 0)), 0) AS tmo
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND fecha_creacion BETWEEN ? AND ?
        " : "
            SELECT
                COUNT(*) AS total_gestiones,
                SUM(CASE
                    WHEN LOWER(REPLACE(TRIM(tipo_contacto), ' ', '_')) IN ('contacto_exitoso','contacto_tercero')
                      OR UPPER(TRIM(tipo_contacto)) IN ('CONTACTO EXITOSO','CONTACTO CON TERCERO')
                    THEN 1 ELSE 0 END) AS contactos_efectivos,
                COUNT(DISTINCT cliente_id) AS total_clientes,
                COALESCE(AVG(NULLIF(duracion_segundos, 0)), 0) AS tmo
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND fecha_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([(string)$asesorId, (string)$inicioR, (string)$finR]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $this->pdo->prepare("
            SELECT
                COUNT(*) AS ventas_exitosas,
                COALESCE(SUM(a.valor_acuerdo), 0) AS total_ventas_monto
            FROM acuerdos a
            JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
        ");
        $stmt2->execute([(string)$asesorId, (string)$inicioR, (string)$finR]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalGestiones = (int)($row['total_gestiones'] ?? 0);
        $ventas = (int)($row2['ventas_exitosas'] ?? 0);
        $monto = (float)($row2['total_ventas_monto'] ?? 0);

        $tasaConversion = $totalGestiones > 0 ? round(($ventas / $totalGestiones) * 100, 1) : 0.0;
        $tasaContactoEfectivo = $totalGestiones > 0 ? round((((int)($row['contactos_efectivos'] ?? 0)) / $totalGestiones) * 100, 1) : 0.0;
        $promedioVenta = $ventas > 0 ? round($monto / $ventas, 2) : 0.0;

        return [
            'total_gestiones' => $totalGestiones,
            'contactos_efectivos' => (int)($row['contactos_efectivos'] ?? 0),
            'total_clientes' => (int)($row['total_clientes'] ?? 0),
            'ventas_exitosas' => $ventas,
            'tasa_conversion' => $tasaConversion,
            'tasa_contacto_efectivo' => $tasaContactoEfectivo,
            'tiempo_promedio_conversacion' => (int) round((float)($row['tmo'] ?? 0)),
            'total_ventas_monto' => $monto,
            'promedio_venta' => $promedioVenta,
            'inicio' => $inicioR,
            'fin' => $finR,
        ];
    }

    public function getGestionesPorDia($asesorId, $periodo = 'dia') {
        // Para CoordinadorController solo se usa 'dia'. Devolvemos una fila con totales.
        [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);
        $stmt = $this->pdo->prepare($this->hasNormColumns() ? "
            SELECT
                COUNT(*) AS total_gestiones,
                SUM(CASE
                    WHEN tipo_contacto_norm IN ('contacto_exitoso','contacto_tercero')
                    THEN 1 ELSE 0 END) AS contactos_efectivos,
                SUM(CASE
                    WHEN resultado_contacto_norm IN ('acuerdo_pago','acuerdo_de_pago')
                    THEN 1 ELSE 0 END) AS acuerdos_hoy
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND fecha_creacion BETWEEN ? AND ?
        " : "
            SELECT
                COUNT(*) AS total_gestiones,
                SUM(CASE
                    WHEN LOWER(REPLACE(TRIM(tipo_contacto), ' ', '_')) IN ('contacto_exitoso','contacto_tercero')
                      OR UPPER(TRIM(tipo_contacto)) IN ('CONTACTO EXITOSO','CONTACTO CON TERCERO')
                    THEN 1 ELSE 0 END) AS contactos_efectivos,
                SUM(CASE
                    WHEN LOWER(REPLACE(TRIM(resultado_contacto), ' ', '_')) IN ('acuerdo_pago','acuerdo_de_pago')
                      OR UPPER(TRIM(resultado_contacto)) = 'ACUERDO DE PAGO'
                    THEN 1 ELSE 0 END) AS acuerdos_hoy
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND fecha_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [[
            'fecha' => date('Y-m-d'),
            'total_gestiones' => (int)($row['total_gestiones'] ?? 0),
            'contactos_efectivos' => (int)($row['contactos_efectivos'] ?? 0),
            // Compat: antes se llamaba 'ventas' y se contaba desde acuerdos. Ahora representa acuerdos en historial.
            'ventas' => (int)($row['acuerdos_hoy'] ?? 0),
        ]];
    }

    /**
     * Resumen del día (hoy) para la tabla "Gestión de Asesores" (coordinador).
     *
     * Reglas (según requerimiento):
     * - gestiones_hoy: todas las gestiones con DATE(fecha_creacion)=hoy
     * - contactos_efectivos_hoy: gestiones de hoy con tipo_contacto in (contacto_exitoso, contacto_tercero)
     * - acuerdos_hoy: gestiones de hoy con resultado_contacto = acuerdo_pago
     */
    public function getResumenActividadHoyAsesor($asesorId): array {
        $stmt = $this->pdo->prepare($this->hasNormColumns() ? "
            SELECT
                COUNT(*) AS gestiones_hoy,
                SUM(CASE
                    WHEN tipo_contacto_norm IN ('contacto_exitoso','contacto_tercero')
                    THEN 1 ELSE 0 END) AS contactos_efectivos_hoy,
                SUM(CASE
                    WHEN resultado_contacto_norm IN ('acuerdo_pago','acuerdo_de_pago')
                    THEN 1 ELSE 0 END) AS acuerdos_hoy
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND DATE(fecha_creacion) = CURDATE()
        " : "
            SELECT
                COUNT(*) AS gestiones_hoy,
                SUM(CASE
                    WHEN LOWER(REPLACE(TRIM(tipo_contacto), ' ', '_')) IN ('contacto_exitoso','contacto_tercero')
                      OR UPPER(TRIM(tipo_contacto)) IN ('CONTACTO EXITOSO','CONTACTO CON TERCERO')
                    THEN 1 ELSE 0 END) AS contactos_efectivos_hoy,
                SUM(CASE
                    WHEN LOWER(REPLACE(TRIM(resultado_contacto), ' ', '_')) IN ('acuerdo_pago','acuerdo_de_pago')
                      OR UPPER(TRIM(resultado_contacto)) = 'ACUERDO DE PAGO'
                    THEN 1 ELSE 0 END) AS acuerdos_hoy
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND DATE(fecha_creacion) = CURDATE()
        ");
        $stmt->execute([(string)$asesorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $out = [
            'gestiones_hoy' => (int)($row['gestiones_hoy'] ?? 0),
            'contactos_efectivos_hoy' => (int)($row['contactos_efectivos_hoy'] ?? 0),
            'acuerdos_hoy' => (int)($row['acuerdos_hoy'] ?? 0),
        ];

        // #region agent log b7eaa7 actividad hoy breakdown when mismatch
        // Si hay gestiones hoy pero los contadores salen 0, registrar valores reales guardados en BD.
        try {
            if ($out['gestiones_hoy'] > 0 && ($out['contactos_efectivos_hoy'] === 0 || $out['acuerdos_hoy'] === 0)) {
                $tcStmt = $this->pdo->prepare("
                    SELECT COALESCE(NULLIF(TRIM(tipo_contacto), ''), '(vacio)') AS k, COUNT(*) AS c
                    FROM historial_gestiones
                    WHERE asesor_cedula = ?
                      AND DATE(fecha_creacion) = CURDATE()
                    GROUP BY COALESCE(NULLIF(TRIM(tipo_contacto), ''), '(vacio)')
                    ORDER BY c DESC
                    LIMIT 10
                ");
                $tcStmt->execute([(string)$asesorId]);
                $tc = $tcStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $rcStmt = $this->pdo->prepare("
                    SELECT COALESCE(NULLIF(TRIM(resultado_contacto), ''), '(vacio)') AS k, COUNT(*) AS c
                    FROM historial_gestiones
                    WHERE asesor_cedula = ?
                      AND DATE(fecha_creacion) = CURDATE()
                    GROUP BY COALESCE(NULLIF(TRIM(resultado_contacto), ''), '(vacio)')
                    ORDER BY c DESC
                    LIMIT 10
                ");
                $rcStmt->execute([(string)$asesorId]);
                $rc = $rcStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                    'sessionId' => 'b7eaa7',
                    'runId' => 'pre',
                    'hypothesisId' => 'H7',
                    'location' => 'models/GestionModel.php:getResumenActividadHoyAsesor:breakdown',
                    'message' => 'today_value_breakdown',
                    'data' => [
                        'asesorIdLen' => strlen((string)$asesorId),
                        'gestiones_hoy' => $out['gestiones_hoy'],
                        'contactos_efectivos_hoy' => $out['contactos_efectivos_hoy'],
                        'acuerdos_hoy' => $out['acuerdos_hoy'],
                        'tipo_contacto_top' => $tc,
                        'resultado_contacto_top' => $rc,
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            }
        } catch (Throwable $e) {}
        // #endregion

        return $out;
    }

    public function getGestionesClienteEnTarea($clienteId, $asesorId, $inicio = null, $fin = null) {
        $params = [(int)$clienteId, (string)$asesorId];
        $sql = "
            SELECT hg.*
            FROM historial_gestiones hg
            WHERE hg.cliente_id = ?
              AND hg.asesor_cedula = ?
        ";
        if ($inicio !== null && $fin !== null && (string)$inicio !== '' && (string)$fin !== '') {
            $i = (string)$inicio;
            $f = (string)$fin;
            if (strlen($i) <= 10) $i .= ' 00:00:00';
            if (strlen($f) <= 10) $f .= ' 23:59:59';
            $tsI = strtotime($i);
            $tsF = strtotime($f);
            if ($tsI !== false && $tsF !== false) {
                $sql .= " AND hg.fecha_creacion BETWEEN ? AND ? ";
                $params[] = date('Y-m-d H:i:s', $tsI);
                $params[] = date('Y-m-d H:i:s', $tsF);
            }
        }
        $sql .= " ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getLlamadasPendientesCoordinador($coordinadorId) {
        $stmt = $this->pdo->prepare($this->hasNormColumns() ? "
            SELECT
                hg.id_gestion,
                hg.fecha_creacion AS fecha_gestion,
                hg.asesor_cedula,
                u.nombre AS asesor_nombre,
                c.id_cliente,
                c.nombre AS cliente_nombre,
                c.cedula AS cliente_cedula,
                c.tel1 AS telefono,
                b.nombre AS nombre_base,
                hg.resultado_contacto,
                hg.razon_especifica
            FROM historial_gestiones hg
            JOIN usuarios u ON hg.asesor_cedula = u.cedula
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE DATE(hg.fecha_creacion) = CURDATE()
              AND hg.resultado_contacto_norm IN ('volver_a_llamar','volver_llamar','agenda_llamada_de_seguimiento')
              AND EXISTS (
                SELECT 1
                FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = hg.asesor_cedula
                  AND ac.cordinador_cedula = ?
                  AND ac.estado = 'activo'
              )
            ORDER BY hg.fecha_creacion DESC
            LIMIT 200
        " : "
            SELECT
                hg.id_gestion,
                hg.fecha_creacion AS fecha_gestion,
                hg.asesor_cedula,
                u.nombre AS asesor_nombre,
                c.id_cliente,
                c.nombre AS cliente_nombre,
                c.cedula AS cliente_cedula,
                c.tel1 AS telefono,
                b.nombre AS nombre_base,
                hg.resultado_contacto,
                hg.razon_especifica
            FROM historial_gestiones hg
            JOIN usuarios u ON hg.asesor_cedula = u.cedula
            JOIN clientes c ON hg.cliente_id = c.id_cliente
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE DATE(hg.fecha_creacion) = CURDATE()
              AND (hg.resultado_contacto = 'volver_llamar' OR hg.resultado_contacto LIKE '%VOLVER A LLAMAR%')
              AND EXISTS (
                SELECT 1
                FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = hg.asesor_cedula
                  AND ac.cordinador_cedula = ?
                  AND ac.estado = 'activo'
              )
            ORDER BY hg.fecha_creacion DESC
            LIMIT 200
        ");
        $stmt->execute([(string)$coordinadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Total de gestiones del asesor en el periodo (por defecto mes calendario), alineado con getTipificacionesPorResultado.
     *
     * @param string $asesorId Cédula del asesor
     */
    public function getTotalLlamadasByAsesor($asesorId, $periodo = 'mes') {
        $periodo = strtolower(trim((string)$periodo));
        if ($periodo === 'total' || $periodo === 'all') {
            $inicio = '1970-01-01 00:00:00';
            $fin = '2099-12-31 23:59:59';
        } else {
            [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);
        }
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS n
            FROM historial_gestiones
            WHERE asesor_cedula = ?
              AND fecha_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);
    }

    /**
     * Acuerdos de pago: gestiones en `historial_gestiones` con resultado_contacto = acuerdo_pago
     * y fecha_creacion dentro del periodo (para "mes", solo dentro del mes calendario, sin rebasar).
     *
     * @param string $asesorId Cédula del asesor
     */
    public function getTotalAcuerdosByAsesor($asesorId, $periodo = 'mes') {
        $periodo = strtolower(trim((string)$periodo));
        if ($periodo === 'total' || $periodo === 'all') {
            $inicio = '1970-01-01 00:00:00';
            $fin = '2099-12-31 23:59:59';
        } else {
            [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);
        }
        // Normalizar y contar SOLO 'acuerdo_pago' (equivalente a 'acuerdo pago' al reemplazar '_' por espacio).
        // Nota: en BD también existe 'ACUERDO DE PAGO', pero el criterio solicitado es estrictamente acuerdo_pago.
        $stmt = $this->pdo->prepare($this->hasNormColumns() ? "
            SELECT COUNT(*) AS n
            FROM historial_gestiones hg
            WHERE hg.asesor_cedula = ?
              AND hg.resultado_contacto_norm IN ('acuerdo_pago','acuerdo_de_pago')
              AND hg.fecha_creacion >= ?
              AND hg.fecha_creacion <= ?
        " : "
            SELECT COUNT(*) AS n
            FROM historial_gestiones hg
            WHERE hg.asesor_cedula = ?
              AND LOWER(REPLACE(TRIM(hg.resultado_contacto), '_', ' ')) = 'acuerdo pago'
              AND hg.fecha_creacion >= ?
              AND hg.fecha_creacion <= ?
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);
    }

    /** @deprecated Usar getTotalAcuerdosByAsesor; se mantiene por compatibilidad. */
    public function getTotalVentasByAsesor($asesorId, $periodo = 'mes') {
        return $this->getTotalAcuerdosByAsesor($asesorId, $periodo);
    }

    public function getEstadisticasPorTipoVenta($asesorId, $periodo = 'mes') {
        [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);
        $stmt = $this->pdo->prepare($this->hasNormColumns() ? "
            SELECT
                COALESCE(NULLIF(TRIM(a.tipo_acuerdo), ''), 'sin_tipo') AS tipo_venta,
                COUNT(*) AS cantidad,
                COALESCE(SUM(a.valor_acuerdo), 0) AS monto_total
            FROM acuerdos a
            INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND hg.resultado_contacto_norm IN ('acuerdo_pago','acuerdo_de_pago')
              AND hg.fecha_creacion >= ?
              AND hg.fecha_creacion <= ?
            GROUP BY tipo_venta
            ORDER BY cantidad DESC
        " : "
            SELECT
                COALESCE(NULLIF(TRIM(a.tipo_acuerdo), ''), 'sin_tipo') AS tipo_venta,
                COUNT(*) AS cantidad,
                COALESCE(SUM(a.valor_acuerdo), 0) AS monto_total
            FROM acuerdos a
            INNER JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
            WHERE hg.asesor_cedula = ?
              AND LOWER(REPLACE(TRIM(hg.resultado_contacto), '_', ' ')) = 'acuerdo pago'
              AND hg.fecha_creacion >= ?
              AND hg.fecha_creacion <= ?
            GROUP BY tipo_venta
            ORDER BY cantidad DESC
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'tipo' => (string)($r['tipo_venta'] ?? ''),
                'tipo_venta' => (string)($r['tipo_venta'] ?? ''),
                'cantidad' => (int)($r['cantidad'] ?? 0),
                'monto_total' => (float)($r['monto_total'] ?? 0),
            ];
        }
        return $out;
    }

    public function getEstadisticasPorRechazo($asesorId, $periodo = 'mes') {
        [$inicio, $fin] = $this->rangoFechasPeriodo($periodo);
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(hg.razon_especifica), ''), 'Sin razón específica') AS razon,
                COUNT(*) AS cantidad
            FROM historial_gestiones hg
            WHERE hg.asesor_cedula = ?
              AND hg.fecha_creacion BETWEEN ? AND ?
              AND NOT EXISTS (
                  SELECT 1 FROM acuerdos a WHERE a.gestion_id = hg.id_gestion
              )
            GROUP BY razon
            ORDER BY cantidad DESC
            LIMIT 25
        ");
        $stmt->execute([(string)$asesorId, (string)$inicio, (string)$fin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'rechazo' => (string)($r['razon'] ?? ''),
                'razon' => (string)($r['razon'] ?? ''),
                'cantidad' => (int)($r['cantidad'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Listado para modal coordinador: clientes asignados por bases + última gestión del asesor, con filtros.
     *
     * @param string $asesorCedula Cédula del asesor (usuarios.cedula)
     * @param array $filtros gestion|tipificacion_especifica|contacto|tipificacion|busqueda
     */
    public function getGestionByAsesorAndFechasConFiltros(
        $asesorCedula,
        $fechaInicio = null,
        $fechaFin = null,
        array $filtros = []
    ) {
        $ced = trim((string)$asesorCedula);
        if ($ced === '') {
            return [];
        }

        $gestion = strtolower(trim((string)($filtros['gestion'] ?? 'gestionado')));
        $busqueda = trim((string)($filtros['busqueda'] ?? ''));
        $tipEsp = trim((string)($filtros['tipificacion_especifica'] ?? 'todos'));
        $contacto = trim((string)($filtros['contacto'] ?? 'todos'));
        $tipificacion = trim((string)($filtros['tipificacion'] ?? 'todos'));
        $fi = trim((string)$fechaInicio);
        $ff = trim((string)$fechaFin);

        if ($gestion === 'no_gestionado') {
            return $this->queryClientesAsignadosSinGestionModal($ced, $busqueda);
        }
        if ($gestion === 'todos') {
            return $this->queryClientesAsignadosConUltimaGestionModal(
                $ced,
                $fi,
                $ff,
                $tipEsp,
                $contacto,
                $tipificacion,
                $busqueda
            );
        }

        return $this->queryClientesGestionadosUltimaGestionModal(
            $ced,
            $fi,
            $ff,
            $tipEsp,
            $contacto,
            $tipificacion,
            $busqueda
        );
    }

    private function queryClientesAsignadosSinGestionModal(string $ced, string $busqueda): array {
        $params = [$ced, $ced];
        $sql = "
            SELECT
                NULL AS id_gestion,
                0 AS id,
                c.id_cliente,
                c.nombre AS cliente_nombre,
                c.nombre,
                c.cedula,
                c.tel1 AS telefono,
                c.tel2 AS celular2,
                NULL AS fecha_gestion,
                '' AS resultado,
                c.id_cliente AS asignacion_id
            FROM clientes c
            INNER JOIN base_clientes b ON b.id_base = c.base_id AND b.estado = 'activo'
            INNER JOIN asignacion_base_asesores aba
                ON aba.base_id = c.base_id AND aba.estado = 'activa' AND aba.asesor_cedula = ?
            WHERE c.estado = 'activo'
              AND NOT EXISTS (
                  SELECT 1 FROM historial_gestiones h
                  WHERE h.cliente_id = c.id_cliente AND h.asesor_cedula = ?
              )
        ";
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $sql .= " AND (c.nombre LIKE ? OR c.cedula LIKE ? OR c.tel1 LIKE ? OR c.tel2 LIKE ? OR c.tel3 LIKE ?) ";
            array_push($params, $like, $like, $like, $like, $like);
        }
        $sql .= " ORDER BY c.nombre ASC LIMIT 2000";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function queryClientesGestionadosUltimaGestionModal(
        string $ced,
        string $fi,
        string $ff,
        string $tipEsp,
        string $contacto,
        string $tipificacion,
        string $busqueda
    ): array {
        $params = [$ced, $ced];
        $sql = "
            SELECT
                hg.id_gestion,
                hg.id_gestion AS id,
                c.id_cliente,
                c.nombre AS cliente_nombre,
                c.nombre,
                c.cedula,
                c.tel1 AS telefono,
                c.tel2 AS celular2,
                hg.fecha_creacion AS fecha_gestion,
                COALESCE(hg.resultado_contacto, '') AS resultado,
                c.id_cliente AS asignacion_id
            FROM historial_gestiones hg
            INNER JOIN clientes c ON c.id_cliente = hg.cliente_id
            INNER JOIN base_clientes b ON b.id_base = c.base_id AND b.estado = 'activo'
            INNER JOIN asignacion_base_asesores aba
                ON aba.base_id = c.base_id AND aba.estado = 'activa' AND aba.asesor_cedula = hg.asesor_cedula
            WHERE hg.asesor_cedula = ?
              AND hg.id_gestion = (
                  SELECT MAX(h2.id_gestion)
                  FROM historial_gestiones h2
                  WHERE h2.cliente_id = hg.cliente_id AND h2.asesor_cedula = ?
              )
        ";

        if ($fi !== '' && $ff !== '') {
            $sql .= " AND DATE(hg.fecha_creacion) BETWEEN ? AND ? ";
            $params[] = substr($fi, 0, 10);
            $params[] = substr($ff, 0, 10);
        }

        if ($tipEsp !== '' && strcasecmp($tipEsp, 'todos') !== 0 && strcasecmp($tipEsp, 'sin_gestion') !== 0) {
            $sql .= " AND (
                hg.resultado_contacto = ?
                OR hg.razon_especifica = ?
                OR hg.resultado_contacto LIKE ?
                OR hg.razon_especifica LIKE ?
            ) ";
            $like = '%' . $tipEsp . '%';
            array_push($params, $tipEsp, $tipEsp, $like, $like);
        }

        if ($contacto !== '' && strcasecmp($contacto, 'todos') !== 0) {
            $sql .= " AND (hg.forma_contacto = ? OR hg.tipo_contacto LIKE ?) ";
            $params[] = $contacto;
            $params[] = '%' . $contacto . '%';
        }

        if ($tipificacion !== '' && strcasecmp($tipificacion, 'todos') !== 0) {
            $sql .= " AND hg.resultado_contacto LIKE ? ";
            $params[] = '%' . $tipificacion . '%';
        }

        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $sql .= " AND (c.nombre LIKE ? OR c.cedula LIKE ? OR c.tel1 LIKE ? OR c.tel2 LIKE ? OR c.tel3 LIKE ?) ";
            array_push($params, $like, $like, $like, $like, $like);
        }

        $sql .= " ORDER BY hg.fecha_creacion DESC, c.nombre ASC LIMIT 2000";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function queryClientesAsignadosConUltimaGestionModal(
        string $ced,
        string $fi,
        string $ff,
        string $tipEsp,
        string $contacto,
        string $tipificacion,
        string $busqueda
    ): array {
        $params = [$ced, $ced, $ced];
        $sql = "
            SELECT
                hg.id_gestion,
                COALESCE(hg.id_gestion, 0) AS id,
                c.id_cliente,
                c.nombre AS cliente_nombre,
                c.nombre,
                c.cedula,
                c.tel1 AS telefono,
                c.tel2 AS celular2,
                hg.fecha_creacion AS fecha_gestion,
                COALESCE(hg.resultado_contacto, '') AS resultado,
                c.id_cliente AS asignacion_id
            FROM clientes c
            INNER JOIN base_clientes b ON b.id_base = c.base_id AND b.estado = 'activo'
            INNER JOIN asignacion_base_asesores aba
                ON aba.base_id = c.base_id AND aba.estado = 'activa' AND aba.asesor_cedula = ?
            LEFT JOIN historial_gestiones hg ON hg.asesor_cedula = ?
              AND hg.id_gestion = (
                  SELECT MAX(h3.id_gestion)
                  FROM historial_gestiones h3
                  WHERE h3.cliente_id = c.id_cliente AND h3.asesor_cedula = ?
              )
            WHERE c.estado = 'activo'
        ";

        if ($fi !== '' && $ff !== '') {
            $sql .= " AND (hg.id_gestion IS NULL OR DATE(hg.fecha_creacion) BETWEEN ? AND ?) ";
            $params[] = substr($fi, 0, 10);
            $params[] = substr($ff, 0, 10);
        }

        if ($tipEsp !== '' && strcasecmp($tipEsp, 'todos') !== 0) {
            if (strcasecmp($tipEsp, 'sin_gestion') === 0) {
                $sql .= " AND hg.id_gestion IS NULL ";
            } else {
                $sql .= " AND hg.id_gestion IS NOT NULL AND (
                    hg.resultado_contacto = ?
                    OR hg.razon_especifica = ?
                    OR hg.resultado_contacto LIKE ?
                    OR hg.razon_especifica LIKE ?
                ) ";
                $like = '%' . $tipEsp . '%';
                array_push($params, $tipEsp, $tipEsp, $like, $like);
            }
        }

        if ($contacto !== '' && strcasecmp($contacto, 'todos') !== 0) {
            $sql .= " AND hg.id_gestion IS NOT NULL AND (hg.forma_contacto = ? OR hg.tipo_contacto LIKE ?) ";
            $params[] = $contacto;
            $params[] = '%' . $contacto . '%';
        }

        if ($tipificacion !== '' && strcasecmp($tipificacion, 'todos') !== 0) {
            $sql .= " AND hg.id_gestion IS NOT NULL AND hg.resultado_contacto LIKE ? ";
            $params[] = '%' . $tipificacion . '%';
        }

        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $sql .= " AND (c.nombre LIKE ? OR c.cedula LIKE ? OR c.tel1 LIKE ? OR c.tel2 LIKE ? OR c.tel3 LIKE ?) ";
            array_push($params, $like, $like, $like, $like, $like);
        }

        $sql .= " ORDER BY (hg.id_gestion IS NULL) ASC, hg.fecha_creacion DESC, c.nombre ASC LIMIT 2000";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Observaciones de una gestión (historial_gestiones). Sin tabla aparte en emermedica_cobranza.
     */
    public function getObservacionesGestion($gestionId) {
        $id = (int)$gestionId;
        if ($id <= 0) {
            return ['comentarios' => '', 'proxima_fecha' => '', 'proxima_hora' => ''];
        }
        $stmt = $this->pdo->prepare("SELECT observaciones FROM historial_gestiones WHERE id_gestion = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $texto = (string)($row['observaciones'] ?? '');
        return [
            'comentarios' => $texto,
            'proxima_fecha' => '',
            'proxima_hora' => '',
        ];
    }

    public function getTotalGestionesByAsesorAndCliente($asesorId, $clienteId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) AS n
            FROM historial_gestiones
            WHERE asesor_cedula = ? AND cliente_id = ?
        ");
        $stmt->execute([(string)$asesorId, (int)$clienteId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);
    }

    public function crearGestionSimple($data) { return $this->crearGestion($data); }
}

