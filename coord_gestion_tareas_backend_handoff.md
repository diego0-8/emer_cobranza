# Handoff backend — Sistema de Tareas (Coord → Asesor)

Fecha: 2026-07-23  
Fuente: `wcentro5`  
Alcance: **solo backend** (BD, modelo, controladores, rutas, contratos JSON). Sin HTML/CSS/JS.

Objetivo: replicar en otro proyecto la lógica de la pestaña **TAREAS** de `Coord_gestion.php` y cómo el asesor consume/gestiona esas tareas.

---

## 1. Regla de negocio central

1. **Acceso a base ≠ asignación de clientes.**  
   Primero el asesor debe tener fila activa en `asignacion_base_asesores`. Después se crean tareas con clientes específicos.
2. Toda tarea nueva escribe **dos capas**:
   - `tareas.clientes_asignados` / `obligaciones_asignadas` (JSON, resumen/compatibilidad)
   - `detalle_tareas` (fuente operativa: pendiente vs gestionado)
3. Clientes en tareas `pendiente` / `en progreso` se consideran **ocupados** para nuevas asignaciones (salvo filtros con checkbox de inclusión, o CSV).
4. Al guardar una gestión, el asesor marca `detalle_tareas.gestionado = 'si'`. Completar la tarea (`estado = completa`) lo hace el coordinador de forma manual.

---

## 2. Archivos fuente a copiar/adaptar

| Rol | Archivo | Qué aporta |
|-----|---------|------------|
| Modelo | `models/Tarea.php` | CRUD + detalle + ocupados |
| Coord | `controllers/CoordGestionController.php` | Crear/listar/completar/eliminar/filtros/CSV |
| Asesor | `controllers/AsesorGestionController.php` | Resumen, clientes pendientes, siguiente, marcar gestionado |
| Router | `index.php` | `$rutasCoordAjax` / `$rutasAsesorAjax` + switch |
| Schema | `wcentro.sql` | Tablas y FKs |

Dependencias colaterales (deben existir en destino):

- `cliente`, `base_clientes`, `obligaciones`, `usuarios`
- `asignacion_base_asesores`
- `historial_gestion` (filtros por tipificación + guardar gestión)
- Opcional: `carga_csv_tareas`, tablas de campaña (`Campana`)

---

## 3. Esquema mínimo

```sql
CREATE TABLE asignacion_base_asesores (
  id_base_asesor INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  base_id INT NOT NULL,
  asesor_cedula VARCHAR(10) NOT NULL,
  fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  estado ENUM('activa','inactiva') DEFAULT 'activa'
);

CREATE TABLE tareas (
  id_tarea INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre_tarea VARCHAR(100) NOT NULL,
  coordinador_cedula VARCHAR(10) NOT NULL,
  asesor_cedula VARCHAR(10) NOT NULL,
  estado ENUM('pendiente','en progreso','completa','cancelada') DEFAULT 'pendiente',
  clientes_asignados TEXT DEFAULT NULL,          -- JSON array de id_cliente
  obligaciones_asignadas TEXT DEFAULT NULL,     -- JSON array de operacion
  base_id INT NOT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_completa TIMESTAMP NULL DEFAULT NULL,
  KEY idx_tareas_asesor_estado (asesor_cedula, estado)
);

CREATE TABLE detalle_tareas (
  id_detalle INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_tarea INT NOT NULL,
  id_cliente INT NOT NULL,
  gestionado ENUM('si','no') DEFAULT 'no',
  KEY idx_tarea_cliente_pend (id_tarea, gestionado),
  CONSTRAINT fk_det_tarea FOREIGN KEY (id_tarea) REFERENCES tareas(id_tarea) ON DELETE CASCADE,
  CONSTRAINT fk_det_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE
);

CREATE TABLE carga_csv_tareas (
  id_carga INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  base_id INT NOT NULL,
  asesor_cedula VARCHAR(10) NOT NULL,
  coordinador_cedula VARCHAR(10) NOT NULL,
  nombre_archivo VARCHAR(255) DEFAULT NULL,
  cedulas_subidas INT NOT NULL DEFAULT 0,
  cedulas_encontradas INT NOT NULL DEFAULT 0,
  cedulas_no_encontradas INT NOT NULL DEFAULT 0,
  id_tarea INT DEFAULT NULL,
  fecha_carga TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_carga_csv_tarea FOREIGN KEY (id_tarea) REFERENCES tareas(id_tarea) ON DELETE SET NULL
);
```

Notas:

- Identidad de usuario = **cédula** (`usuarios.cedula`), no ID numérico.
- `historial_gestion.id_tarea` es NOT NULL → toda gestión necesita una tarea (formal o automática “Gestión libre”).
- Vista opcional `vista_tareas_asesor` existe en SQL legacy; el código vivo no la usa (usa `detalle_tareas`).

