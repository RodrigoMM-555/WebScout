<!-- Perfil de los hijos -->
<?php 
include("../inc/header.php");
include("../inc/conexion_bd.php");

requerirSesion();

// limpiarTexto() ya definida en utils.php (cargado por conexion_bd)

$subidaAviso = $_GET['subida_aviso'] ?? '';
$permisosCalc = isset($_GET['permisos_calc']) ? (int)$_GET['permisos_calc'] : null;


// Comprobamos que nos pasan el id por GET
if(!isset($_GET['id'])) {
    die("No se ha especificado un educando.");
}

// COnvertimos el id a entero por seguridad
$id_educando = intval($_GET['id']);
$idUsuarioSesion = (int)($_SESSION['id_usuario'] ?? 0);

// Consultamos la información del educando
$sql = "SELECT * FROM educandos WHERE id = ? AND id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_educando, $idUsuarioSesion);
$stmt->execute();
$resultado = $stmt->get_result();
$educando = $resultado->fetch_assoc();

if (!$educando) {
    die("No tienes permisos para ver este educando o no existe.");
}

// Determinar clase de color según sección
switch(strtolower($educando['seccion'])) {
    case 'colonia':
        $clase_color = 'colonia';
        break;
    case 'manada':
        $clase_color = 'manada';
        break;
    case 'tropa':
        $clase_color = 'tropa';
        break;
    case 'posta':
        $clase_color = 'posta';
        break;
    case 'rutas':
        $clase_color = 'rutas';
        break;
    default:
        $clase_color = 'otros';
}

// Nombre compelto del educando
$nombreCompleto = $educando['nombre'] . " " . $educando['apellidos'];

// Nombres de los archivos principales
$titulos = [
    1 => "1-Ficha de inscripción",
    2 => "2-Ficha sanitaria",
    3 => "3-Exclusión de responsabilidad",
    4 => "4-Autorización ausentarse de actividades"
];

// Datos de ruta para documentos del educando (estructura nueva + fallback antigua)
$nombreCarpeta = limpiarTexto($nombreCompleto);
$cursoScout = function_exists('obtenerCursoScoutActual')
    ? (int)obtenerCursoScoutActual()
    : (int)date('Y');
$rondaCarpeta = ($cursoScout - 1) . '-' . $cursoScout;
$seccionCarpeta = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string)($educando['seccion'] ?? ''))));
if ($seccionCarpeta === '') {
    $seccionCarpeta = 'sin_seccion';
}

$rutasPosibles = [
    [
        'abs' => BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/' . $seccionCarpeta . '/' . $nombreCarpeta,
        'web' => '../circulares/educandos/' . rawurlencode($rondaCarpeta) . '/' . rawurlencode($seccionCarpeta) . '/' . rawurlencode($nombreCarpeta),
    ],
    [
        'abs' => BASE_PATH . '/circulares/educandos/' . $nombreCarpeta,
        'web' => '../circulares/educandos/' . rawurlencode($nombreCarpeta),
    ],
];

?>

