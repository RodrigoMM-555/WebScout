<?php
/**
 * procesainsertar.php — Inserta un nuevo registro en la tabla indicada
 * =====================================================================
 * Procesa los formularios de alta del panel admin.
 * Lee dinámicamente los campos enviados, adapta formatos por tipo de dato
 * (fechas, booleanos, contraseñas, permisos/secciones) y ejecuta el INSERT
 * sobre la tabla activa.
 * Incluye construcción de mensajes de error/éxito y redirección al listado.
 */
session_start();
include('../../inc/conexion_bd.php');

// Solo admins pueden insertar
requerirAdmin();

// URL de fallback inicial (por si falla antes de conocer la tabla válida)
$urlFallback = '?tabla=educandos&ordenar_por=id&direccion=ASC';

// Validar token CSRF (sin cortar con die)
$token = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    setFlash('error', 'Error al insertar: token CSRF inválido. Recarga e inténtalo de nuevo.');
    header("Location: {$urlFallback}");
    exit;
}

// Validar tabla (sin cortar con die)
$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_GET['tabla'] ?? ''));
if (!in_array($tabla, TABLAS_PERMITIDAS, true)) {
    setFlash('error', 'Error al insertar: tabla no permitida.');
    header("Location: {$urlFallback}");
    exit;
}

$urlVuelta = "?tabla=" . urlencode($tabla)
          . "&seccion=" . urlencode($_GET['seccion'] ?? '')
          . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
          . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC');

$urlInsertar = "?operacion=insertar"
             . "&tabla=" . urlencode($tabla)
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
        return "Error al {$operacion}: ya existe un registro con ese valor único (duplicado).";
    }

    if ($errno === 1452) {
        return "Error al {$operacion}: referencia no válida en un campo relacionado (clave foránea).";
    }

    if (in_array($errno, [1264, 1292], true)) {
        return "Error al {$operacion}: uno de los valores tiene formato inválido.";
    }

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

    // Educandos: la sección se calcula automáticamente desde anio.
    if ($tabla === 'educandos' && $clave === 'seccion') {
        continue;
    }

    // Regla de negocio: usuarios nuevos deben cambiar contraseña en primer acceso.
    if ($tabla === 'usuarios' && $clave === 'cambio_contraseña') {
        $valor = '1';
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

// Regla de negocio: al crear usuarios se fuerza cambio de contraseña.
if ($tabla === 'usuarios' && !in_array('`cambio_contraseña`', $columnas, true)) {
    $columnas[] = '`cambio_contraseña`';
    $valores[] = '?';
    $tipos .= 's';
    $params[] = '1';
}

try {
    // Construir SQL con placeholders
    $sql = "INSERT INTO `{$tabla}` (" . implode(',', $columnas) . ") VALUES (" . implode(',', $valores) . ")";
    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        setFlash('error', 'Error al insertar: no se pudo preparar la consulta.');
        $redirigir($urlInsertar);
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
        $redirigir($urlInsertar);
    }

    $stmt->close();
} catch (Throwable $e) {
    setFlash('error', 'Error al insertar: ' . $e->getMessage());
    $redirigir($urlInsertar);
}

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
$redirigir($urlVuelta);
?>
