<!-- Avisos -->
<?php
session_start();
include("inc/header.php");
include("inc/conexion_bd.php");

// limpiarTexto() ya definida en utils.php (cargado por conexion_bd)
?>

<main class="avisos">

<!-- Seccion de avisos -->
<h1>Avisos</h1>

<?php
// Por si se entra sin sesion
if (!isset($_SESSION["nombre"])) {
    echo '<div class="sin-avisos">No se ha iniciado sesión.</div>';
    exit;
}

// Nombre del usuario
$nombre = $_SESSION["nombre"];

// Obtener ID del usuario
$sql = "SELECT id FROM usuarios WHERE nombre = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// Guardamos el id del usuario
$id_usuario = $usuario["id"];

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

    // Lista de educandos que pertenecen a las secciones del aviso
    $lista_nombres = [];
    foreach ($educandos as $edu) {
        // Compara las secciones del aviso con la sección del educando
        if (strpos($aviso['secciones'], $edu['seccion']) !== false) {
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
        echo "<div class='aviso'>";
        echo "<h3>" . htmlspecialchars($aviso["titulo"]) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($aviso["contenido"])) . "</p>";

        // Fechas en bloque propio
        echo "<div class='aviso-fechas'>";
        echo "<span class='aviso-fecha-item'><strong>📅 Inicio</strong>$fecha_inicio_formateada</span>";
        if (!empty($fecha_fin_formateada)) {
            $fecha_fin_limpia = ltrim($fecha_fin_formateada, '- ');
            echo "<span class='aviso-fecha-item'><strong>🏁 Fin</strong>$fecha_fin_limpia</span>";
        }
        echo "</div>";

        // Detalles del aviso en tarjeta con iconos
        $detalles = [];
        if (!empty($aviso["lugar"]))
            $detalles[] = "<span class='aviso-detalle'><strong>📍 Lugar</strong>" . htmlspecialchars($aviso["lugar"]) . "</span>";
        if (!empty($aviso["municipio"]))
            $detalles[] = "<span class='aviso-detalle'><strong>🏘️ Municipio</strong>" . htmlspecialchars($aviso["municipio"]) . "</span>";
        if (!empty($aviso["provincia"]))
            $detalles[] = "<span class='aviso-detalle'><strong>🗺️ Provincia</strong>" . htmlspecialchars($aviso["provincia"]) . "</span>";
        if (!empty($aviso["responsable"]))
            $detalles[] = "<span class='aviso-detalle'><strong>👤 Responsable</strong>" . htmlspecialchars($aviso["responsable"]) . "</span>";

        echo "<div class='aviso-detalles" . (empty($detalles) ? " solo-secciones" : "") . "'>";
        echo implode('', $detalles);
        // Secciones dentro de la misma tarjeta, en fila completa
        echo "<div class='aviso-secciones" . (empty($detalles) ? " sin-borde" : "") . "'>";
        echo "<strong>📋 Secciones</strong> ";
        $secciones = explode(",", $aviso["secciones"]);
        foreach ($secciones as $sec) {
            $sec = trim($sec);
            echo "<span class='aviso-seccion-chip seccion-$sec'>" . ucfirst($sec) . "</span>";
        }
        echo "</div>";
        echo "</div>";

        // Si no hay circular
        if ($aviso["circular"] == "no") {

            echo "<table class='tabla-archivos'>
                <tr>
                    <th>Niñ@</th>
                    <th>No hay circular adjunta</th> 
                    <th>Asistencia</th>
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
                        echo "<span>No asiste</span>";
                    } elseif ($asis && $asis["asistencia"] === "si") {
                        echo "<span>Sí asiste</span>";
                    } else {
                        echo "<span style='color: blue; font-weight:bold;'>RESPONDER</span>";
                    }
                echo "</td>
                    <td>
                        <form action='contrl/cambiar_asistencia.php' method='post'>
                            <input type='hidden' name='id_aviso' value='".$aviso["id"]."'>
                            <input type='hidden' name='id_educando' value='".$id_educando."'>
                            <label class='switch'>
                                <input type='radio' name='asiste' value='1' $checkedSi onchange='this.form.submit()'> Sí
                                <input type='radio' name='asiste' value='0' $checkedNo onchange='this.form.submit()'> No
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
            echo "<table class='tabla-archivos'>
                <tr>
                    <th>Niñ@</th>
                    <th>Descargar archivo</th>
                    <th>Subir archivo</th>
                    <th>Entregado</th>
                    <th>Asistencia</th>
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
                    $ruta = BASE_PATH . '/circulares/educandos/' . $nombreCarpeta;
                    $entregado = false;
                    if (is_dir($ruta)) {
                        $archivos = array_diff(scandir($ruta), ['.', '..']);
                        $prefijo = $tituloLimpio . '_' . $nombreCarpeta . '.';
                        foreach ($archivos as $f) {
                            if (strpos($f, $prefijo) === 0) {
                                $entregado = true;
                                break;
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
                        <form action='contrl/cambiar_asistencia.php' method='post'>
                            <input type='hidden' name='id_aviso' value='".$aviso["id"]."'>
                            <input type='hidden' name='id_educando' value='".$id_educando."'>
                            <label class='switch'>
                                <input type='radio' name='asiste' value='1' $checkedSi onchange='this.form.submit()'> Sí
                                <input type='radio' name='asiste' value='0' $checkedNo onchange='this.form.submit()'> No
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
<!-- Script para mostrar el nombre del archivo seleccionado -->
<script>
document.querySelectorAll('.input-archivo-oculto').forEach(function(input) {
    input.addEventListener('change', function() {
        const nombre = this.files && this.files.length ? this.files[0].name : 'Sin archivo';
        const form = this.closest('form');
        const target = form ? form.querySelector('.archivo-nombre') : null;
        if (target) target.textContent = nombre;
    });
});
</script>
<?php
include("inc/footer.html");
?>