<main class="educandos-unificado">
    <div class="educandos-superior">
        <!-- Pintamos la informacion del educando -->
        <section class="izquierda <?=$clase_color?>">
            <h1><?=$educando['nombre']?> <?=$educando['apellidos']?></h1>
            <p><span data-i18n="seccion">Sección</span>: <?=$educando['seccion']?></p>
            <p><span data-i18n="anio">Año</span>: <?=$educando['anio']?></p>
            <p><span data-i18n="dni">DNI</span>: <?=$educando['dni']?></p>
            <button class="sin-icono-auto" type="button" onclick="window.location.href='perfil.php'">&larr; <span data-i18n="atras">Atrás</span></button>
        </section>

        <!-- Apartado de documentación principal -->
        <section class="derecha">
            <h1 data-i18n="documentacion">Documentación</h1>

            <article>
        <!-- Cada documento tiene un formulario para subir el archivo -->
        <div class="documentacion" id="doc1">
            <p data-i18n="ficha_inscripcion">1-Ficha de inscripción</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/1-Ficha de inscripción.pdf" target="_blank" data-i18n="descargar">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc1" title="Seleccionar archivo" data-i18n="elegir">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc1" type='file' name='archivo' required>
                <span class="archivo-nombre" data-i18n="sin_archivo">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='1-Ficha de inscripción'>
                <input class="btn-archivo btn-subir" type='submit' value='⬆️ Subir' data-i18n="subir">
            </form>
        </div>

        <div class="documentacion" id="doc2">
            <p>2-Ficha sanitaria</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/2-Ficha sanitaria menor edad.pdf" target="_blank">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc2" title="Seleccionar archivo">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc2" type='file' name='archivo' required>
                <span class="archivo-nombre">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='2-Ficha sanitaria'>
                <input class="btn-archivo btn-subir" type='submit' value='⬆️ Subir'>
            </form>
        </div>

        <div class="documentacion" id="doc3">
            <p>3-Exclusión de responsabilidad</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/4-Exclusión de responsabilidad.pdf" target="_blank">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc3" title="Seleccionar archivo">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc3" type='file' name='archivo' required>
                <span class="archivo-nombre">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='3-Exclusión de responsabilidad'>
                <input class="btn-archivo btn-subir" type='submit' value='⬆️ Subir'>
            </form>
        </div>

        <div class="documentacion" id="doc4">
            <p>4-Autorización ausentarse de actividades</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/5-Autorización ausentarse actividades.pdf" target="_blank">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc4" title="Seleccionar archivo">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc4" type='file' name='archivo' required>
                <span class="archivo-nombre">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='4-Autorización ausentarse de actividades'>
                <input class="btn-archivo btn-subir" type='submit' value='⬆️ Subir'>
            </form>
        </div>
            </article>
        </section>
    </div>

    <section class="documentos-subidos-panel">
        <h2>Archivos subidos</h2>
        <?php
        $rutaDocumentosAbs = '';
        $rutaDocumentosWeb = '';

        foreach ($rutasPosibles as $rutaCandidata) {
            if (is_dir($rutaCandidata['abs'])) {
                $rutaDocumentosAbs = $rutaCandidata['abs'];
                $rutaDocumentosWeb = $rutaCandidata['web'];
                break;
            }
        }

        if ($rutaDocumentosAbs === '') {
            echo "<p>Este educando aún no tiene archivos subidos.</p>";
        } else {
            $archivosListado = array_values(array_filter(scandir($rutaDocumentosAbs), function ($archivo) use ($rutaDocumentosAbs) {
                return $archivo !== '.' && $archivo !== '..' && is_file($rutaDocumentosAbs . '/' . $archivo);
            }));

            natcasesort($archivosListado);

            if (empty($archivosListado)) {
                echo "<p>No hay archivos en la carpeta del educando.</p>";
            } else {
                echo "<div class='documentos-grid'>";

                foreach ($archivosListado as $archivo) {
                    $rutaAbs = $rutaDocumentosAbs . '/' . $archivo;
                    $urlArchivo = $rutaDocumentosWeb . '/' . rawurlencode($archivo);
                    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

                    echo "<article class='doc-card'>";

                    $previewMostrada = false;

                    if (class_exists('Imagick')) {
                        try {
                            $imagick = new Imagick();

                            if ($extension === 'pdf') {
                                $imagick->setResolution(200, 200);
                                $imagick->readImage($rutaAbs . '[0]');
                            } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                                $imagick->readImage($rutaAbs);
                            }

                            if ($imagick->getNumberImages() > 0) {
                                $imagick->setIteratorIndex(0);
                                $imagick->transformImageColorspace(Imagick::COLORSPACE_SRGB);
                                $imagick->setImageBackgroundColor('white');

                                if ($imagick->getImageAlphaChannel()) {
                                    $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                                }

                                $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                                $imagick->thumbnailImage(260, 0);
                                $imagick->setImageFormat('jpeg');

                                $base64 = base64_encode($imagick->getImageBlob());
                                echo "<img class='doc-preview' src='data:image/jpeg;base64,{$base64}' alt='Preview de " . htmlspecialchars($archivo) . "'>";
                                $previewMostrada = true;
                            }

                            $imagick->clear();
                            $imagick->destroy();
                        } catch (Exception $e) {
                            $previewMostrada = false;
                        }
                    }

                    if (!$previewMostrada) {
                        echo "<p class='doc-sin-preview'>Sin preview disponible.</p>";
                    }

                    echo "<p class='doc-nombre' title='" . htmlspecialchars($archivo) . "'><strong>" . htmlspecialchars($archivo) . "</strong></p>";
                    echo "<a class='doc-link' href='" . htmlspecialchars($urlArchivo) . "' target='_blank'>Abrir archivo</a>";
                    echo "</article>";
                }

                echo "</div>";
            }
        }
        ?>
    </section>