---

## 4. Modelo `Tarea` — API interna

Métodos a portar:

| Método | Comportamiento |
|--------|----------------|
| `crear($datos)` | INSERT estado=`pendiente`. Si no hay `nombre_tarea`, genera `Tarea {cedula} - Y-m-d H:i`. JSON-encode de arrays. |
| `obtenerPorCoordinador($cedula)` | Todas las tareas del coord + join base/usuarios. Decodifica JSON. |
| `obtenerPorAsesor($cedula)` | Solo `pendiente`/`en progreso` y base `activo`. |
| `obtenerClientesAsignados($baseId)` | Une JSON de tareas activas de la base → IDs únicos ocupados. |
| `insertarDetalleTareas($idTarea, $ids)` | Un INSERT por cliente con `gestionado='no'`. |
| `marcarClienteGestionado($idTarea, $idCliente)` | `UPDATE ... SET gestionado='si'`. |
| `actualizarEstado($id, $estado)` | Si `completa`, setea `fecha_completa=NOW()`. |

---

## 5. Endpoints coordinador

Auth: sesión + `usuario_rol === 'coordinador'`.  
Respuesta: `Content-Type: application/json`.  
Cédula: `$_SESSION['usuario_id'] ?? $_SESSION['usuario_cedula']`.

### 5.1 Lectura (prerrequisitos de la pestaña)

| Action | Método | Params | Respuesta clave |
|--------|--------|--------|-----------------|
| `obtener_bases` | `obtenerBases()` | — | Bases (front filtra activas) |
| `obtener_asesores_con_acceso` | `obtenerAsesoresAccesoBase()` | `GET base_id` | `{success, asesores[]}` |
| `obtener_clientes_disponibles` | `obtenerClientesDisponibles()` | `GET base_id` | `{clientes_disponibles, total_clientes, clientes_asignados}` |
| `obtener_tareas_coordinador` | `obtenerTareasCoordinador()` | — | `{asignaciones[], data[]}` |
| `obtener_valores_filtros` | `obtenerValoresFiltros()` | `GET base_id` | oficinas, años, conceptos, canales, nivel1/2 |
| `obtener_asignaciones_pendientes` | `obtenerAsignacionesPendientes()` | — | Solo estado `pendiente` |

Disponibles = `COUNT(cliente WHERE base_id)` − IDs en tareas activas.

### 5.2 Escritura — tres formas de crear tarea

#### A) Por cantidad

- Action: `crear_asignacion_clientes` (alias `asignar_clientes`)
- POST: `base_id`, `asesor_cedula`, `cantidad_clientes`, `nombre_tarea?`
- Flujo:
  1. Validar acceso activo en `asignacion_base_asesores`.
  2. Listar `id_cliente` de la base `ORDER BY id_cliente`.
  3. Excluir ocupados (`obtenerClientesAsignados`).
  4. `array_slice` de la cantidad pedida.
  5. Cargar `obligaciones.operacion` de esos clientes.
  6. `Tarea::crear` + `insertarDetalleTareas`.
- Error típico: asesor sin acceso / no hay suficientes disponibles.

#### B) Por filtros de obligaciones / tipificación

1. `POST aplicar_filtros_obligaciones`  
   - `base_id`, `asesor_cedula`, `filtros` (JSON), `incluir_en_tareas_pendientes?`  
   - SQL base: `obligaciones` ⨝ `cliente` WHERE `base_id`.  
   - Filtros: oficina, año_castigo, concepto_mes_actual, estado_proceso_juridico, total{operador,valor}, total_a_pagar{operador,valor}.  
   - Tipificación: `EXISTS (historial_gestion ... canal/nivel1/nivel2)`.  
   - Por defecto excluye clientes ocupados; con flag los incluye.  
   - Respuesta: `{clientes: [ids], total_clientes, total_obligaciones}`.

2. `POST crear_asignacion_clientes_filtrados`  
   - `base_id`, `asesor_cedula`, `clientes_ids` (JSON), `cantidad_asignar?`, `nombre_tarea?`  
   - Si `cantidad_asignar > 0` y menor al total → `array_slice`.  
   - Misma persistencia dual.

Ejemplo `filtros`:

```json
{
  "oficina": "BOGOTA",
  "total_a_pagar": {"operador": ">=", "valor": 500000},
  "canal_contacto": "llamada_saliente",
  "nivel1_tipo": "NO CONTACTO"
}
```

**Al replicar:** whitelist de operadores (`= > < >= <=`); revalidar que cada `cliente_id` pertenezca a la base.

#### C) Por CSV de cédulas

