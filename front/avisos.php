<!-- Avisos -->
<?php
session_start();
include("inc/header.html");
include("inc/conexion_bd.php");

// Funcion para depurar texto
function limpiarTexto($texto) {
    $texto = str_replace(' ', '_', $texto);
    $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    if ($tmp !== false) {
        $texto = $tmp;
    }
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $texto);
}
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
$sql = "SELECT * FROM avisos";
$resultado = $conexion->query($sql);

$avisos_mostrados = [];
$hayAvisos = false;

// Recorremos los avisos
while ($aviso = $resultado->fetch_assoc()) {

    // Lista de educandos que pertenecen a las secciones del aviso
    $lista_nombres = [];
    foreach ($educandos as $edu) {
        if (strpos($aviso['secciones'], $edu['seccion']) !== false) {
            $lista_nombres[] = [
                "id" => $edu["id"],
                "nombreCompleto" => $edu['nombre'] . " " . $edu['apellidos']
            ];
        }
    }

    // Si hay educandos que deben recibir el aviso y aún no se ha mostrado
    if (!empty($lista_nombres) && !in_array($aviso["id"], $avisos_mostrados)) {

        // Formateamos la fecha
        $fecha_formateada = date("d/m/Y H:i", strtotime($aviso["fecha_hora"]));

        // Información del aviso
        echo "<div class='aviso'>";
        echo "<h3>" . htmlspecialchars($aviso["titulo"]) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($aviso["contenido"])) . "</p>";
        echo "<p style='font-size:14px; color:gray;'>$fecha_formateada</p>";
        echo "<p>Lugar: " . $aviso["lugar"] . "</p>";
        echo "<p>Municipio: " . $aviso["municipio"] . "</p>";
        echo "<p>Provincia: " . $aviso["provincia"] . "</p>";
        echo "<p>Responsable de la actividad: ".$aviso["responsable"]."</p>";
        echo "<p>Secciones: " . implode(", ", explode(",", $aviso["secciones"])) . "</p>";

        // Circular
        if ($aviso["circular"] == "no") {
            echo "<p>No hay circular adjunta</p>";
        }

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
                    $ruta = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreCarpeta;
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

                echo "<tr class='$filaClase'>
                    <td>$nombreCompleto</td>";
                
                if ($puedeSubir) {
                    echo "
                        <td><a href='../circulares/plantillas/6-Autorización participación actividad.pdf' target='_blank'>⬇️</a></td>
                        <td><form action='contrl/subearchivo.php?ori=avisos' method='post' enctype='multipart/form-data'>
                        <input type='file' name='archivo' required>
                        <input type='hidden' name='nombreCompleto' value='".htmlspecialchars($nombreCompleto)."'>
                        <input type='hidden' name='tituloAviso' value='".htmlspecialchars($aviso['titulo'])."'>
                        <input type='submit' value='⬆️'>
                    </form></td>";
                } else {
                    echo "<td></td><td></td>
                    <span style='color:gray;'>No aplica</span>";
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