</main>

<script>
// Muestra el nombre del archivo seleccionado junto a cada botón "Elegir".
document.querySelectorAll('.input-archivo-oculto').forEach(function(input) {
    input.addEventListener('change', function() {
        const nombre = this.files && this.files.length ? this.files[0].name : 'Sin archivo';
        const form = this.closest('form');
        const target = form ? form.querySelector('.archivo-nombre') : null;
        if (target) target.textContent = nombre;
    });
});

(function() {
    // Persistencia de scroll:
    // al enviar un formulario de subida se guarda posición,
    // al recargar la página se restaura para mantener contexto.
    const scrollKey = 'educandos_scroll_y';
    const savedScroll = sessionStorage.getItem(scrollKey);

    if (savedScroll !== null) {
        const targetY = parseInt(savedScroll, 10) || 0;
        // Doble RAF para esperar a que el layout esté completamente pintado.
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                window.scrollTo(0, targetY);
            });
        });
        sessionStorage.removeItem(scrollKey);
    }

    document.querySelectorAll('form.form-archivo').forEach(function(form) {
        form.addEventListener('submit', function() {
            sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
        });
    });
})();
</script>

<?php

// Comprobamos si la carpeta existe y listamos los archivos para marcar los documentos entregados
foreach ($rutasPosibles as $ruta) {
if (is_dir($ruta['abs'])) {

    // Recopilamos los archivos subidos a la ruta
    $archivos = array_diff(scandir($ruta['abs']), ['.', '..']);

    // Por cada titulo/archivo
    foreach ($titulos as $num => $titulo) {
        // Limpiamos el titulo y lo juntamos con el nombre
        $tituloLimpio = limpiarTexto($titulo);
        $prefijo = $tituloLimpio . '_' . $nombreCarpeta;

        // Buscamos el archivo igual al prefijo para marcar que si se ha entregado
        foreach ($archivos as $f) {
            if (strpos($f, $prefijo) === 0) {
                                // Marca visualmente el bloque de documento como "entregado".
                echo "<script>
                        document.getElementById('doc$num').classList.add('entregado');
                      </script>";
                break;
            }
        }
    }
}
}

include("../inc/footer.html");

if ($subidaAviso === 'ocr_conversion_fallida') {
    echo "<script>alert('El PDF se ha subido correctamente, pero no se pudo convertir temporalmente para leer las casillas. Revisa los permisos manualmente.');</script>";
}

if ($subidaAviso === 'ocr_sin_educando') {
    echo "<script>alert('El PDF se ha subido, pero no se encontró el educando para actualizar permisos en la base de datos.');</script>";
}

if ($subidaAviso === 'ocr_columna_permisos_falta') {
    echo "<script>alert('El PDF se ha subido, pero falta la columna permisos en la tabla educandos. Ejecuta la migración SQL.');</script>";
}

if ($subidaAviso === 'ocr_update_preparacion_fallida' || $subidaAviso === 'ocr_update_fallido') {
    echo "<script>alert('El PDF se ha subido, pero falló la actualización de permisos en base de datos. Revisa logs del servidor.');</script>";
}

if ($permisosCalc !== null) {
    echo "<script>console.log('Permisos calculados automáticamente: ' + " . $permisosCalc . ");</script>";
}
?>
