<?php
/**
 * subearchivo.php — Gestiona la subida de archivos (documentos y circulares)
 * ===========================================================================
 * Recibe ficheros desde formularios de avisos y documentación de educandos,
 * valida el archivo, lo mueve a almacenamiento final y mantiene copia temporal
 * para procesado cuando aplica.
 * Para "1-Ficha de inscripción" convierte PDF a PNG y calcula permisos de
 * forma automática mediante análisis por coordenadas y densidad de píxeles.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("../../inc/conexion_bd.php");

// Solo usuarios autenticados pueden subir archivos
requerirSesion();

// ── Validación del archivo subido ───────────────────────────
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    die('Error al subir el archivo.');
}

// Tamaño máximo: 10 MB
$maxSize = 10 * 1024 * 1024;
if ($_FILES['archivo']['size'] > $maxSize) {
    die('El archivo excede el tamaño máximo permitido (10 MB).');
}

// Solo permitir PDF, JPG, PNG
$extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
$extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $extensionesPermitidas, true)) {
    die('Tipo de archivo no permitido. Solo se aceptan: ' . implode(', ', $extensionesPermitidas));
}

// limpiarTexto() ya está definida en utils.php (cargado por conexion_bd)

// Normaliza texto para comparaciones OCR (minúsculas y sin acentos).
function normalizarBusqueda($texto) {
    $texto = trim((string)$texto);
    $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    if ($tmp !== false) {
        $texto = $tmp;
    }
    return strtolower($texto);
}

// Busca el id del educando. Primero intenta nombre+apellidos exactos,
// y si no, compara contra el formato limpiado usado en carpetas.
function buscarIdEducando(mysqli $conexion, $nombreCompletoOriginal, $nombreEducandoLimpio) {
    $nombreCompletoOriginal = trim((string)$nombreCompletoOriginal);

    if ($nombreCompletoOriginal !== '') {
        $partes = preg_split('/\s+/', $nombreCompletoOriginal);
        $nombre = array_shift($partes) ?? '';
        $apellidos = trim(implode(' ', $partes));

        if ($nombre !== '' && $apellidos !== '') {
            $sql = "SELECT id FROM educandos WHERE nombre = ? AND apellidos = ? LIMIT 1";
            $stmt = $conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $nombre, $apellidos);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    return (int)$row['id'];
                }
            }
        }
    }

    $sql = "SELECT id, nombre, apellidos FROM educandos";
    $result = $conexion->query($sql);
    if (!$result) {
        return null;
    }

    while ($row = $result->fetch_assoc()) {
        $nombreDb = limpiarTexto(($row['nombre'] ?? '') . ' ' . ($row['apellidos'] ?? ''));
        if ($nombreDb === $nombreEducandoLimpio) {
            return (int)$row['id'];
        }
    }

    return null;
}

// Devuelve el nombre de carpeta de ronda en formato "YYYY-YYYY".
// Usa la lógica scout actual (curso), con fallback al año natural.
function obtenerCarpetaRondaActual() {
    $curso = function_exists('obtenerCursoScoutActual')
        ? (int)obtenerCursoScoutActual()
        : (int)date('Y');

    $inicio = $curso - 1;
    return $inicio . '-' . $curso;
}

// Comprueba si existe una columna concreta en una tabla.
// Se usa para no romper si la migración de 'permisos' no está aplicada.
function existeColumna(mysqli $conexion, $tabla, $columna) {
    $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
    $columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
    if ($tabla === '' || $columna === '') {
        return false;
    }

    try {
        $columnaEsc = $conexion->real_escape_string($columna);
        $sql = "SHOW COLUMNS FROM `{$tabla}` LIKE '{$columnaEsc}'";
        $res = $conexion->query($sql);
        if ($res === false) {
            error_log('Error comprobando columna ' . $tabla . '.' . $columna . ': ' . $conexion->error);
            return false;
        }
        return $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        error_log('Excepción comprobando columna ' . $tabla . '.' . $columna . ': ' . $e->getMessage());
        return false;
    }
}

// Movimiento robusto de ficheros: intenta rename y si falla (p.ej. distinto FS),
// hace copy+unlink.
function moverArchivoSeguro($origen, $destino) {
    if (@rename($origen, $destino)) {
        return true;
    }

    if (@copy($origen, $destino)) {
        @unlink($origen);
        return true;
    }

    return false;
}

// Convierte la primera página de un PDF a PNG a 300 DPI.
// También corrige orientación y escala para mantener proporción de la plantilla.
function convertirPdfAPng($rutaPdf, $rutaPng, &$error) {
    $error = '';

    if (!class_exists('Imagick')) {
        $error = 'La extensión Imagick no está disponible en el servidor.';
        return false;
    }

    try {
        $filtroLanczos = defined('Imagick::FILTER_LANCZOS') ? constant('Imagick::FILTER_LANCZOS') : 22;
        $claseImagickPixel = 'ImagickPixel';

        $pdfMeta = new Imagick();
        $pdfMeta->pingImage($rutaPdf . "[0]");
        $pdfWidthPt = (int)$pdfMeta->getImageWidth();
        $pdfHeightPt = (int)$pdfMeta->getImageHeight();
        $pdfMeta->clear();
        $pdfMeta->destroy();

        $imagen = new Imagick();
        $imagen->setResolution(300, 300);
        $imagen->readImage($rutaPdf . "[0]");

        // Importante: algunos PDFs con transparencias se rasterizan con fondo oscuro
        // en ciertas zonas. Aplanamos sobre blanco para evitar lecturas OCR falsas.
        $imagen->setImageBackgroundColor('white');
        if (method_exists($imagen, 'setImageAlphaChannel') && defined('Imagick::ALPHACHANNEL_REMOVE')) {
            $alphaRemove = constant('Imagick::ALPHACHANNEL_REMOVE');
            $imagen->setImageAlphaChannel($alphaRemove);
        }
        if (defined('Imagick::LAYERMETHOD_FLATTEN')) {
            $layerFlatten = constant('Imagick::LAYERMETHOD_FLATTEN');
            $imagen = $imagen->mergeImageLayers($layerFlatten);
        }

        $imagen->setImageFormat('png');

        $imgW = (int)$imagen->getImageWidth();
        $imgH = (int)$imagen->getImageHeight();

        $pdfEsHorizontal = $pdfWidthPt > $pdfHeightPt;
        $imgEsHorizontal = $imgW > $imgH;

        if ($pdfEsHorizontal !== $imgEsHorizontal) {
            if (class_exists($claseImagickPixel)) {
                $pixelBlanco = new $claseImagickPixel('white');
                $imagen->rotateImage($pixelBlanco, 90);
            }
        }

        if ($pdfWidthPt > 0 && $pdfHeightPt > 0) {
            $targetW = (int)round(($pdfWidthPt / 72) * 300);
            $targetH = (int)round(($pdfHeightPt / 72) * 300);
            if ($targetW > 0 && $targetH > 0) {
                $imagen->resizeImage($targetW, $targetH, $filtroLanczos, 1, false);
            }
        }

        $imagen->setImageCompressionQuality(100);
        $ok = $imagen->writeImage($rutaPng);
        $imagen->clear();
        $imagen->destroy();

        return (bool)$ok;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        return false;
    }
}


// Detecta si una región está marcada (escritura/tachado) analizando
// densidad de píxeles oscuros en el interior del recuadro.
function esRegionMarcada($imagen, $x, $y, $w, $h) {
    if ($w <= 0 || $h <= 0) {
        return false;
    }

    $crop = clone $imagen;
    $crop->cropImage($w, $h, $x, $y);
    $colorGray = defined('Imagick::COLORSPACE_GRAY') ? constant('Imagick::COLORSPACE_GRAY') : 2;
    $pixelChar = defined('Imagick::PIXEL_CHAR') ? constant('Imagick::PIXEL_CHAR') : 1;
    $crop->setImageColorspace($colorGray);

    $pixeles = $crop->exportImagePixels(0, 0, $w, $h, 'I', $pixelChar);
    $crop->clear();
    $crop->destroy();

    if (!is_array($pixeles) || count($pixeles) === 0) {
        return false;
    }

    // Se descarta borde exterior de la casilla (marco impreso), porque
    // aporta negro "fijo" incluso cuando la casilla está vacía.
    $margen = 4;
    if ($w > ($margen * 2) && $h > ($margen * 2)) {
        $pixelesInterior = [];
        for ($fila = $margen; $fila < $h - $margen; $fila++) {
            $inicio = $fila * $w + $margen;
            $longitud = $w - ($margen * 2);
            $pixelesInterior = array_merge($pixelesInterior, array_slice($pixeles, $inicio, $longitud));
        }
        if (!empty($pixelesInterior)) {
            $pixeles = $pixelesInterior;
        }
    }

    // Heurística de marcado:
    // - Pixel oscuro: intensidad < 150 (en escala 0..255)
    // - Casilla marcada: al menos 2% de oscuros en el área analizada
    $total = count($pixeles);
    $oscuros = 0;
    foreach ($pixeles as $v) {
        if ((int)$v < 150) {
            $oscuros++;
        }
    }

    $ratioOscuro = $oscuros / $total;
    return $ratioOscuro >= 0.02;
}

// Devuelve una puntuación continua de marcado para comparar "Sí" vs "No"
// y tolerar marcas parciales (no completamente rellenas).
function obtenerPuntuacionRegion($imagen, $x, $y, $w, $h) {
    if ($w <= 0 || $h <= 0) {
        return 0.0;
    }

    $crop = clone $imagen;
    $crop->cropImage($w, $h, $x, $y);
    $colorGray = defined('Imagick::COLORSPACE_GRAY') ? constant('Imagick::COLORSPACE_GRAY') : 2;
    $pixelChar = defined('Imagick::PIXEL_CHAR') ? constant('Imagick::PIXEL_CHAR') : 1;
    $crop->setImageColorspace($colorGray);

    $pixeles = $crop->exportImagePixels(0, 0, $w, $h, 'I', $pixelChar);
    $crop->clear();
    $crop->destroy();

    if (!is_array($pixeles) || count($pixeles) === 0) {
        return 0.0;
    }

    // Misma lógica de recorte interior que en esRegionMarcada(), pero aquí
    // no devolvemos bool: devolvemos un ratio continuo para comparar opciones.
    $margen = 4;
    if ($w > ($margen * 2) && $h > ($margen * 2)) {
        $pixelesInterior = [];
        for ($fila = $margen; $fila < $h - $margen; $fila++) {
            $inicio = $fila * $w + $margen;
            $longitud = $w - ($margen * 2);
            $pixelesInterior = array_merge($pixelesInterior, array_slice($pixeles, $inicio, $longitud));
        }
        if (!empty($pixelesInterior)) {
            $pixeles = $pixelesInterior;
        }
    }

    $total = count($pixeles);
    if ($total === 0) {
        return 0.0;
    }

    $oscuros = 0;
    foreach ($pixeles as $v) {
        if ((int)$v < 150) {
            $oscuros++;
        }
    }

    return $oscuros / $total;
}

function evaluarMapaPermisos($imagen, $imgW, $imgH, $escalaX, $escalaY, $wCaja, $hCaja, $mapa, $etiquetaMapa) {
    $permisos = 0;
    $extremos = 0;
    $contrasteTotal = 0.0;

    foreach ($mapa as $item) {
        // Conversión de coordenadas de plantilla base -> coordenadas reales
        // según escala de la imagen renderizada por Imagick.
        $xSi = (int)round($item['si']['x'] * $escalaX);
        $ySi = (int)round($item['si']['y'] * $escalaY);
        $xNo = (int)round($item['no']['x'] * $escalaX);
        $yNo = (int)round($item['no']['y'] * $escalaY);

        $xSi = max(0, min($xSi, $imgW - $wCaja));
        $ySi = max(0, min($ySi, $imgH - $hCaja));
        $xNo = max(0, min($xNo, $imgW - $wCaja));
        $yNo = max(0, min($yNo, $imgH - $hCaja));

        // Puntuaciones de densidad de marca para ambas casillas de la misma fila.
        $scoreSi = obtenerPuntuacionRegion($imagen, $xSi, $ySi, $wCaja, $hCaja);
        $scoreNo = obtenerPuntuacionRegion($imagen, $xNo, $yNo, $wCaja, $hCaja);

        // Umbrales más permisivos para aceptar marcas suaves/parciales.
        $siMarcado = ($scoreSi >= 0.008) && ($scoreSi > ($scoreNo + 0.0015));
        $noMarcado = ($scoreNo >= 0.008) && ($scoreNo > ($scoreSi + 0.0015));

        // Regla de negocio: solo se concede permiso cuando "Sí" gana claramente.
        if ($siMarcado && !$noMarcado) {
            $permisos += (int)$item['permiso'];
        }

        if ($scoreSi >= 0.98 && $scoreNo >= 0.98) {
            $extremos++;
        }

        $contrasteTotal += abs($scoreSi - $scoreNo);

        error_log('OCR[' . $etiquetaMapa . '] casilla permiso=' . (int)$item['permiso'] .
            ' score_si=' . round($scoreSi, 4) .
            ' score_no=' . round($scoreNo, 4) .
            ' si=' . ($siMarcado ? '1' : '0') .
            ' no=' . ($noMarcado ? '1' : '0'));
    }

    return [
        'permisos' => $permisos,
        'extremos' => $extremos,
        'contraste' => $contrasteTotal,
        'etiqueta' => $etiquetaMapa,
    ];
}

// Método principal de permisos:
// - Usa coordenadas fijas de la plantilla 1-Ficha de inscripción (base 300 DPI).
// - Escala automáticamente al tamaño real del PNG generado.
// - Solo suma permiso cuando Sí > No con margen de seguridad.
function calcularPermisosDesdeCoordenadasPlantilla($rutaPng) {
    if (!class_exists('Imagick')) {
        return 0;
    }

    try {
        $imagen = new Imagick($rutaPng);
    } catch (Throwable $e) {
        return 0;
    }

    $imgW = (int)$imagen->getImageWidth();
    $imgH = (int)$imagen->getImageHeight();

    if ($imgW <= 0 || $imgH <= 0) {
        $imagen->clear();
        $imagen->destroy();
        return 0;
    }

    // Resolución de referencia del PNG generado desde la plantilla (300 DPI, A4).
    $baseW = 2481;
    $baseH = 3508;

    $escalaX = $imgW / $baseW;
    $escalaY = $imgH / $baseH;

    // Tamaño real aproximado de casilla indicado: 16x16.
    $tamCajaBase = 16;
    $wCaja = max(10, (int)round($tamCajaBase * $escalaX));
    $hCaja = max(10, (int)round($tamCajaBase * $escalaY));

    // Coordenadas validadas sobre la salida real de Imagick (origen superior-izquierdo).
    // Cada bloque contiene 2 regiones por permiso: casilla "si" y casilla "no".
    $mapa = [
        [
            'permiso' => PERM_WHATSAPP,
            'si' => ['x' => 122, 'y' => 2321],
            'no' => ['x' => 122, 'y' => 2412],
        ],
        [
            'permiso' => PERM_SOLO,
            'si' => ['x' => 122, 'y' => 2648],
            'no' => ['x' => 122, 'y' => 2690],
        ],
        [
            'permiso' => PERM_FOTOS,
            'si' => ['x' => 122, 'y' => 2861],
            'no' => ['x' => 122, 'y' => 2951],
        ],
        [
            'permiso' => PERM_COCHE,
            'si' => ['x' => 122, 'y' => 3159],
            'no' => ['x' => 122, 'y' => 3202],
        ],
    ];

    $res = evaluarMapaPermisos($imagen, $imgW, $imgH, $escalaX, $escalaY, $wCaja, $hCaja, $mapa, 'MANUAL16');
    $permisos = (int)$res['permisos'];
    error_log('OCR mapa elegido=MANUAL16 contraste=' . round($res['contraste'], 4) . ' permisos=' . $permisos);

    $imagen->clear();
    $imagen->destroy();

    return $permisos;
}

// Método OCR antiguo por texto cercano (se mantiene por compatibilidad).
function calcularPermisosDesdeImagen($rutaPng) {
    if (!class_exists('Imagick')) {
        return 0;
    }

    $palabras = obtenerPalabrasTesseract($rutaPng);
    if (empty($palabras)) {
        return 0;
    }

    $mapa = [
        PERM_WHATSAPP => ['whatsapp', 'wassap', 'watsap', 'watsapp'],
        PERM_SOLO => ['solo', 'irse'],
        PERM_FOTOS => ['imagen', 'imagenes', 'fotografia', 'fotos'],
        PERM_COCHE => ['vehiculo', 'vehiculoprivado', 'coche', 'privado']
    ];

    try {
        $imagen = new Imagick($rutaPng);
    } catch (Throwable $e) {
        return 0;
    }

    $imgW = (int)$imagen->getImageWidth();
    $imgH = (int)$imagen->getImageHeight();
    $permisos = 0;

    foreach ($mapa as $valorPermiso => $claves) {
        $encontrada = null;

        foreach ($palabras as $p) {
            foreach ($claves as $clave) {
                if (strpos($p['texto_norm'], $clave) !== false) {
                    $encontrada = $p;
                    break 2;
                }
            }
        }

        if (!$encontrada) {
            continue;
        }

        $siToken = null;
        $noToken = null;
        $margenY = max(25, $encontrada['height'] * 2);

        foreach ($palabras as $p2) {
            if ($p2['left'] <= $encontrada['left']) {
                continue;
            }

            if (abs($p2['top'] - $encontrada['top']) > $margenY) {
                continue;
            }

            $txt = $p2['texto_norm'];
            if (($txt === 'si' || $txt === 'sí') && ($siToken === null || $p2['left'] < $siToken['left'])) {
                $siToken = $p2;
            }

            if ($txt === 'no' && ($noToken === null || $p2['left'] < $noToken['left'])) {
                $noToken = $p2;
            }
        }

        $siMarcado = false;
        $noMarcado = false;

        if ($siToken !== null) {
            $xSi = max(0, $siToken['left'] - 55);
            $ySi = max(0, $siToken['top'] - 10);
            $wSi = min(45, $imgW - $xSi);
            $hSi = min(max($siToken['height'] + 20, 35), $imgH - $ySi);
            $siMarcado = esRegionMarcada($imagen, $xSi, $ySi, $wSi, $hSi);
        }

        if ($noToken !== null) {
            $xNo = max(0, $noToken['left'] - 55);
            $yNo = max(0, $noToken['top'] - 10);
            $wNo = min(45, $imgW - $xNo);
            $hNo = min(max($noToken['height'] + 20, 35), $imgH - $yNo);
            $noMarcado = esRegionMarcada($imagen, $xNo, $yNo, $wNo, $hNo);
        }

        // Regla de negocio: vacío o ambiguo => NO.
        if ($siMarcado && !$noMarcado) {
            $permisos += $valorPermiso;
        }
    }

    $imagen->clear();
    $imagen->destroy();

    return $permisos;
}

// Validar que se recibió un archivo sin errores
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != 0) {
    die("Error: no se recibió ningún archivo o hubo un error en la subida.");
}

// Obtener información del archivo
$archivoTmp = $_FILES['archivo']['tmp_name'];
$nombreOriginal = $_FILES['archivo']['name'];
$extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

// Recoger datos, si no hay se asigna un valor por defecto
$nombreEducandoOriginal = $_POST['nombreCompleto'] ?? 'sin_nombre';
$tituloAvisoOriginal = $_POST['tituloAviso'] ?? 'sin_aviso';

// Limpiar los textos para evitar problemas con caracteres especiales
$nombreEducando = limpiarTexto($nombreEducandoOriginal);
$tituloAviso = limpiarTexto($tituloAvisoOriginal);

// Carpeta destino del archivo (ronda/seccion/educando)
$rondaCarpeta = obtenerCarpetaRondaActual();
$idEducandoRuta = buscarIdEducando($conexion, $nombreEducandoOriginal, $nombreEducando);
$seccionEducandoRuta = 'sin_seccion';

if ($idEducandoRuta !== null) {
    $stmtSec = $conexion->prepare("SELECT seccion FROM educandos WHERE id = ? LIMIT 1");
    if ($stmtSec) {
        $stmtSec->bind_param('i', $idEducandoRuta);
        $stmtSec->execute();
        $resSec = $stmtSec->get_result();
        if ($filaSec = $resSec->fetch_assoc()) {
            $sec = strtolower(trim((string)($filaSec['seccion'] ?? '')));
            $sec = preg_replace('/[^a-z0-9_\-]/', '', $sec);
            if ($sec !== '') {
                $seccionEducandoRuta = $sec;
            }
        }
        $stmtSec->close();
    }
}

$baseDir = BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/' . $seccionEducandoRuta . '/' . $nombreEducando;
$tmpDirPreferido = BASE_PATH . '/circulares/tmp';
$tmpDirAlternativo = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'webscout_uploads';
$tmpDir = '';

// Crear carpeta si no existe, conpermisos adecuados y mensaje de error
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0777, true)) {
        $err = error_get_last();
        die("Error: no se pudo crear la carpeta $baseDir. Motivo: " . ($err['message'] ?? 'desconocido'));
    }
}

foreach ([$tmpDirPreferido, $tmpDirAlternativo] as $dirCandidato) {
    if (!is_dir($dirCandidato)) {
        @mkdir($dirCandidato, 0777, true);
    }
    @chmod($dirCandidato, 0777);

    if (is_dir($dirCandidato) && is_writable($dirCandidato)) {
        $tmpDir = $dirCandidato;
        break;
    }
}

if ($tmpDir === '') {
    die("Error: no hay una carpeta temporal con permisos de escritura para subir archivos.");
}

// Flujo de guardado de documentos:
// 1) El archivo SIEMPRE entra primero a temporal.
// 2) Si es PDF de ficha 1, se crea PNG temporal para leer casillas.
// 3) El archivo final guardado para el usuario es el PDF original.
$nombreTemporal = uniqid('upload_', true) . '.' . $extension;
$rutaTemporal = $tmpDir . '/' . $nombreTemporal;

if (!move_uploaded_file($archivoTmp, $rutaTemporal)) {
    $err = error_get_last();
    die("Error al mover el archivo a la carpeta temporal. Carpeta usada: $tmpDir. Motivo: " . ($err['message'] ?? 'permiso denegado o ruta no válida'));
}

$esFichaInscripcion = (limpiarTexto($tituloAvisoOriginal) === limpiarTexto('1-Ficha de inscripción'));

$rutaFinal = '';
$permisosCalculados = null;
$avisoSubida = '';
if ($extension === 'pdf' && $esFichaInscripcion) {
    // Copia temporal PNG SOLO para leer casillas (no se entrega al usuario).
    $rutaPngTemporal = $tmpDir . '/' . uniqid('ocr_', true) . '.png';
    $errorConv = '';

    if (convertirPdfAPng($rutaTemporal, $rutaPngTemporal, $errorConv)) {
        // Cálculo determinista por coordenadas de plantilla.
        $permisosCalculados = calcularPermisosDesdeCoordenadasPlantilla($rutaPngTemporal);
        error_log('OCR permisos calculados=' . (int)$permisosCalculados . ' para educando=' . $nombreEducando);
        @unlink($rutaPngTemporal);
    } else {
        // Si falla OCR, se permite subida del PDF y se avisa al usuario.
        $permisosCalculados = 0;
        $avisoSubida = 'ocr_conversion_fallida';
        error_log('No se pudo generar PNG temporal para OCR de permisos: ' . $errorConv);
    }

    // Persistencia final: se guarda SIEMPRE el PDF original firmado/subido.
    // El PNG se usa únicamente como insumo técnico de análisis automático.
    $nuevoNombre = $tituloAviso . "_" . $nombreEducando . ".pdf";
    $rutaFinal = $baseDir . '/' . $nuevoNombre;
    if (!moverArchivoSeguro($rutaTemporal, $rutaFinal)) {
        @unlink($rutaTemporal);
        die("Error al mover el PDF temporal a la carpeta final.");
    }
} else {
    $nuevoNombre = $tituloAviso . "_" . $nombreEducando . "." . $extension;
    $rutaFinal = $baseDir . '/' . $nuevoNombre;
    if (!moverArchivoSeguro($rutaTemporal, $rutaFinal)) {
        @unlink($rutaTemporal);
        die("Error al mover el archivo temporal a la carpeta final.");
    }
}

if ($esFichaInscripcion && $permisosCalculados !== null) {
    // Persistencia de permisos en la BD.
    // Si algo falla, no se rompe la subida: se registra aviso + log.
    $idEducando = $idEducandoRuta ?? buscarIdEducando($conexion, $nombreEducandoOriginal, $nombreEducando);
    if ($idEducando === null) {
        $avisoSubida = $avisoSubida ?: 'ocr_sin_educando';
        error_log('No se encontró educando para actualizar permisos: ' . $nombreEducandoOriginal);
    } elseif (!existeColumna($conexion, 'educandos', 'permisos')) {
        $avisoSubida = $avisoSubida ?: 'ocr_columna_permisos_falta';
        error_log('La columna permisos no existe en educandos');
    } else {
        try {
            $stmtPerm = $conexion->prepare("UPDATE educandos SET permisos = ? WHERE id = ?");
            if ($stmtPerm) {
                $stmtPerm->bind_param("ii", $permisosCalculados, $idEducando);
                $stmtPerm->execute();
                error_log('Permisos actualizados: id=' . $idEducando . ' valor=' . (int)$permisosCalculados . ' filas=' . $stmtPerm->affected_rows);
            } else {
                $avisoSubida = $avisoSubida ?: 'ocr_update_preparacion_fallida';
            }
        } catch (Throwable $e) {
            $avisoSubida = $avisoSubida ?: 'ocr_update_fallido';
            error_log('No se pudieron guardar permisos automáticamente: ' . $e->getMessage());
        }
    }
}

// Redirección final. Se envían flags por querystring para mostrar avisos
// (fallo OCR, fallo update BD, etc.) y el valor calculado para depuración.
if (file_exists($rutaFinal)) {
    $origen = $_GET['ori'] ?? 'inicio';
    if ($origen == "educandos") {
        $idEducando = buscarIdEducando($conexion, $nombreEducandoOriginal, $nombreEducando);
        if ($idEducando !== null) {
            $origen = "educandos.php?id=" . $idEducando;
            if ($avisoSubida !== '') {
                $origen .= "&subida_aviso=" . urlencode($avisoSubida);
            }
            if ($permisosCalculados !== null) {
                $origen .= "&permisos_calc=" . (int)$permisosCalculados;
            }
            header("Location: ../$origen");
        } else {
            $origen = "inicio";
            header("Location: ../$origen.php");
        }
    }
    else {
        $url = "../$origen.php";
        if ($avisoSubida !== '') {
            $url .= "?subida_aviso=" . urlencode($avisoSubida);
            if ($permisosCalculados !== null) {
                $url .= "&permisos_calc=" . (int)$permisosCalculados;
            }
        }
        header("Location: $url");
    }

} else {
    echo "<strong>Error al subir el archivo</strong>";
}

?>