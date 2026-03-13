<?php
declare(strict_types=1);

require_once "../../inc/conexion_bd.php";
require_once "../../tools/utils.php";

$id_aviso = isset($_GET['id_aviso']) ? (int)$_GET['id_aviso'] : 0;
if ($id_aviso <= 0) {
    http_response_code(400);
    echo "ID de aviso inválido.";
    exit;
}

// Obtener aviso
$stmt = $conexion->prepare("SELECT titulo, secciones FROM avisos WHERE id = ?");
$stmt->bind_param("i", $id_aviso);
$stmt->execute();
$resAviso = $stmt->get_result();
$aviso = $resAviso->fetch_assoc();
$stmt->close();

if (!$aviso) {
    http_response_code(404);
    echo "Aviso no encontrado.";
    exit;
}

$tituloAviso = $aviso['titulo'];
$secciones = array_values(array_unique(array_filter(array_map(function($v){return strtolower(trim($v));}, explode(',', (string)$aviso['secciones'])))));

if (empty($secciones)) {
    http_response_code(404);
    echo "Aviso sin secciones.";
    exit;
}

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

if (empty($educandos)) {
    http_response_code(404);
    echo "No hay educandos en estas secciones.";
    exit;
}

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

if (empty($asistentes)) {
    http_response_code(404);
    echo "No hay asistentes.";
    exit;
}

$tituloLimpio = limpiarTexto($tituloAviso);
$curso = function_exists('obtenerCursoScoutActual') ? obtenerCursoScoutActual() : date('Y');
$rondaCarpeta = ($curso - 1) . '-' . $curso;

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo "ZipArchive no disponible";
    exit;
}

$tmp = tempnam(sys_get_temp_dir(), "zip_");
unlink($tmp);
$tmp .= ".zip";

$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$total = 0;

foreach ($asistentes as $edu) {
    $nombre = $edu["nombre"] . " " . $edu["apellidos"];
    $nombreCarpeta = limpiarTexto($nombre);
    $seccionCarpeta = preg_replace('/[^a-z0-9_\-]/', '', strtolower($edu["seccion"]));

    $prefijo = $tituloLimpio . "_" . $nombreCarpeta . ".";

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

$zip->close();

if ($total === 0) {
    unlink($tmp);
    echo "No hay archivos para descargar.";
    exit;
}

$nombreZip = "archivos_" . $tituloLimpio . "_" . $rondaCarpeta . ".zip";

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"$nombreZip\"");
header("Content-Length: " . filesize($tmp));

readfile($tmp);
unlink($tmp);
exit;