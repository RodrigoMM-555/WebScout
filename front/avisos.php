<!-- Avisos -->
<?php
session_start();
include("../inc/header.php");
include("../inc/conexion_bd.php");

// limpiarTexto() ya definida en utils.php (cargado por conexion_bd)
?>

<main class="avisos">

<!-- Seccion de avisos -->
<h1 data-i18n="avisos">Avisos</h1>

<?php
// Por si se entra sin sesion
if (!isset($_SESSION["id_usuario"])) {
    echo '<div class="sin-avisos">No se ha iniciado sesión.</div>';
    exit;
}

// Guardamos el id del usuario
$id_usuario = (int)$_SESSION["id_usuario"];

// Obtener educandos del usuario
$sql = "SELECT id, nombre, apellidos, seccion FROM educandos WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();

// Guardamos la infromacion de los educandos un una lista
$educandos = [];
while ($fila = $resultado->fetch_assoc()) {
    $educandos[] = $fila;
}

// Obtenemos los avisos de la base de datos
$fechaActual = new DateTime();
$fechaFormateada = $fechaActual->format("Y-m-d H:i:s");
// ★ FIX: Prepared statement en vez de interpolación de string (anti-SQLi)
$sql = "SELECT * FROM avisos WHERE fecha_hora_inicio >= ? ORDER BY fecha_hora_inicio";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $fechaFormateada);
$stmt->execute();
$resultado = $stmt->get_result();

$avisos_mostrados = [];
$hayAvisos = false;

