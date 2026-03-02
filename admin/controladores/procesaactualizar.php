<?php
/**
 * procesaactualizar.php — Actualiza un registro existente
 * =========================================================
 * Seguridad aplicada:
 *   - Requiere sesión de admin
 *   - Valida tabla contra whitelist
 *   - Usa prepared statements (bind_param)
 *   - Token CSRF validado
 *   - Contraseña vacía → se omite (no hashea cadena vacía)
 */
session_start();
include('../inc/conexion_bd.php');

// Solo admins pueden actualizar
requerirAdmin();

// Validar token CSRF
validarCSRF();

// Validar tabla e id
$tabla = validarTabla($_GET['tabla'] ?? '');
$id    = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    die('ID de registro no válido.');
}

// ── Construir asignaciones SET col=? ────────────────────────
$asignaciones = [];
$tipos        = '';
$params       = [];

foreach ($_POST as $clave => $valor) {

    // Saltar campos especiales
    if ($clave === 'csrf_token' || $clave === 'id') continue;

    // ★ FIX: Si la contraseña viene vacía, no actualizarla
    if ($clave === 'contraseña') {
        if ($valor === '' || $valor === null) {
            continue; // Omitir — conserva la contraseña actual
        }
        $valor = password_hash($valor, PASSWORD_DEFAULT);
    }

    // Corregir nombre de columna
    if ($clave === 'año') {
        $clave = 'anio';
    }

    // Arrays (secciones[] → cadena, permisos[] → suma de bits)
    if (is_array($valor)) {
        if ($clave === 'permisos') {
            $valor = (string)array_sum(array_map('intval', $valor));
        } else {
            $valor = implode(',', $valor);
        }
    }

    $asignaciones[] = "`{$clave}` = ?";
    $tipos         .= 's';
    $params[]       = ($valor === '' || $valor === null) ? null : $valor;
}

// Añadir el id al final del bind
$tipos   .= 'i';
$params[] = $id;

// Construir UPDATE con placeholders
$sql  = "UPDATE `{$tabla}` SET " . implode(', ', $asignaciones) . " WHERE id = ?";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error preparando consulta: " . $conexion->error);
}

// bind_param requiere referencias
$bindParams = [$tipos];
for ($i = 0; $i < count($params); $i++) {
    $bindParams[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);

if (!$stmt->execute()) {
    setFlash('error', 'Error al actualizar: ' . $stmt->error);
} else {
    setFlash('exito', 'Registro actualizado correctamente.');
}

$stmt->close();

// Redirección al listado
header("Location: ?tabla=" . urlencode($tabla)
     . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
     . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC'));
exit;
?>