- Action: `crear_asignacion_clientes_csv`
- POST multipart: `base_id`, `asesor_cedula`, `archivo_csv`, `nombre_tarea?`
- Parser (`extraerCedulasDesdeCsv`): columna `cedula` o una por línea; unique.
- Resuelve solo cédulas de esa `base_id`.
- **No excluye** clientes ya en tareas activas (diferencia vs cantidad/filtros).
- Registra trazabilidad en `carga_csv_tareas` (si la tabla existe; no falla si no).
- Respuesta: `tarea_id`, `clientes_asignados`, `cedulas_csv`, `cedulas_encontradas`, `cedulas_no_encontradas`.

### 5.3 Completar / eliminar

| Action | POST | Efecto |
|--------|------|--------|
| `completar_tarea` / `completar_asignacion` | `tarea_id` | Solo dueño (`coordinador_cedula`). `estado=completa`, `fecha_completa=NOW()`. Libera clientes para nuevas asignaciones. |
| `eliminar_tarea` | `tarea_id` | `DELETE FROM tareas` (cascade a `detalle_tareas`). |

---

## 6. Endpoints asesor (cómo “le aparece” la tarea)

Auth: sesión + `usuario_rol === 'asesor'`.

### 6.1 Resumen de tareas (dashboard / lista de tareas)

- Action: `obtener_resumen_tareas` → `obtenerResumenTareas()`
- Fuente: `Tarea::obtenerPorAsesor` + conteos desde `detalle_tareas`.
- Solo muestra tareas con **≥ 1 pendiente** (`gestionado='no'`).
- Item de respuesta:

```json
{
  "tarea_id": 123,
  "base_nombre": "...",
  "fecha_asignacion": "23/07/2026",
  "total_clientes_asignados": 50,
  "clientes_gestionados": 12,
  "clientes_pendientes": 38,
  "porcentaje_progreso": 24,
  "estado": "pendiente"
}
```

### 6.2 Clientes pendientes de la tarea

- Action usada vía dashboard / filtros: `obtenerClientesAsignados()` / `obtener_clientes_filtrados`
- SQL operativo:

```sql
SELECT c.*
FROM detalle_tareas dt
INNER JOIN tareas t ON t.id_tarea = dt.id_tarea AND t.asesor_cedula = :asesor
INNER JOIN cliente c ON c.id_cliente = dt.id_cliente
INNER JOIN base_clientes bc ON bc.id_base = c.base_id AND bc.estado = 'activo'
INNER JOIN asignacion_base_asesores aba
  ON aba.base_id = c.base_id AND aba.asesor_cedula = :asesor AND aba.estado = 'activa'
WHERE dt.gestionado = 'no'
  AND t.estado IN ('pendiente', 'en progreso')
  AND c.estado = 'activo'
```

Filtros (`obtener_clientes_filtrados`): `gestionado=gestionado|no_gestionado`, contactado, fecha.

### 6.3 Siguiente cliente

- Action: `obtener_siguiente_cliente` GET `cliente_id`
- Dentro de la misma tarea activa del cliente actual:
  1. Siguiente `id_detalle` con `gestionado='no'`
  2. Si no hay, wrap al primer pendiente distinto del actual
  3. Si no quedan → `cliente: null`

### 6.4 Guardar gestión → marca gestionado

En `guardarGestion()` / `guardarGestionConDatos()`:

1. Validar acceso vía `asignacion_base_asesores` (base del cliente), **no** exige estar en tarea formal.
2. Resolver `id_tarea`:
   - `resolverTareaActivaParaCliente` (join `detalle_tareas`)
   - si no hay → `asegurarTareaGestionLibre` crea/reusa `"[AUTO] Gestión libre - Base {id}"`
3. Insertar `historial_gestion` con ese `id_tarea`.
4. `Tarea::marcarClienteGestionado($idTarea, $clienteId)`.

Efecto visible: el cliente desaparece de pendientes / deja de salir en “siguiente”.

---

## 7. Diagrama de flujo (backend)

```text
COORDINADOR
  [acceso base] asignacion_base_asesores (activa)
        │
        ▼
  crear tarea (cantidad | filtros | CSV)
        │
        ├─► tareas (JSON clientes/obligaciones, estado=pendiente)
        └─► detalle_tareas (N filas, gestionado=no)

ASESOR
  obtener_resumen_tareas ──► tareas activas con pendientes > 0
  listar clientes ─────────► detalle_tareas.gestionado='no'
  guardar_gestion ─────────► historial_gestion + gestionado='si'
  obtener_siguiente_cliente ► próximo pendiente misma tarea

COORDINADOR
  completar_tarea ─────────► estado=completa (libera IDs para reasignar)
```

---

## 8. Contratos JSON de referencia

### Crear por cantidad (éxito)

