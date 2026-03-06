<?php
/**
 * procesainsertar.php — Inserta un nuevo registro en la tabla indicada
 * =====================================================================
 * Seguridad aplicada:
 *   - Requiere sesión de admin
 *   - Valida tabla contra whitelist
 *   - Usa prepared statements (bind_param)
 *   - Token CSRF validado
 *   - Un solo escape (corrige el doble real_escape_string anterior)
 */
session_start();
include('../inc/conexion_bd.php');

// Solo admins pueden insertar
requerirAdmin();

// Validar token CSRF
validarCSRF();

// Validar tabla
$tabla = validarTabla($_GET['tabla'] ?? '');

$urlVuelta = "?tabla=" . urlencode($tabla)
          . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
          . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC');

$mensajeErrorDB = static function (string $operacion, int $errno, string $error): string {
    if (in_array($errno, [1048, 1364], true)) {
        if (preg_match("/column '([^']+)'/i", $error, $coincidencia)) {
            $campo = ucfirst(str_replace('_', ' ', $coincidencia[1]));
            return "Error al {$operacion}: falta un campo obligatorio ({$campo}).";
        }
        return "Error al {$operacion}: faltan campos obligatorios.";
    }

    return "Error al {$operacion}: " . $error;
};

// ── Construir arrays de columnas y valores ──────────────────
$columnas = [];
$valores  = [];
$tipos    = '';   // string de tipos para bind_param
$params   = [];   // valores reales para bind_param

foreach ($_POST as $clave => $valor) {

    // Saltar el token CSRF (ya validado arriba)
    if ($clave === 'csrf_token') continue;

    // Hashear contraseña
    if ($clave === 'contraseña') {
        $valor = password_hash($valor, PASSWORD_DEFAULT);
    }

    // Corregir nombre de columna año → anio
    if ($clave === 'año') {
        $clave = 'anio';
    }

    // Si es array (caso de secciones[]), unir con comas
    if (is_array($valor)) {
        // Permisos: sumar los bits; secciones: unir con comas
        if ($clave === 'permisos') {
            $valor = (string)array_sum(array_map('intval', $valor));
        } else {
            $valor = implode(',', $valor);
        }
    }

    $columnas[] = "`{$clave}`";
    $valores[]  = '?';
    $tipos     .= 's'; // todo como string; MySQL convierte automáticamente
    $params[]   = ($valor === '' || $valor === null) ? null : $valor;
}

// Construir SQL con placeholders
$sql = "INSERT INTO `{$tabla}` (" . implode(',', $columnas) . ") VALUES (" . implode(',', $valores) . ")";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    setFlash('error', 'Error al insertar: no se pudo preparar la consulta.');
    header("Location: {$urlVuelta}");
    exit;
}

// bind_param requiere referencias
$bindParams = [$tipos];
for ($i = 0; $i < count($params); $i++) {
    $bindParams[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);

if (!$stmt->execute()) {
    setFlash('error', $mensajeErrorDB('insertar', (int)$stmt->errno, (string)$stmt->error));
    $stmt->close();
    header("Location: {$urlVuelta}");
    exit;
}

$stmt->close();

// ── Si se insertó un aviso, crear asistencias automáticas ───
if ($tabla === 'avisos') {
    $id_aviso = $conexion->insert_id;

    if (isset($_POST['secciones']) && is_array($_POST['secciones'])) {
        $secciones = $_POST['secciones'];

        // Preparar placeholders para IN (?)
        $placeholders = implode(',', array_fill(0, count($secciones), '?'));
        $tiposSec     = str_repeat('s', count($secciones));

        $sqlEdu = "SELECT id FROM educandos WHERE seccion IN ({$placeholders})";
        $stmtEdu = $conexion->prepare($sqlEdu);
        $stmtEdu->bind_param($tiposSec, ...$secciones);
        $stmtEdu->execute();
        $resEdu = $stmtEdu->get_result();

        // Preparar INSERT de asistencia una sola vez (reutilizable en el bucle)
        $stmtAsis = $conexion->prepare("INSERT INTO asistencias (id_aviso, id_educando) VALUES (?, ?)");

        while ($fila = $resEdu->fetch_assoc()) {
            $stmtAsis->bind_param("ii", $id_aviso, $fila['id']);
            if (!$stmtAsis->execute()) {
                error_log("Error insertando asistencia: " . $stmtAsis->error);
            }
        }

        $stmtAsis->close();
        $stmtEdu->close();
    }
}

// Mensaje flash de éxito
setFlash('exito', 'Registro insertado correctamente.');

// Redirección al listado
header("Location: {$urlVuelta}");
exit;
?>
