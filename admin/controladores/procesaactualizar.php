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

// URL de fallback inicial (por si falla antes de conocer la tabla válida)
$urlFallback = '?tabla=educandos&ordenar_por=id&direccion=ASC';

// Validar token CSRF (sin cortar con die)
$token = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    setFlash('error', 'Error al actualizar: token CSRF inválido. Recarga e inténtalo de nuevo.');
    header("Location: {$urlFallback}");
    exit;
}

// Validar tabla e id (sin cortar con die)
$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_GET['tabla'] ?? ''));
if (!in_array($tabla, TABLAS_PERMITIDAS, true)) {
    setFlash('error', 'Error al actualizar: tabla no permitida.');
    header("Location: {$urlFallback}");
    exit;
}

$id    = (int)($_POST['id'] ?? 0);

$urlVuelta = "?tabla=" . urlencode($tabla)
          . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
          . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC');

$urlEditar = "?operacion=actualizar"
          . "&tabla=" . urlencode($tabla)
          . "&id=" . urlencode((string)$id)
          . "&seccion=" . urlencode($_GET['seccion'] ?? '')
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

if ($id <= 0) {
    setFlash('error', 'Error al actualizar: ID de registro no válido.');
    header("Location: {$urlEditar}");
    exit;
}

if ($tabla === 'lista_espera') {
    $camposObligatoriosLista = [
        'nombre_nino',
        'apellidos_nino',
        'fecha_nacimiento',
        'nombre_contacto',
        'telefono_contacto',
        'correo_contacto'
    ];

    foreach ($camposObligatoriosLista as $campoObligatorio) {
        $valorCampo = trim((string)($_POST[$campoObligatorio] ?? ''));
        if ($valorCampo === '') {
            setFlash('error', 'Error al actualizar: faltan campos obligatorios en la lista de espera.');
            header("Location: {$urlEditar}");
            exit;
        }
    }
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

if (empty($asignaciones)) {
    setFlash('error', 'Error al actualizar: no hay campos para actualizar.');
    header("Location: {$urlEditar}");
    exit;
}

$huboError = false;

try {
    // Construir UPDATE con placeholders
    $sql  = "UPDATE `{$tabla}` SET " . implode(', ', $asignaciones) . " WHERE id = ?";
    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        setFlash('error', 'Error al actualizar: no se pudo preparar la consulta.');
        header("Location: {$urlEditar}");
        exit;
    }

    // bind_param requiere referencias
    $bindParams = [$tipos];
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);

    if (!$stmt->execute()) {
        $huboError = true;
        setFlash('error', $mensajeErrorDB('actualizar', (int)$stmt->errno, (string)$stmt->error));
    } else {
        setFlash('exito', 'Registro actualizado correctamente.');
    }

    $stmt->close();
} catch (Throwable $e) {
    $huboError = true;
    setFlash('error', 'Error al actualizar: ' . $e->getMessage());
}

// Redirección al listado
header("Location: " . ($huboError ? $urlEditar : $urlVuelta));
exit;
?>
