<?php
declare(strict_types=1);

/**
 * descargar_todo.php — Descarga masiva de documentos de educandos asistentes a un aviso
 * -------------------------------------------------------------------------------
 * Genera un ZIP con los documentos de todos los educandos asistentes a un aviso concreto.
 * Filtra por secciones, verifica permisos y estructura, y responde con el archivo ZIP.
 *
 * Recibe: GET 'id_aviso' (obligatorio), GET 'seccion' (opcional)
 * Devuelve: ZIP descargable o error
 */

require_once "../../inc/conexion_bd.php";
require_once "../../tools/utils.php";

$id_aviso = isset($_GET['id_aviso']) ? (int)$_GET['id_aviso'] : 0;
if ($id_aviso <= 0) {
    http_response_code(400);
    echo "ID de aviso inválido.";
    exit;
}

// Valida que se reciba un id_aviso válido por GET

// Obtener aviso
$stmt = $conexion->prepare("SELECT titulo, secciones FROM avisos WHERE id = ?");
$stmt->bind_param("i", $id_aviso);
$stmt->execute();
$resAviso = $stmt->get_result();
$aviso = $resAviso->fetch_assoc();
$stmt->close();

// Consulta el aviso y sus secciones asociadas

if (!$aviso) {
    http_response_code(404);
    echo "Aviso no encontrado.";
    exit;
}

// Si no existe el aviso, responde con error 404

$tituloAviso = $aviso['titulo'];
$secciones = array_values(array_unique(array_filter(array_map(function($v){return strtolower(trim($v));}, explode(',', (string)$aviso['secciones'])))));

// Normaliza y filtra las secciones asociadas al aviso

if (empty($secciones)) {
    http_response_code(404);
    echo "Aviso sin secciones.";
    exit;
}

// Si el aviso no tiene secciones, responde con error

$seccionFiltro = isset($_GET['seccion']) ? strtolower(trim($_GET['seccion'])) : null;
if ($seccionFiltro) {
    if (!in_array($seccionFiltro, $secciones, true)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        require_once '../../tools/utils.php';
        setFlash('error', 'Sección no válida.');
        $redir = '../verCirculares.php?id_aviso=' . $id_aviso;
        header('Location: ' . $redir);
        exit;
    }
    $secciones = [$seccionFiltro];
}

// Si se filtra por sección, valida que sea válida y restringe a esa sección

// Obtener educandos
$placeholders = implode(',', array_fill(0, count($secciones), '?'));
$tipos = str_repeat('s', count($secciones));

$stmt = $conexion->prepare("SELECT id, nombre, apellidos, LOWER(TRIM(seccion)) AS seccion FROM educandos WHERE LOWER(TRIM(seccion)) IN ($placeholders)");
$stmt->bind_param($tipos, ...$secciones);
$stmt->execute();
$resEdu = $stmt->get_result();

$educandos = [];
while ($fila = $resEdu->fetch_assoc()) $educandos[] = $fila;
$stmt->close();

// Consulta todos los educandos de las secciones seleccionadas

if (empty($educandos)) {
    http_response_code(404);
    echo "No hay educandos en estas secciones.";
    exit;
}

// Si no hay educandos en esas secciones, responde con error

// Filtrar asistentes
$asistentes = [];
$stmt = $conexion->prepare("SELECT asistencia FROM asistencias WHERE id_aviso = ? AND id_educando = ?");
foreach ($educandos as $edu) {
    $stmt->bind_param("ii", $id_aviso, $edu['id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res && $res['asistencia'] == 'si') $asistentes[] = $edu;
}
$stmt->close();

// Filtra solo los educandos que han asistido al aviso

$tituloLimpio = limpiarTexto($tituloAviso);
$curso = function_exists('obtenerCursoScoutActual') ? obtenerCursoScoutActual() : date('Y');
$rondaCarpeta = ($curso - 1) . '-' . $curso;

// Prepara nombres de carpetas y ronda scout para buscar documentos

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo "ZipArchive no disponible";
    exit;
}

// Verifica que la extensión ZipArchive esté disponible

$tmp = tempnam(sys_get_temp_dir(), "zip_");
unlink($tmp);
$tmp .= ".zip";

$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$total = 0;

// Crea un archivo temporal ZIP para almacenar los documentos

foreach ($asistentes as $edu) {
    $nombre = $edu["nombre"] . " " . $edu["apellidos"];
    $nombreCarpeta = limpiarTexto($nombre);
    $seccionCarpeta = preg_replace('/[^a-z0-9_\-]/', '', strtolower($edu["seccion"]));

    $prefijo = $tituloLimpio . "_" . $nombreCarpeta . "_" . $rondaCarpeta . ".";

    $ruta = BASE_PATH . "/circulares/educandos/$rondaCarpeta/$seccionCarpeta/$nombreCarpeta/";
    if (!is_dir($ruta)) continue;

    $archivos = array_filter(scandir($ruta), fn($a) =>
        $a !== "." && $a !== ".." && is_file($ruta . $a) && strpos($a, $prefijo) === 0
    );

    foreach ($archivos as $archivo) {
        $local = "$seccionCarpeta/$nombreCarpeta/$archivo";
        $zip->addFile($ruta . $archivo, $local);
        $total++;
    }
}

// Por cada asistente, busca los archivos que correspondan al aviso y los añade al ZIP

$zip->close();

if ($total === 0) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_once '../../tools/utils.php';
    setFlash('error', $seccionFiltro ? 'No hay archivos para descargar en la sección seleccionada.' : 'No hay archivos para descargar.');
    $redir = '../verCirculares.php?id_aviso=' . $id_aviso;
    header('Location: ' . $redir);
    unlink($tmp);
    exit;
}

// Si no se añadió ningún archivo, muestra mensaje de error y redirige

if (isset($_GET['seccion']) && count($secciones) === 1) {
    $nombreZip = "archivos_" . $tituloLimpio . "_" . $secciones[0] . "_" . $rondaCarpeta . ".zip";
} else {
    $nombreZip = "archivos_" . $tituloLimpio . "_" . $rondaCarpeta . ".zip";
}

// Prepara el nombre del archivo ZIP según si hay filtro de sección

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"$nombreZip\"");
header("Content-Length: " . filesize($tmp));

readfile($tmp);
unlink($tmp);
exit;

// Envía el archivo ZIP como descarga y elimina el temporal