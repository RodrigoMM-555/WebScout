<?php
session_start();
include("inc/header.html");
include("inc/conexion_bd.php");

// --- Función EXACTA a la de subearchivo.php ---
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
    <style>
        .tr-entregado { background: #e9f9ed; }
        .tr-pendiente { background: #fdeaea; }
    </style>

    <h1>Avisos</h1>

<?php
if (!isset($_SESSION["nombre"])) {
    echo '<div class="sin-avisos">No se ha iniciado sesión.</div>';
    exit;
}

$nombre = $_SESSION["nombre"];

// Obtener ID del usuario
$sql = "SELECT id FROM usuarios WHERE nombre = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

if (!$usuario) {
    echo '<div class="sin-avisos">Usuario no encontrado.</div>';
    exit;
}

$id_usuario = $usuario["id"];

// Obtener educandos
$sql = "SELECT nombre, apellidos, seccion FROM educandos WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();

$educandos = [];
while ($fila = $resultado->fetch_assoc()) {
    $educandos[] = $fila;
}

// Obtener avisos
$sql = "SELECT * FROM avisos";
$resultado = $conexion->query($sql);

$avisos_mostrados = [];
$hayAvisos = false;

while ($aviso = $resultado->fetch_assoc()) {

    $lista_nombres = [];

    foreach ($educandos as $edu) {
        if (strpos($aviso['secciones'], $edu['seccion']) !== false) {
            $lista_nombres[] = $edu['nombre'] . " " . $edu['apellidos'];
        }
    }

    if (!empty($lista_nombres) && !in_array($aviso["id"], $avisos_mostrados)) {

        $fecha_formateada = date("d/m/Y H:i", strtotime($aviso["fecha_hora"]));

        echo "<div class='aviso'>";
        echo "<h3>" . htmlspecialchars($aviso["titulo"]) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($aviso["contenido"])) . "</p>";
        echo "<p style='font-size:14px; color:gray;'>$fecha_formateada</p>";
        echo "<p>Lugar: " . $aviso["lugar"] . "</p>";
        echo "<p>Municipio: " . $aviso["municipio"] . "</p>";
        echo "<p>Provincia: " . $aviso["provincia"] . "</p>";
        echo "<p>Responsable de la actividad: ".$aviso["responsable"]."</p>";
        echo "<p>Secciones: " . implode(", ", explode(",", $aviso["secciones"])) . "</p>";

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
                </tr>";

            foreach ($lista_nombres as $nombreCompleto) {

                // Normalizar igual que en subearchivo.php
                $nombreCarpeta = limpiarTexto($nombreCompleto);
                $tituloLimpio  = limpiarTexto($aviso['titulo']);

                // Ruta del educando
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

                $filaClase = $entregado ? "tr-entregado" : "tr-pendiente";

                echo "<tr class='$filaClase'>
                    <td>$nombreCompleto</td>
                    <td><a href='../circulares/plantillas/6-Autorización participación actividad.pdf' target='_blank'>⬇️</a></td>
                    <td>
                        <form action='contrl/subearchivo.php?ori=avisos' method='post' enctype='multipart/form-data'>
                            <input type='file' name='archivo' required>
                            <input type='hidden' name='nombreCompleto' value='".htmlspecialchars($nombreCompleto)."'>
                            <input type='hidden' name='tituloAviso' value='".htmlspecialchars($aviso['titulo'])."'>
                            <input type='submit' value='⬆️'>
                        </form>
                    </td>
                    <td>";

                if ($entregado) {
                    echo "<span style='color:green; font-weight:bold;'>Sí</span>";
                } else {
                    echo "<span style='color:red; font-weight:bold;'>No</span>";
                }

                echo "</td></tr>";
            }

            echo "</table>";
        }

        echo "</div>";

        $avisos_mostrados[] = $aviso["id"];
        $hayAvisos = true;
    }
}

if (!$hayAvisos) {
    echo "<div class='sin-avisos'>No hay avisos disponibles para tus hijos.</div>";
}
?>
</main>