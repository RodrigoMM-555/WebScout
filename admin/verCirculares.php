    // --- INICIO BLOQUE PRINCIPAL DE PROCESAMIENTO DE AVISO Y SECCIONES ---
    // Se obtiene el aviso y se procesan las secciones asociadas para determinar los grupos de educandos
    // --- FIN BLOQUE PRINCIPAL DE PROCESAMIENTO DE AVISO Y SECCIONES ---
    // --- INICIO BLOQUE DE MENSAJES Y BOTONES DE DESCARGA ---
    // Se muestran los botones de descarga por sección y el botón de descarga total
    // --- FIN BLOQUE DE MENSAJES Y BOTONES DE DESCARGA ---
    // --- INICIO BLOQUE DE OBTENCIÓN DE EDUCANDOS ---
    // Se consulta la base de datos para obtener todos los educandos de las secciones seleccionadas
    // --- FIN BLOQUE DE OBTENCIÓN DE EDUCANDOS ---
    // --- INICIO BLOQUE DE FILTRADO DE ASISTENTES ---
    // Se recorre el array de educandos y se filtran solo los que tienen asistencia 'si' en el evento
    // --- FIN BLOQUE DE FILTRADO DE ASISTENTES ---
    // --- INICIO BLOQUE DE VISUALIZACIÓN DE DOCUMENTOS ---
    // Si hay asistentes, se recorre cada uno para mostrar sus documentos subidos
            // Se prepara el nombre de carpeta y la ruta de cada educando
                // Si la carpeta de documentos no existe, se omite este educando
                // Se obtiene la lista de archivos de la carpeta del educando
                // Se recorre cada archivo encontrado y se muestra como enlace de descarga
    // --- FIN BLOQUE DE VISUALIZACIÓN DE DOCUMENTOS ---
<?php
/**
 * verCirculares.php — Visualización y descarga de archivos de circulares por aviso
 * -------------------------------------------------------------------------------
 * Permite al administrador ver y descargar los archivos subidos por los educandos
 * para un aviso concreto, agrupando por sección y permitiendo descarga masiva.
 *
 * Recibe: GET 'id_aviso' (ID del aviso a consultar)
 * Devuelve: HTML con botones de descarga y listado de archivos subidos.
 */
