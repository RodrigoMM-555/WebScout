<?php
/**
 * procesainsertar.php — Inserta un nuevo registro en la tabla indicada
 * =====================================================================
 * Este script procesa los formularios de alta del panel admin.
 * - Lee dinámicamente los campos enviados por POST.
 * - Adapta formatos según el tipo de dato (fechas, booleanos, contraseñas, permisos/secciones).
 * - Valida el token CSRF y la tabla permitida.
 * - Construye y ejecuta el INSERT sobre la tabla activa usando prepared statements.
 * - Gestiona reglas de negocio específicas (educandos, usuarios, avisos).
 * - Construye mensajes de error/éxito y redirige al listado correspondiente.
 */
session_start(); // Inicia la sesión PHP para acceder a variables de sesión
include('../../inc/conexion_bd.php'); // Incluye la conexión a la base de datos

// Solo los administradores pueden ejecutar esta acción de inserción
requerirAdmin();

// URL de fallback inicial (por si ocurre un error antes de conocer la tabla válida)
$urlFallback = '?tabla=educandos&ordenar_por=id&direccion=ASC';

// Validar el token CSRF para evitar ataques de falsificación de petición
$token = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    setFlash('error', 'Error al insertar: token CSRF inválido. Recarga e inténtalo de nuevo.');
    header("Location: {$urlFallback}");
    exit;
}

// Validar que la tabla recibida sea una de las permitidas
$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_GET['tabla'] ?? ''));
if (!in_array($tabla, TABLAS_PERMITIDAS, true)) {
    setFlash('error', 'Error al insertar: tabla no permitida.');
    header("Location: {$urlFallback}");
    exit;
}

// Construye la URL de vuelta al listado y la URL de inserción para redirección según el resultado
$urlVuelta = "?tabla=" . urlencode($tabla)
          . "&seccion=" . urlencode($_GET['seccion'] ?? '')
          . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
          . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC');

$urlInsertar = "?operacion=insertar"
             . "&tabla=" . urlencode($tabla)
             . "&seccion=" . urlencode($_GET['seccion'] ?? '')
             . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
             . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC');

// Función para redirigir a una URL (soporta headers y fallback JS si ya se enviaron headers)
$redirigir = static function (string $url): void {
    if (!headers_sent()) {
        header("Location: {$url}");
    } else {
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
    }
    exit;
};

// Función para mostrar mensajes de error de base de datos de forma amigable y comprensible para el usuario
$mensajeErrorDB = static function (string $operacion, int $errno, string $error): string {
    // Error de clave única duplicada
    if ($errno === 1062) {
        return "Error al {$operacion}: ya existe un registro con ese valor único (duplicado).";
    }
    // Error de clave foránea
    if ($errno === 1452) {
        return "Error al {$operacion}: referencia no válida en un campo relacionado (clave foránea).";
    }
    // Error de tipo de dato
    if (in_array($errno, [1264, 1292], true)) {
        return "Error al {$operacion}: uno de los valores tiene formato inválido.";
    }
    // Error de campo obligatorio vacío
    if (in_array($errno, [1048, 1364], true)) {
        if (preg_match("/column '([^']+)'/i", $error, $coincidencia)) {
            $campo = ucfirst(str_replace('_', ' ', $coincidencia[1]));
            return "Error al {$operacion}: falta un campo obligatorio ({$campo}).";
        }
        return "Error al {$operacion}: faltan campos obligatorios.";
    }
    // Cualquier otro error
    return "Error al {$operacion}: " . $error;
};

// ── Construir arrays de columnas y valores para el INSERT ──
$columnas = [];
$valores  = [];
$tipos    = '';   // string de tipos para bind_param
$params   = [];   // valores reales para bind_param