// Recorremos los avisos
while ($aviso = $resultado->fetch_assoc()) {

    $seccionesAviso = array_filter(array_map(function ($seccion) {
        return strtolower(trim((string)$seccion));
    }, explode(',', (string)$aviso['secciones'])));

    // Lista de educandos que pertenecen a las secciones del aviso
    $lista_nombres = [];
    foreach ($educandos as $edu) {
        $seccionEducando = strtolower(trim((string)$edu['seccion']));

        // Compara las secciones del aviso con la sección del educando
        if ($seccionEducando !== '' && in_array($seccionEducando, $seccionesAviso, true)) {
            $lista_nombres[] = [
                "id" => $edu["id"],
                "nombreCompleto" => $edu['nombre'] . " " . $edu['apellidos']
            ];
        }
    }

    // Si hay educandos que deben recibir el aviso y aún no se ha mostrado
    if (!empty($lista_nombres) && !in_array($aviso["id"], $avisos_mostrados)) {

        // Informacion incial del aviso
        // Formateamos la fecha
        $fecha_inicio_formateada = date("d/m/Y H:i", strtotime($aviso["fecha_hora_inicio"]));
        if (!empty($aviso["fecha_hora_fin"])) {
            $fecha_fin_formateada = "- ". date("d/m/Y H:i", strtotime($aviso["fecha_hora_fin"]));
        } else {
            $fecha_fin_formateada = "";
        }

        // Información del aviso
        echo "<div class='aviso' id='aviso-" . (int)$aviso["id"] . "'>";
        echo "<h3>" . htmlspecialchars($aviso["titulo"]) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($aviso["contenido"])) . "</p>";

        // Fechas en bloque propio
        echo "<div class='aviso-fechas'>";
        echo "<span class='aviso-fecha-item'><strong data-i18n='inicio_fecha'>📅 Inicio</strong>$fecha_inicio_formateada</span>";
        if (!empty($fecha_fin_formateada)) {
            $fecha_fin_limpia = ltrim($fecha_fin_formateada, '- ');
            echo "<span class='aviso-fecha-item'><strong data-i18n='fin_fecha'>🏁 Fin</strong>$fecha_fin_limpia</span>";
        }
        echo "</div>";

        // Detalles del aviso en tarjeta con iconos
        $detalles = [];
        if (!empty($aviso["lugar"]))
            $detalles[] = "<span class='aviso-detalle'><strong data-i18n='lugar'>📍 Lugar</strong>" . htmlspecialchars($aviso["lugar"]) . "</span>";
        if (!empty($aviso["municipio"]))
            $detalles[] = "<span class='aviso-detalle'><strong data-i18n='municipio'>🏘️ Municipio</strong>" . htmlspecialchars($aviso["municipio"]) . "</span>";
        if (!empty($aviso["provincia"]))
            $detalles[] = "<span class='aviso-detalle'><strong data-i18n='provincia'>🗺️ Provincia</strong>" . htmlspecialchars($aviso["provincia"]) . "</span>";
        if (!empty($aviso["responsable"]))
            $detalles[] = "<span class='aviso-detalle'><strong data-i18n='responsable'>👤 Responsable</strong>" . htmlspecialchars($aviso["responsable"]) . "</span>";

        echo "<div class='aviso-detalles" . (empty($detalles) ? " solo-secciones" : "") . "'>";
        echo implode('', $detalles);
        // Secciones dentro de la misma tarjeta, en fila completa
        echo "<div class='aviso-secciones" . (empty($detalles) ? " sin-borde" : "") . "'>";
        echo "<strong data-i18n='secciones'>📋 Secciones</strong> ";
        foreach ($seccionesAviso as $sec) {
            echo "<span class='aviso-seccion-chip seccion-$sec'>" . ucfirst($sec) . "</span>";
        }
        echo "</div>";
        echo "</div>";

        // Si no hay circular
        if ($aviso["circular"] == "no") {

            echo "<table class='tabla-archivos tabla-sin-circular'>
                <tr>
                    <th data-i18n='nino'>Niñ@</th>
                    <th data-i18n='no_circular'>No hay circular adjunta</th> 
                    <th data-i18n='asistencia'>Asistencia</th>
                </tr>";

            foreach ($lista_nombres as $edu) {

                $id_educando = $edu["id"];
                $nombreCompleto = $edu["nombreCompleto"];

                // Comprobar asistencia del educando en la BBDD
                $stmtAsis = $conexion->prepare("SELECT asistencia FROM asistencias WHERE id_aviso = ? AND id_educando = ?");
                $stmtAsis->bind_param("ii", $aviso["id"], $id_educando);
                $stmtAsis->execute();
                $resAsis = $stmtAsis->get_result();
                $asis = $resAsis->num_rows > 0 ? $resAsis->fetch_assoc() : null;

                // Estado visual segun ele stado de la asistencia
                if (!$asis || $asis["asistencia"] === "pendiente") {
                    $filaClase = "tr-pendiente-gris"; // gris
                    $checkedSi = "";
                    $checkedNo = "";
                } elseif ($asis["asistencia"] === "si") {
                    $filaClase = "tr-entregado"; // verde
                    $checkedSi = "checked";
                    $checkedNo = "";
                } elseif ($asis["asistencia"] === "no") {
                    $filaClase = "tr-pendiente-gris"; // gris también
                    $checkedSi = "";
                    $checkedNo = "checked";
                }

                // Pintamos el formualrio de asistencia
                echo "<tr class='$filaClase'>
                    <td>$nombreCompleto</td>
                    <td>";
                    if ($asis && $asis["asistencia"] === "no") {
                        echo "<span data-i18n='no_asiste'>No asiste</span>";
                    } elseif ($asis && $asis["asistencia"] === "si") {
                        echo "<span data-i18n='si_asiste'>Sí asiste</span>";
                    } else {
                        echo "<span style='color: blue; font-weight:bold;' data-i18n='responder'>RESPONDER</span>";
                    }
                echo "</td>
                    <td>
                        <form class='form-asistencia' action='contrl/cambiar_asistencia.php' method='post'>
                            <input type='hidden' name='id_aviso' value='".$aviso["id"]."'>
                            <input type='hidden' name='id_educando' value='".$id_educando."'>
                            <input type='hidden' name='return_anchor' value='aviso-".(int)$aviso["id"]."'>
                            <label class='switch'>
                                <input class='asistencia-radio' type='radio' name='asiste' value='1' $checkedSi> Sí
                                <input class='asistencia-radio' type='radio' name='asiste' value='0' $checkedNo> No
                                <span class='slider'></span>
                            </label>
                        </form>
                    </td>
                </tr>";
            }

            echo "</table>";
        }
        // Si hay circualr
        if ($aviso["circular"] == "si") {
            echo "<table class='tabla-archivos tabla-con-circular'>
                <tr>
                    <th data-i18n='nino'>Niñ@</th>
                    <th data-i18n='nino'>Descargar archivo</th>
                    <th data-i18n='nino'>Subir archivo</th>
                    <th data-i18n='nino'>Entregado</th>
                    <th data-i18n='nino'>Asistencia</th>
                </tr>";

            foreach ($lista_nombres as $edu) {

                $id_educando = $edu["id"];
                $nombreCompleto = $edu["nombreCompleto"];

                // Comprobar asistencia
                $stmtAsis = $conexion->prepare("SELECT asistencia FROM asistencias WHERE id_aviso = ? AND id_educando = ?");
                $stmtAsis->bind_param("ii", $aviso["id"], $id_educando);
                $stmtAsis->execute();
                $resAsis = $stmtAsis->get_result();
                $asis = $resAsis->num_rows > 0 ? $resAsis->fetch_assoc() : null;

                // Determinar estado de asistencia y si puede subir archivo
                if (!$asis || $asis["asistencia"] === "pendiente" || $asis["asistencia"] === "no") {
                    $filaClase = "tr-pendiente-gris"; // gris
                    $puedeSubir = false;
                    $checkedSi = $checkedNo = "";
                    if ($asis && $asis["asistencia"] === "no") $checkedNo = "checked";
                } elseif ($asis["asistencia"] === "si") {
                    // Comprobar si entregó archivo
                    $nombreCarpeta = limpiarTexto($nombreCompleto);
                    $tituloLimpio  = limpiarTexto($aviso['titulo']);
                    $seccionCarpeta = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string)($edu['seccion'] ?? ''))));
                    if ($seccionCarpeta === '') {
                        $seccionCarpeta = 'sin_seccion';
                    }

                    $cursoScout = function_exists('obtenerCursoScoutActual')
                        ? (int)obtenerCursoScoutActual()
                        : (int)date('Y');
                    $rondaCarpeta = ($cursoScout - 1) . '-' . $cursoScout;

                    $rutasCandidatas = [
                        BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/' . $seccionCarpeta . '/' . $nombreCarpeta,
                        BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/sin_seccion/' . $nombreCarpeta,
                        BASE_PATH . '/circulares/educandos/' . $nombreCarpeta,
                    ];

                    // Si la seccion no coincide (cambio reciente o dato desfasado),
                    // buscar tambien en cualquier seccion de la ronda actual.
                    $globRonda = BASE_PATH . '/circulares/educandos/' . $rondaCarpeta . '/*/' . $nombreCarpeta;
                    $rutasRonda = glob($globRonda, GLOB_ONLYDIR);
                    if (is_array($rutasRonda) && !empty($rutasRonda)) {
                        $rutasCandidatas = array_merge($rutasCandidatas, $rutasRonda);
                    }

                    $rutasCandidatas = array_values(array_unique($rutasCandidatas));

                    $entregado = false;
                    // El archivo subido tiene el nombre: $tituloAviso . "_" . $nombreEducando . "_" . $rondaCarpeta . "." . $extension;
                    $prefijo = $tituloLimpio . '_' . $nombreCarpeta . '_' . $rondaCarpeta . '.';

                    foreach ($rutasCandidatas as $ruta) {
                        if (!is_dir($ruta)) {
                            continue;
                        }

                        $archivos = array_diff(scandir($ruta), ['.', '..']);
                        foreach ($archivos as $f) {
                            if (strpos($f, $prefijo) === 0) {
                                $entregado = true;
                                break 2;
                            }
                        }
                    }

                    $filaClase = $entregado ? "tr-entregado" : "tr-pendiente"; // verde o rojo
                    $puedeSubir = true;
                    $checkedSi = "checked";
                    $checkedNo = "";
                }

                // Pintamos lo que queda del formulario segun si puede subir o no el archivo, segun si va o no va
                echo "<tr class='$filaClase'>
                    <td>$nombreCompleto</td>";
                
                if ($puedeSubir) {
                    $inputId = "archivo_" . (int)$aviso["id"] . "_" . (int)$id_educando;
                    echo "
                        <td><a class='btn-archivo btn-descargar' href='../circulares/plantillas/6-Autorización participación actividad.pdf' target='_blank'>Descargar</a></td>
                        <td><form class='form-archivo' action='contrl/subearchivo.php?ori=avisos' method='post' enctype='multipart/form-data'>
                        <label class='btn-archivo btn-archivo-select-emoji' for='".$inputId."' title='Seleccionar archivo'>📎 Elegir</label>
                        <input class='input-archivo input-archivo-oculto' id='".$inputId."' type='file' name='archivo' required>
                        <span class='archivo-nombre'>Sin archivo</span>
                        <input type='hidden' name='nombreCompleto' value='".htmlspecialchars($nombreCompleto)."'>
                        <input type='hidden' name='tituloAviso' value='".htmlspecialchars($aviso['titulo'])."'>
                        <input class='btn-archivo btn-subir' type='submit' value='⬆️ Subir'>
                    </form></td>";
                } else {
                    echo "<td></td>
                    <td>";
                    if ($asis && $asis["asistencia"] === "no") {
                        echo "<span>No asiste</span>";
                    } else {
                        echo "<span style='color: blue; font-weight:bold;'>RESPONDER</span>";
                    }
                }

                echo "</td>
                    <td>";
                if ($puedeSubir) {
                    echo $entregado ? "<span style='color:green; font-weight:bold;'>Sí</span>" : "<span style='color:red; font-weight:bold;'>No</span>";
                } else {
                    echo "<span style='color:gray;'>-</span>";
                }
                echo "</td>
                    <td>
                        <form class='form-asistencia' action='contrl/cambiar_asistencia.php' method='post'>
                            <input type='hidden' name='id_aviso' value='".$aviso["id"]."'>
                            <input type='hidden' name='id_educando' value='".$id_educando."'>
                            <input type='hidden' name='return_anchor' value='aviso-".(int)$aviso["id"]."'>
                            <label class='switch'>
                                <input class='asistencia-radio' type='radio' name='asiste' value='1' $checkedSi> Sí
                                <input class='asistencia-radio' type='radio' name='asiste' value='0' $checkedNo> No
                                <span class='slider'></span>
                            </label>
                        </form>
                    </td>
                </tr>";
            }

            echo "</table>";
        }

        echo "</div>";

        $avisos_mostrados[] = $aviso["id"];
        $hayAvisos = true;
    }
}

