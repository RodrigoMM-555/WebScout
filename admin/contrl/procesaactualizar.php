<?php
/**
 * procesaactualizar.php — Actualiza un registro existente
 * =========================================================
 * Procesa el formulario de edición del panel admin.
 * Construye dinámicamente el UPDATE según los campos recibidos,
 * normaliza casos especiales (checkboxes, fechas, contraseñas) y guarda
 * cambios en la tabla seleccionada.
 * También prepara mensajes de resultado y redirección de vuelta al listado.
 */
session_start();
include('../../inc/conexion_bd.php');

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
          . "&seccion=" . urlencode($_GET['seccion'] ?? '')
          . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
          . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC');

$urlEditar = "?operacion=actualizar"
          . "&tabla=" . urlencode($tabla)
          . "&id=" . urlencode((string)$id)
          . "&seccion=" . urlencode($_GET['seccion'] ?? '')
          . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
          . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC');

$redirigir = static function (string $url): void {
    if (!headers_sent()) {
        header("Location: {$url}");
    } else {
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
    }
    exit;
};

$mensajeErrorDB = static function (string $operacion, int $errno, string $error): string {
    if ($errno === 1062) {
        if (preg_match("/for key '([^']+)'/i", $error, $coincidencia)) {
            $indice = $coincidencia[1];
            $partes = preg_split('/[._]/', $indice);
            $ultimo = end($partes);
            if ($ultimo !== false && $ultimo !== '') {
                $campo = ucfirst(str_replace('_', ' ', (string)$ultimo));
                return "No se pudo actualizar: ya existe otro registro con el mismo valor en {$campo}.";
            }
        }
        return "No se pudo actualizar: ya existe otro registro con un valor que debe ser único.";
    }

    if ($errno === 1452) {
        return "No se pudo actualizar: uno de los datos relacionados no existe o no es válido.";
    }

    if (in_array($errno, [1264, 1292], true)) {
        return "No se pudo actualizar: revisa el formato de los datos introducidos (fecha, número o texto).";
    }

    if (in_array($errno, [1048, 1364], true)) {
        if (preg_match("/column '([^']+)'/i", $error, $coincidencia)) {
            $campo = ucfirst(str_replace('_', ' ', $coincidencia[1]));
            return "No se pudo actualizar: el campo {$campo} es obligatorio.";
        }
        return "No se pudo actualizar: faltan campos obligatorios.";
    }

    return "No se pudo actualizar el registro. Si el problema continúa, contacta con administración.";
};

if ($id <= 0) {
    setFlash('error', 'No se pudo actualizar: identificador de registro no válido.');
    $redirigir($urlEditar);
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
            setFlash('error', 'No se pudo actualizar: faltan campos obligatorios en la lista de espera.');
            $redirigir($urlEditar);
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

    // Educandos: la sección se calcula automáticamente desde anio.
    if ($tabla === 'educandos' && $clave === 'seccion') {
        continue;
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

if ($tabla === 'educandos') {
    $anioRecibido = $_POST['anio'] ?? $_POST['año'] ?? null;
    if (is_numeric($anioRecibido)) {
        $seccionCalculada = calcularSeccionScoutPorAnio((int)$anioRecibido);
        if ($seccionCalculada !== null) {
            $asignaciones[] = "`seccion` = ?";
            $tipos .= 's';
            $params[] = $seccionCalculada;
        }
    }
}

// Añadir el id al final del bind
$tipos   .= 'i';
$params[] = $id;

if (empty($asignaciones)) {
    setFlash('error', 'No se pudo actualizar: no hay cambios para guardar.');
    $redirigir($urlEditar);
}

$huboError = false;

try {
    // Construir UPDATE con placeholders
    $sql  = "UPDATE `{$tabla}` SET " . implode(', ', $asignaciones) . " WHERE id = ?";
    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        setFlash('error', 'No se pudo actualizar: error interno al preparar la operación.');
        $redirigir($urlEditar);
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
    setFlash('error', 'No se pudo actualizar por un error inesperado. Inténtalo de nuevo.');
}

// Redirección al listado
$redirigir($huboError ? $urlEditar : $urlVuelta);
?>
