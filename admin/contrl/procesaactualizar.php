<?php
/**
 * procesaactualizar.php — Actualiza un registro existente en la base de datos desde el panel admin
 * ================================================================================================
 * Este script recibe los datos de un formulario de edición y realiza la actualización del registro correspondiente.
 * - Valida el token CSRF para evitar ataques de falsificación de petición.
 * - Valida que la tabla sea permitida y el id sea válido.
 * - Construye dinámicamente la consulta UPDATE según los campos recibidos.
 * - Normaliza y trata casos especiales: checkboxes, fechas, contraseñas, arrays de permisos/secciones.
 * - Si la tabla es 'educandos', recalcula la sección automáticamente según el año.
 * - Gestiona errores de base de datos y muestra mensajes claros al usuario.
 * - Redirige de vuelta al listado o al formulario de edición según el resultado.
 */
session_start(); // Inicia la sesión PHP para acceder a variables de sesión
include('../../inc/conexion_bd.php'); // Incluye la conexión a la base de datos

// Solo los administradores pueden ejecutar esta acción
requerirAdmin();

// URL de fallback inicial (por si ocurre un error antes de conocer la tabla válida)
$urlFallback = '?tabla=educandos&ordenar_por=id&direccion=ASC';

// Validar el token CSRF para evitar ataques de falsificación de petición
$token = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    setFlash('error', 'Error al actualizar: token CSRF inválido. Recarga e inténtalo de nuevo.');
    header("Location: {$urlFallback}");
    exit;
}

// Validar que la tabla recibida sea una de las permitidas
$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_GET['tabla'] ?? ''));
if (!in_array($tabla, TABLAS_PERMITIDAS, true)) {
    setFlash('error', 'Error al actualizar: tabla no permitida.');
    header("Location: {$urlFallback}");
    exit;
}

// Obtener el id del registro a actualizar (debe ser numérico)
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



session_start(); // Inicia sesión para acceder a $_SESSION
include('../../inc/conexion_bd.php'); // Conexión a la base de datos

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

// Construye la URL de vuelta al listado y la URL de edición para redirección según el resultado
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

// Función para redirigir a una URL (soporta headers y fallback JS)
// Función para redirigir a una URL (soporta headers y fallback JS si ya se enviaron headers)
$redirigir = static function (string $url): void {
    if (!headers_sent()) {
        header("Location: {$url}");
    } else {
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
    }
    exit;
};

// Función para mostrar mensajes de error de base de datos de forma amigable
// Función para mostrar mensajes de error de base de datos de forma amigable y comprensible para el usuario
$mensajeErrorDB = static function (string $operacion, int $errno, string $error): string {
    // Error de clave única duplicada
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
    // Error de clave foránea
    if ($errno === 1452) {
        return "No se pudo actualizar: uno de los datos relacionados no existe o no es válido.";
    }
    // Error de tipo de dato
    if (in_array($errno, [1264, 1292], true)) {
        return "No se pudo actualizar: revisa el formato de los datos introducidos (fecha, número o texto).";
    }
    // Error de campo obligatorio vacío
    if (in_array($errno, [1048, 1364], true)) {
        if (preg_match("/column '([^']+)'/i", $error, $coincidencia)) {
            $campo = ucfirst(str_replace('_', ' ', $coincidencia[1]));
            return "No se pudo actualizar: el campo {$campo} es obligatorio.";
        }
        return "No se pudo actualizar: faltan campos obligatorios.";
    }
    // Cualquier otro error
    return "No se pudo actualizar el registro. Si el problema continúa, contacta con administración.";
};

// Validar que el id sea mayor que cero
if ($id <= 0) {
    setFlash('error', 'No se pudo actualizar: identificador de registro no válido.');
    $redirigir($urlEditar);
}

// Validación especial para lista_espera: todos estos campos deben estar presentes y no vacíos
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

// Construir el array de asignaciones para el UPDATE dinámico
$asignaciones = [];
$tipos        = '';
$params       = [];

foreach ($_POST as $clave => $valor) {
    // Saltar campos especiales que no deben actualizarse
    if ($clave === 'csrf_token' || $clave === 'id') continue;
    // Si la contraseña viene vacía, no actualizarla (mantener la anterior)
    if ($clave === 'contraseña') {
        if ($valor === '' || $valor === null) {
            continue;
        }
        $valor = password_hash($valor, PASSWORD_DEFAULT);
    }
    // Corregir nombre de columna para año
    if ($clave === 'año') {
        $clave = 'anio';
    }
    // En educandos, la sección se calcula automáticamente, nunca se actualiza manualmente
    if ($tabla === 'educandos' && $clave === 'seccion') {
        continue;
    }
    // Si el campo es un array (checkboxes múltiples), convertir a string adecuado
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

// Si la tabla es educandos, recalcula la sección automáticamente según el año recibido
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

// Añadir el id al final del bind_param para el WHERE id = ?
$tipos   .= 'i';
$params[] = $id;

// Si no hay ningún campo para actualizar, mostrar error y volver al formulario
if (empty($asignaciones)) {
    setFlash('error', 'No se pudo actualizar: no hay cambios para guardar.');
    $redirigir($urlEditar);
}

$huboError = false;

try {
    // Construir la consulta UPDATE con placeholders y ejecutarla de forma segura
    $sql  = "UPDATE `{$tabla}` SET " . implode(', ', $asignaciones) . " WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        setFlash('error', 'No se pudo actualizar: error interno al preparar la operación.');
        $redirigir($urlEditar);
    }
    // bind_param requiere referencias, por eso se usa un array de referencias
    $bindParams = [$tipos];
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    // Ejecutar la consulta y comprobar si hubo error
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

// Redirigir al listado o al formulario de edición según si hubo error
$redirigir($huboError ? $urlEditar : $urlVuelta);
?>
