<?php
// Vista de detalle de un educando en admin.
// Incluye datos del educando, datos del tutor y previsualización de plantillas.
session_start();
?>
<main class='info-educandos-page'>
<link rel="stylesheet" href="css/estilo.css">
<?php
include "../inc/conexion_bd.php";

# Solo admins pueden ver esta página
requerirAdmin();

# Validar que se ha pasado un id_educando
if (!isset($_GET['id_educando'])) {
    echo "Educando no especificado";
    exit;
}
$id_educando = (int)$_GET['id_educando'];

# Obtener educando
$stmt = $conexion->prepare("SELECT * FROM educandos WHERE id = ?");
$stmt->bind_param("i", $id_educando);
$stmt->execute();
$resEducando = $stmt->get_result();
$educando = $resEducando->fetch_assoc();
$stmt->close();

if (!$educando) {
    echo "Educando no encontrado";
    exit;
}

# Obtener usuario asociado
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $educando['id_usuario']);
$stmt->execute();
$resUsuario = $stmt->get_result();
$usuario = $resUsuario->fetch_assoc();
$stmt->close();

// Compatibilidad: en algunos sitios el apellido está como 'apellido' y en otros como 'apellidos'.
$apellidoEducando = $educando['apellido'] ?? ($educando['apellidos'] ?? '');

// Cabecera de la vista
echo "<div class='asi'>
        <h1>Info: " . htmlspecialchars($educando['nombre']) . " " . htmlspecialchars($apellidoEducando) . "</h1>
        <a href='#' onclick='history.back(); return false;'>Volver</a>
      </div>";

echo "<div class='info-educandos-layout'>";

# Mostrar información
echo "<section class='info-izquierda'>";
echo "<h2>Información del Educando</h2>";
echo "<p><strong>Nombre:</strong> " . htmlspecialchars($educando['nombre']) . "</p>";
echo "<p><strong>Apellido:</strong> " . htmlspecialchars($apellidoEducando) . "</p>";
echo "<p><strong>Año:</strong> " . htmlspecialchars($educando['anio']) . "</p>";
echo "<p><strong>Sección:</strong> " . htmlspecialchars($educando['seccion']) . "</p>";
echo "<p><strong>DNI:</strong> " . htmlspecialchars($educando['dni']) . "</p>";

echo "<h2>Información de tutores</h2>";
echo "<h3>Tutor 1</h3>";
echo "<p><strong>Nombre:</strong> " . htmlspecialchars(trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''))) . "</p>";
echo "<p><strong>Email:</strong> " . htmlspecialchars($usuario['email'] ?? '') . "</p>";
echo "<p><strong>Teléfono:</strong> " . htmlspecialchars($usuario['telefono'] ?? '') . "</p>";
echo "<p><strong>Dirección:</strong> " . htmlspecialchars($usuario['direccion'] ?? '') . "</p>";

$nombre2    = trim($usuario['nombre2'] ?? '');
$apellidos2 = trim($usuario['apellidos2'] ?? '');
$email2     = trim($usuario['email2'] ?? '');
$telefono2  = trim($usuario['telefono2'] ?? '');
if ($nombre2 !== '' || $apellidos2 !== '') {
    echo "<h3>Tutor 2</h3>";
    echo "<p><strong>Nombre:</strong> " . htmlspecialchars(trim($nombre2 . ' ' . $apellidos2)) . "</p>";
    if ($email2 !== '') {
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email2) . "</p>";
    }
    if ($telefono2 !== '') {
        echo "<p><strong>Teléfono:</strong> " . htmlspecialchars($telefono2) . "</p>";
    }
}
echo "</section>";

// Bloque derecho: listado de archivos del educando con miniatura y enlace de apertura.
echo "<section class='documentos-derecha'>";
echo "<h2>Documentación del educando</h2>";

// Se construye el nombre de carpeta del niño con el mismo formato usado al subir archivos.
$nombreCarpetaEducando = limpiarTexto(trim(($educando['nombre'] ?? '') . ' ' . ($apellidoEducando ?? '')));

// Nueva estructura: ronda/seccion/educando.
// Se mantiene compatibilidad con la ruta antigua.
$cursoScout = function_exists('obtenerCursoScoutActual')
    ? (int)obtenerCursoScoutActual()
    : (int)date('Y');