// Si no hay avisos
if (!$hayAvisos) {
    echo "<div class='sin-avisos'>No hay avisos disponibles para tus hijos.</div>";
}
?>
</main>

<!--
    JavaScript de la vista de avisos
    ================================
    1) Muestra nombre del archivo elegido.
    2) Actualiza asistencia por AJAX sin recargar toda la página.
    3) Re-renderiza solo el aviso afectado para reflejar estado real del servidor.
    4) Conserva posición de scroll al subir documentos.
-->
<script>
// Enlaza inputs de archivo dentro de un scope (document completo o aviso reemplazado).
// Se usa un data-flag para no registrar listeners duplicados tras recargas parciales.
function enlazarInputsArchivo(scope) {
    scope.querySelectorAll('.input-archivo-oculto').forEach(function(input) {
        if (input.dataset.bindArchivo === '1') return;
        input.dataset.bindArchivo = '1';

        input.addEventListener('change', function() {
            const nombre = this.files && this.files.length ? this.files[0].name : 'Sin archivo';
            const form = this.closest('form');
            const target = form ? form.querySelector('.archivo-nombre') : null;
            if (target) target.textContent = nombre;
        });
    });
}

// Recarga únicamente el bloque #aviso-{id} desde el HTML servido por PHP.
// Esto evita recargar toda la página y mantiene la UI sincronizada con BD.
function recargarAviso(idAviso) {
    const avisoActual = document.getElementById('aviso-' + idAviso);
    if (!avisoActual) return Promise.resolve();

    return fetch(window.location.pathname + window.location.search, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        if (!response.ok) throw new Error('No se pudo recargar el aviso');
        return response.text();
    })
    .then(function(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const avisoNuevo = doc.getElementById('aviso-' + idAviso);

        if (!avisoNuevo) throw new Error('Aviso no encontrado en la respuesta');

        avisoActual.replaceWith(avisoNuevo);

        // Muy importante: al reemplazar nodos, se pierden listeners previos.
        // Por eso re-enlazamos los eventos en el nuevo bloque insertado.
        enlazarInputsArchivo(avisoNuevo);
        enlazarRadiosAsistencia(avisoNuevo);
    });
}

    // Enlaza radios "Sí/No" de asistencia.
    // Flujo:
    // - Dispara POST por fetch con ajax=1.
    // - Backend responde JSON.
    // - Se recarga solo el aviso afectado.
    // - Si algo falla, fallback a submit clásico para no bloquear al usuario.