?>
<main>
        <link rel="stylesheet" href="css/estilo.css">
        <?php
        // Conexión a la base de datos y utilidades
        include_once __DIR__ . "/../inc/conexion_bd.php";
        require_once "../tools/utils.php";

        // Validar parámetro obligatorio
        $id_aviso = isset($_GET['id_aviso']) ? (int)$_GET['id_aviso'] : 0;
        // Si no se especifica un id de aviso, se muestra mensaje de error y se detiene la ejecución
        if ($id_aviso <= 0) {
            echo "<p>Evento no especificado.</p>";
            exit;
        }

        // Obtener aviso y título
        $stmt = $conexion->prepare("SELECT titulo, secciones FROM avisos WHERE id = ?");
        $stmt->bind_param("i", $id_aviso);
        $stmt->execute();
        $resAviso = $stmt->get_result();
        $aviso = $resAviso->fetch_assoc();
        $stmt->close();

        $tituloAviso = $aviso['titulo'] ?? '';
        // Procesar secciones del aviso
        $secciones = array_values(array_unique(array_filter(array_map(function($v){return strtolower(trim($v));}, explode(',', (string)$aviso['secciones'])))));

        // Mostrar mensaje flash si existe
        // Si existe la función mostrarFlash, se muestra cualquier mensaje flash pendiente
        if (function_exists('mostrarFlash')) mostrarFlash();

        // Renderizar cabecera y botones de descarga
        echo "<div class='asi'>
        <h1>Archivos subidos de " . htmlspecialchars($tituloAviso) . "</h1>
                <ul class='descarga-lista'>";
        // Si hay más de una sección, se generan botones de descarga por sección y uno para todo
        if (count($secciones) > 1) {
            foreach ($secciones as $sec) {
                // Por cada sección, se crea un botón para descargar solo los archivos de esa sección
                $secLabel = ucfirst($sec);
                echo "<button class='descarga-btn " . htmlspecialchars($sec) . "' onclick='descargarSeccion(\"" . htmlspecialchars($sec) . "\")'>Descargar " . $secLabel . "</button>";
            }
            // Botón para descargar todos los archivos de todas las secciones
            echo "<button class='descarga-btn' onclick='descargarTodo()'>Descargar todo</button>";
        } else {
            // Si solo hay una sección, solo se muestra el botón de descarga total
            echo "<button class='descarga-btn' onclick='descargarTodo()'>Descargar todo</button>";
        }
        echo "<a class='volver-asistencia' href='#' onclick='history.back(); return false;'>Volver</a>
                </ul>
            </div>
            <section class='documentos-subidos-panel'>";

        // Obtener educandos asistentes a las secciones
        $educandos = [];
        // Si hay secciones válidas, se obtienen los educandos de esas secciones
        if (!empty($secciones)) {
            $placeholders = implode(',', array_fill(0, count($secciones), '?'));
            $tipos = str_repeat('s', count($secciones));
            $stmtEdu = $conexion->prepare("SELECT id, nombre, apellidos, LOWER(TRIM(seccion)) AS seccion FROM educandos WHERE LOWER(TRIM(seccion)) IN ($placeholders)");
            $stmtEdu->bind_param($tipos, ...$secciones);
            $stmtEdu->execute();
            $resEdu = $stmtEdu->get_result();
            while ($fila = $resEdu->fetch_assoc()) {
                // Se agrega cada educando encontrado al array
                $educandos[] = $fila;
            }
            $stmtEdu->close();
        }

        $asistentes = [];
        // Por cada educando, se comprueba si asistió al evento
        foreach ($educandos as $edu) {
            $stmtAsis = $conexion->prepare("SELECT asistencia FROM asistencias WHERE id_aviso = ? AND id_educando = ?");
            $stmtAsis->bind_param("ii", $id_aviso, $edu['id']);
            $stmtAsis->execute();
            $resAsis = $stmtAsis->get_result();
            $asis = $resAsis->fetch_assoc();
            // Si el educando tiene asistencia marcada como 'si', se añade al array de asistentes
            if ($asis && $asis['asistencia'] === 'si') {
                $asistentes[] = $edu;
            }
            $stmtAsis->close();
        }

        $tituloLimpioAviso = limpiarTexto($tituloAviso);
        $cursoScout = function_exists('obtenerCursoScoutActual') ? (int)obtenerCursoScoutActual() : (int)date('Y');
        $rondaCarpeta = ($cursoScout - 1) . '-' . $cursoScout;

        // Si no hay asistentes, se muestra mensaje. Si hay, se listan los documentos subidos por cada uno
        // Si no hay asistentes, se muestra un mensaje informativo
        if (empty($asistentes)) {
            echo "<p>No hay asistentes al evento.</p>";
        } else {
            echo "<div class='documentos-grid'>";
            // Por cada asistente, se prepara la ruta de su carpeta y se muestran sus archivos
            foreach ($asistentes as $edu) {
                // --- INICIO BLOQUE DE PREPARACIÓN DE RUTAS Y FILTRO ---
                $nombreCompleto = ($edu['nombre'] ?? '') . ' ' . ($edu['apellidos'] ?? '');
                $nombreCarpeta = limpiarTexto($nombreCompleto);
                $seccionCarpeta = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string)($edu['seccion'] ?? ''))));
                // Si la sección está vacía, se usa un nombre por defecto
                if ($seccionCarpeta === '') {
                    $seccionCarpeta = 'sin_seccion';
                }
                $prefijo = $tituloLimpioAviso . '_' . $nombreCarpeta . '_' . $rondaCarpeta .'.';
                $rutaDocumentosAbs = BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/' . $seccionCarpeta . '/' . $nombreCarpeta;
                $rutaDocumentosWeb = '../circulares/educandos/' . $rondaCarpeta . '/' . $seccionCarpeta . '/' . $nombreCarpeta;

                // Si la carpeta no existe, se omite este educando
                if (!is_dir($rutaDocumentosAbs)) continue;

                // Se listan los archivos que cumplen el prefijo y son archivos válidos
                $archivosListado = array_values(array_filter(scandir($rutaDocumentosAbs), function ($archivo) use ($rutaDocumentosAbs, $prefijo) {
                    return $archivo !== '.' && $archivo !== '..' && is_file($rutaDocumentosAbs . '/' . $archivo) && strpos($archivo, $prefijo) === 0;
                }));
                natcasesort($archivosListado);
                // --- FIN BLOQUE DE PREPARACIÓN DE RUTAS Y FILTRO ---

                // --- INICIO BLOQUE DE VISUALIZACIÓN DE ARCHIVOS ---
                foreach ($archivosListado as $archivo) {
                    // Se prepara la ruta absoluta y la URL web del archivo
                    $rutaAbs = $rutaDocumentosAbs . '/' . $archivo;
                    $urlArchivo = $rutaDocumentosWeb . '/' . rawurlencode($archivo);
                    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

                    echo "<article class='doc-card'>";
                    $previewMostrada = false;

                    // Si está disponible la extensión Imagick, se intenta mostrar una miniatura del archivo
                    if (class_exists('Imagick')) {
                        try {
                            $imagick = new Imagick();
                            // Si es PDF, se genera preview de la primera página
                            if ($extension === 'pdf') {
                                $imagick->setResolution(200, 200);
                                $imagick->readImage($rutaAbs . '[0]');
                            } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                                // Si es imagen, se genera preview directamente
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
                                // Se muestra la miniatura generada en base64
                                echo "<img class='doc-preview' src='data:image/jpeg;base64,{$base64}' alt='Preview de " . htmlspecialchars($archivo) . "'>";
                                $previewMostrada = true;
                            }
                            $imagick->clear();
                            $imagick->destroy();
                        } catch (Exception $e) {
                            // Si ocurre un error, no se muestra preview
                            $previewMostrada = false;
                        }
                    }

                    // Si no se pudo mostrar preview, se indica al usuario
                    if (!$previewMostrada) {
                        echo "<p class='doc-sin-preview'>Sin preview disponible.</p>";
                    }

                    // Se muestra el nombre del archivo y del educando
                    echo "<p class='doc-nombre' title='" . htmlspecialchars($archivo) . "'><strong>" . htmlspecialchars($archivo) . "</strong></p>";
                    echo "<p class='doc-nombre-edu'>" . htmlspecialchars($nombreCompleto) . "</p>";
                    // Enlace para abrir el archivo en nueva pestaña
                    echo "<a class='doc-link' href='" . htmlspecialchars($urlArchivo) . "' target='_blank'>Abrir archivo</a>";
                    echo "</article>";
                }
                // --- FIN BLOQUE DE VISUALIZACIÓN DE ARCHIVOS ---
            }
            echo "</div>";
        }
            // --- INICIO BLOQUE DE FUNCIONES JAVASCRIPT DE DESCARGA ---
            // Función para descargar todos los archivos del aviso actual
            // Función para descargar solo los archivos de una sección específica
            // --- FIN BLOQUE DE FUNCIONES JAVASCRIPT DE DESCARGA ---
        ?>
    </section>

    <script>
        function descargarTodo() {
            const titulo = <?php echo json_encode($tituloAviso); ?>;
            const idAviso = <?php echo (int)$id_aviso; ?>;

            if (!confirm('¿Descargar todos los archivos de "' + titulo + '"?')) return;

            window.location.href = 'controladores/descargar_todo.php?id_aviso=' + idAviso;
        }
       function descargarSeccion(seccion) {
            const titulo = <?php echo json_encode($tituloAviso); ?>;
            const idAviso = <?php echo (int)$id_aviso; ?>;

            if (!confirm('¿Descargar los archivos de la sección "' + seccion + '" de "' + titulo + '"?')) return;

            window.location.href = 'controladores/descargar_todo.php?id_aviso=' + idAviso + '&seccion=' + encodeURIComponent(seccion);
        }
    </script>
</main>