$rondaCarpeta = ($cursoScout - 1) . '-' . $cursoScout;
$seccionCarpeta = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string)($educando['seccion'] ?? ''))));
if ($seccionCarpeta === '') {
    $seccionCarpeta = 'sin_seccion';
}

// Ruta absoluta para leer archivos del educando desde servidor, y ruta web para abrirlos.
$rutasPosibles = [
    [
        'abs' => BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/' . $seccionCarpeta . '/' . $nombreCarpetaEducando,
        'web' => '../circulares/educandos/' . rawurlencode($rondaCarpeta) . '/' . rawurlencode($seccionCarpeta) . '/' . rawurlencode($nombreCarpetaEducando),
    ],
    [
        'abs' => BASE_PATH . '/circulares/educandos/' . $nombreCarpetaEducando,
        'web' => '../circulares/educandos/' . rawurlencode($nombreCarpetaEducando),
    ],
];

$rutaDocumentosAbs = '';
$rutaDocumentosWeb = '';
foreach ($rutasPosibles as $rutaCandidata) {
    if (is_dir($rutaCandidata['abs'])) {
        $rutaDocumentosAbs = $rutaCandidata['abs'];
        $rutaDocumentosWeb = $rutaCandidata['web'];
        break;
    }
}

if (!is_dir($rutaDocumentosAbs)) {
    echo "<p>El educando aún no tiene carpeta de documentación.</p>";
} else {

    // Se filtran '.' y '..' y solo se dejan archivos reales (no carpetas).
    $archivos = array_values(array_filter(scandir($rutaDocumentosAbs), function ($archivo) use ($rutaDocumentosAbs) {
        return $archivo !== '.' && $archivo !== '..' && is_file($rutaDocumentosAbs . '/' . $archivo);
    }));

    // Orden alfabético natural (1,2,10 en orden humano).
    natcasesort($archivos);

    if (empty($archivos)) {
        echo "<p>No hay archivos en la carpeta del educando.</p>";
    } else {
        echo "<div class='documentos-grid'>";

        foreach ($archivos as $archivo) {

            $rutaAbs = $rutaDocumentosAbs . '/' . $archivo;
            $urlArchivo = $rutaDocumentosWeb . '/' . rawurlencode($archivo);
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

            echo "<article class='doc-card'>";
            echo "<p class='doc-nombre' title='" . htmlspecialchars($archivo) . "'><strong>" . htmlspecialchars($archivo) . "</strong></p>";

            $previewMostrada = false;

            // Previsualización opcional con Imagick (si la extensión está instalada).
            if (class_exists('Imagick')) {
                try {
                    $imagick = new Imagick();

                    // Para PDF se lee solo la primera página para miniatura.
                    if ($extension === 'pdf') {
                        // Mejor resolución para evitar miniaturas oscuras
                        $imagick->setResolution(200, 200);
                        $imagick->readImage($rutaAbs . '[0]');
                    } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                        // Para imágenes se lee el archivo completo directamente.
                        $imagick->readImage($rutaAbs);
                    }

                    if ($imagick->getNumberImages() > 0) {

                        $imagick->setIteratorIndex(0);

                        // Forzar espacio de color correcto
                        $imagick->transformImageColorspace(Imagick::COLORSPACE_SRGB);

                        // Fondo blanco para evitar negro por transparencia
                        $imagick->setImageBackgroundColor('white');

                        // Quitar canal alpha si existe
                        if ($imagick->getImageAlphaChannel()) {
                            $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                        }

                        // Aplanar capas correctamente
                        $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

                        // Redimensionar miniatura
                        $imagick->thumbnailImage(260, 0);

                        $imagick->setImageFormat('jpeg');

                        $base64 = base64_encode($imagick->getImageBlob());

                        echo "<img class='doc-preview' src='data:image/jpeg;base64,{$base64}' 
                            alt='Preview de " . htmlspecialchars($archivo) . "'>";

                        $previewMostrada = true;
                    }

                    // Liberar memoria explícitamente por cada archivo procesado.
                    $imagick->clear();
                    $imagick->destroy();

                } catch (Exception $e) {
                    $previewMostrada = false;
                }
            }

            if (!$previewMostrada) {
                echo "<p class='doc-sin-preview'>Sin preview disponible.</p>";
            }

            echo "<a class='doc-link' href='" . htmlspecialchars($urlArchivo) . "' target='_blank'>Abrir archivo</a>";
            echo "</article>";
        }

        echo "</div>";
    }
}

echo "</section>";
echo "</div>";
?>
</main>