function enlazarRadiosAsistencia(scope) {
    scope.querySelectorAll('form.form-asistencia .asistencia-radio').forEach(function(input) {
        if (input.dataset.bindAsistencia === '1') return;
        input.dataset.bindAsistencia = '1';

        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (!form) return;

            const payload = new URLSearchParams(new FormData(form));
            payload.append('ajax', '1');

            // Bloquear radios durante la petición para evitar doble envío rápido.
            const radios = form.querySelectorAll('input[name="asiste"]');
            radios.forEach(function(radio) { radio.disabled = true; });

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload.toString()
            })
            .then(function(response) {
                if (!response.ok) throw new Error('Error de red');
                return response.json();
            })
            .then(function(data) {
                if (!data || data.ok !== true) throw new Error('Respuesta inválida');
                return recargarAviso(data.id_aviso);
            })
            .catch(function() {
                // Degradación elegante: mantener compatibilidad sin JS/AJAX estable.
                form.submit();
            })
            .finally(function() {
                radios.forEach(function(radio) { radio.disabled = false; });
            });
        });
    });
}

enlazarInputsArchivo(document);
enlazarRadiosAsistencia(document);

(function() {
    // Guarda y restaura scroll cuando se envía un formulario de subida.
    // Sin esto, al volver de la subida el usuario perdería contexto.
    const scrollKey = 'avisos_archivo_scroll_y';
    const savedScroll = sessionStorage.getItem(scrollKey);

    if (savedScroll !== null) {
        const targetY = parseInt(savedScroll, 10) || 0;
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.addEventListener('load', function() {
            window.scrollTo(0, targetY);
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'auto';
            }
        }, { once: true });
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
include("../inc/footer.html");
?>