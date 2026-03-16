<main>
        <link rel="stylesheet" href="css/estilo.css">
        <?php
        include "../inc/conexion_bd.php";
        require_once "../tools/utils.php";

        $id_aviso = isset($_GET['id_aviso']) ? (int)$_GET['id_aviso'] : 0;
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
        $secciones = array_values(array_unique(array_filter(array_map(function($v){return strtolower(trim($v));}, explode(',', (string)$aviso['secciones'])))));

        // Mostrar mensaje flash si existe
        if (function_exists('mostrarFlash')) mostrarFlash();

        echo "<div class='asi'>
        <h1>Archivos subidos de " . htmlspecialchars($tituloAviso) . "</h1>
                <ul class='descarga-lista'>";
        if (count($secciones) > 1) {
            foreach ($secciones as $sec) {
                $secLabel = ucfirst($sec);
                echo "<button class='descarga-btn " . htmlspecialchars($sec) . "' onclick='descargarSeccion(\"" . htmlspecialchars($sec) . "\")'>Descargar " . $secLabel . "</button>";
            }
            echo "<button class='descarga-btn' onclick='descargarTodo()'>Descargar todo</button>";
        } else {
            echo "<button class='descarga-btn' onclick='descargarTodo()'>Descargar todo</button>";
        }
        echo "<a class='volver-asistencia' href='#' onclick='history.back(); return false;'>Volver</a>
                </ul>
            </div>
            <section class='documentos-subidos-panel'>";

        // Obtener educandos asistentes
        $educandos = [];
        if (!empty($secciones)) {
            $placeholders = implode(',', array_fill(0, count($secciones), '?'));
            $tipos = str_repeat('s', count($secciones));
            $stmtEdu = $conexion->prepare("SELECT id, nombre, apellidos, LOWER(TRIM(seccion)) AS seccion FROM educandos WHERE LOWER(TRIM(seccion)) IN ($placeholders)");
            $stmtEdu->bind_param($tipos, ...$secciones);
            $stmtEdu->execute();
            $resEdu = $stmtEdu->get_result();
            while ($fila = $resEdu->fetch_assoc()) {
                $educandos[] = $fila;
            }
            $stmtEdu->close();
        }

        $asistentes = [];
        foreach ($educandos as $edu) {
            $stmtAsis = $conexion->prepare("SELECT asistencia FROM asistencias WHERE id_aviso = ? AND id_educando = ?");
            $stmtAsis->bind_param("ii", $id_aviso, $edu['id']);
            $stmtAsis->execute();
            $resAsis = $stmtAsis->get_result();
            $asis = $resAsis->fetch_assoc();
            if ($asis && $asis['asistencia'] === 'si') {
                $asistentes[] = $edu;
            }
            $stmtAsis->close();
        }

        $tituloLimpioAviso = limpiarTexto($tituloAviso);
        $cursoScout = function_exists('obtenerCursoScoutActual') ? (int)obtenerCursoScoutActual() : (int)date('Y');
        $rondaCarpeta = ($cursoScout - 1) . '-' . $cursoScout;

        if (empty($asistentes)) {
            echo "<p>No hay asistentes al evento.</p>";
        } else {
            echo "<div class='documentos-grid'>";
            foreach ($asistentes as $edu) {
                $nombreCompleto = ($edu['nombre'] ?? '') . ' ' . ($edu['apellidos'] ?? '');
                $nombreCarpeta = limpiarTexto($nombreCompleto);
                $seccionCarpeta = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string)($edu['seccion'] ?? ''))));
                if ($seccionCarpeta === '') {
                    $seccionCarpeta = 'sin_seccion';
                }
                $prefijo = $tituloLimpioAviso . '_' . $nombreCarpeta . '_' . $rondaCarpeta .'.';
                $rutaDocumentosAbs = BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/' . $seccionCarpeta . '/' . $nombreCarpeta;
                $rutaDocumentosWeb = '../circulares/educandos/' . $rondaCarpeta . '/' . $seccionCarpeta . '/' . $nombreCarpeta;

                if (!is_dir($rutaDocumentosAbs)) continue;

                $archivosListado = array_values(array_filter(scandir($rutaDocumentosAbs), function ($archivo) use ($rutaDocumentosAbs, $prefijo) {
                    return $archivo !== '.' && $archivo !== '..' && is_file($rutaDocumentosAbs . '/' . $archivo) && strpos($archivo, $prefijo) === 0;
                }));
                natcasesort($archivosListado);

                foreach ($archivosListado as $archivo) {
                    $rutaAbs = $rutaDocumentosAbs . '/' . $archivo;
                    $urlArchivo = $rutaDocumentosWeb . '/' . rawurlencode($archivo);
                    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

                    echo "<article class='doc-card'>";
                    $previewMostrada = false;

                    // Preview con Imagick
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
                    echo "<p class='doc-nombre-edu'>" . htmlspecialchars($nombreCompleto) . "</p>";
                    echo "<a class='doc-link' href='" . htmlspecialchars($urlArchivo) . "' target='_blank'>Abrir archivo</a>";
                    echo "</article>";
                }
            }
            echo "</div>";
        }
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