foreach ($_POST as $clave => $valor) {
    // Saltar el token CSRF (ya validado arriba)
    if ($clave === 'csrf_token') continue;
    // Si el campo es contraseña, hashearla antes de guardar
    if ($clave === 'contraseña') {
        $valor = password_hash($valor, PASSWORD_DEFAULT);
    }
    // Corregir nombre de columna año → anio
    if ($clave === 'año') {
        $clave = 'anio';
    }
    // En educandos, la sección se calcula automáticamente, nunca se inserta manualmente
    if ($tabla === 'educandos' && $clave === 'seccion') {
        continue;
    }
    // Regla de negocio: usuarios nuevos deben cambiar contraseña en primer acceso
    if ($tabla === 'usuarios' && $clave === 'cambio_contraseña') {
        $valor = '1';
    }
    // Si el campo es un array (checkboxes múltiples), convertir a string adecuado
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

// Validación especial para educandos: todos estos campos deben estar presentes y no vacíos
if ($tabla === 'educandos') {
    $camposObligatoriosEducando = [
        'nombre' => 'Nombre',
        'apellidos' => 'Apellidos',
        'anio' => 'Año',
        'id_usuario' => 'Madre/Padre'
    ];
    foreach ($camposObligatoriosEducando as $campo => $etiqueta) {
        $valorCampo = trim((string)($_POST[$campo] ?? ''));
        if ($valorCampo === '') {
            setFlash('error', "Error al insertar: el campo {$etiqueta} es obligatorio.");
            $redirigir($urlInsertar);
        }
    }
}

// Si la tabla es educandos, recalcula la sección automáticamente según el año recibido
if ($tabla === 'educandos') {
    $anioRecibido = $_POST['anio'] ?? $_POST['año'] ?? null;
    if (is_numeric($anioRecibido)) {
        $seccionCalculada = calcularSeccionScoutPorAnio((int)$anioRecibido);
        if ($seccionCalculada !== null) {
            $indiceSeccion = array_search('`seccion`', $columnas, true);
            if ($indiceSeccion !== false) {
                $params[$indiceSeccion] = $seccionCalculada;
            } else {
                $columnas[] = '`seccion`';
                $valores[] = '?';
                $tipos .= 's';
                $params[] = $seccionCalculada;
            }
        }
    }
}

// Regla de negocio: al crear usuarios se fuerza el cambio de contraseña en el primer acceso
if ($tabla === 'usuarios' && !in_array('`cambio_contraseña`', $columnas, true)) {
    $columnas[] = '`cambio_contraseña`';
    $valores[] = '?';
    $tipos .= 's';
    $params[] = '1';
}

try {
    // Construir la consulta SQL con placeholders y ejecutarla de forma segura
    $sql = "INSERT INTO `{$tabla}` (" . implode(',', $columnas) . ") VALUES (" . implode(',', $valores) . ")";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        setFlash('error', 'Error al insertar: no se pudo preparar la consulta.');
        $redirigir($urlInsertar);
    }
    // bind_param requiere referencias, por eso se usa un array de referencias
    $bindParams = [$tipos];
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    // Ejecutar la consulta y comprobar si hubo error
    if (!$stmt->execute()) {
        setFlash('error', $mensajeErrorDB('insertar', (int)$stmt->errno, (string)$stmt->error));
        $stmt->close();
        $redirigir($urlInsertar);
    }
    $stmt->close();
} catch (Throwable $e) {
    setFlash('error', 'Error al insertar: ' . $e->getMessage());
    $redirigir($urlInsertar);
}

// ── Si se insertó un aviso, crear asistencias automáticas para los educandos de las secciones seleccionadas ──
if ($tabla === 'avisos') {
    $id_aviso = $conexion->insert_id; // Obtiene el id del aviso recién insertado
    if (isset($_POST['secciones']) && is_array($_POST['secciones'])) {
        $secciones = $_POST['secciones'];
        // Preparar placeholders para la cláusula IN (?)
        $placeholders = implode(',', array_fill(0, count($secciones), '?'));
        $tiposSec     = str_repeat('s', count($secciones));
        $sqlEdu = "SELECT id FROM educandos WHERE seccion IN ({$placeholders})";
        $stmtEdu = $conexion->prepare($sqlEdu);
        $stmtEdu->bind_param($tiposSec, ...$secciones);
        $stmtEdu->execute();
        $resEdu = $stmtEdu->get_result();
        // Preparar el INSERT de asistencia una sola vez (reutilizable en el bucle)
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

// Mensaje flash de éxito tras la inserción
setFlash('exito', 'Registro insertado correctamente.');

// Redirigir al listado tras la inserción exitosa
$redirigir($urlVuelta);
?>