```json
{
  "success": true,
  "message": "Se asignaron 50 clientes exitosamente",
  "tarea_id": 123,
  "clientes_asignados": 50,
  "obligaciones_asignadas": 62
}
```

### Crear por CSV (éxito)

```json
{
  "success": true,
  "message": "Se asignaron 10 clientes desde el CSV.",
  "tarea_id": 123,
  "clientes_asignados": 10,
  "obligaciones_asignadas": 12,
  "cedulas_csv": 11,
  "cedulas_encontradas": 10,
  "cedulas_no_encontradas": 1
}
```

### Error genérico

```json
{ "success": false, "message": "..." }
```

---

## 9. Registro de rutas (`index.php`)

Incluir en `$rutasCoordAjax` y switch:

- `obtener_clientes_disponibles`
- `obtener_asesores_con_acceso`
- `crear_asignacion_clientes` / `asignar_clientes`
- `obtener_tareas_coordinador`
- `completar_tarea` / `completar_asignacion`
- `eliminar_tarea`
- `obtener_valores_filtros`
- `aplicar_filtros_obligaciones`
- `crear_asignacion_clientes_filtrados`
- `crear_asignacion_clientes_csv`
- `obtener_asignaciones_pendientes`

Incluir en `$rutasAsesorAjax` y switch:

- `obtener_resumen_tareas`
- `obtener_clientes_filtrados`
- `obtener_siguiente_cliente`
- `guardar_gestion`
- `obtener_estadisticas_asesor` (métricas que incluyen conteos de tarea)

---

## 10. Checklist de réplica backend

1. Crear tablas `tareas`, `detalle_tareas`, `asignacion_base_asesores` (+ opcional `carga_csv_tareas`).
2. Portar `models/Tarea.php` completo.
3. Portar métodos de tareas/filtros/CSV del `CoordGestionController`.
4. Portar métodos de resumen/clientes/siguiente/marcar gestionado del `AsesorGestionController`.
5. Registrar actions en el router con auth por rol.
6. Garantizar sesión con cédula y rol.
7. Probar:
   - crear por cantidad / filtros / CSV;
   - asesor ve resumen y pendientes;
   - guardar gestión marca `gestionado='si'`;
   - siguiente cliente no repite gestionados;
   - completar libera clientes;
   - eliminar hace cascade en detalle.

---

## 11. Decisiones / riesgos al portar

| Tema | Comportamiento actual | Recomendación |
|------|----------------------|---------------|
| Dual write JSON + detalle | Obligatorio | En destino nuevo, preferir `detalle_tareas` como fuente única a medio plazo |
| Estado `en progreso` | Existe en ENUM, casi no se setea | Definir transición o eliminar |
| Completar automático | No: aunque todos estén gestionados, sigue `pendiente` hasta completar manual | Documentar o automatizar |
| CSV vs ocupados | CSV no excluye ocupados | Unificar política |
| Operadores SQL en filtros | Concatenados | Whitelist |
| `clientes_ids` filtrados | Confía en el cliente HTTP | Revalidar en servidor |
| Transacciones | Crear + detalle no es atómico | Usar `BEGIN/COMMIT` |
| Gestión libre | Crea tarea AUTO si no hay formal | Replicar si el destino permite gestionar fuera de tarea |
| Campañas | Coord limita asesores vía `Campana` | Incluir migración si aplica alcance multi-campaña |

---

## 12. Mapa rápido “qué copiar del controlador”

### CoordGestionController (métodos)

- `obtenerClientesDisponibles`
- `crearAsignacionClientes`
- `obtenerTareasCoordinador`
- `completarTarea`
- `obtenerValoresFiltros`
- `aplicarFiltrosObligaciones`
- `crearAsignacionClientesFiltrados`
- `crearAsignacionClientesCsv`
- `extraerCedulasDesdeCsv` (privado)
- `registrarCargaCsvTarea` (privado)
- `eliminarTarea`
- `obtenerAsignacionesPendientes`
- (prerreq acceso) `obtenerAsesoresAccesoBase` / `guardarAccesoBase`

### AsesorGestionController (métodos)

- `obtenerResumenTareas`
- `obtenerClientesAsignados`
- `obtenerClientesFiltrados`
- `obtenerSiguienteCliente`
- `resolverTareaActivaParaCliente` (privado)
- `asegurarTareaGestionLibre` (privado)
- `guardarGestion` / `guardarGestionConDatos` (parte `marcarClienteGestionado`)
- `obtenerEstadisticasAsesor` (conteos ligados a tareas)

---

Fuente de verdad: `wcentro5` al 2026-07-23.  
Documento hermano con UI (histórico): `coord_gestion_tareas_handoff.md` — **no usar para réplica backend-only**.
