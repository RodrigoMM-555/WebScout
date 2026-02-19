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

<!-- Pequeño style para las entregas -->
<style>
    .tr-entregado { background: #e9f9ed; }
    .tr-pendiente { background: #fdeaea; }
</style>

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
$sql = "SELECT nombre, apellidos, seccion FROM educandos WHERE id_usuario = ?";
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

// 
while ($aviso = $resultado->fetch_assoc()) {

    // Lista de nombres de educandos que teien avisos en sus secciones
    $lista_nombres = [];

    // En cada educando comprobamos si su seccion esta en las secciones del aviso, si es asi lo añadimos a la lista de destinatarios con sus apellidos
    foreach ($educandos as $edu) {
        if (strpos($aviso['secciones'], $edu['seccion']) !== false) {
            $lista_nombres[] = $edu['nombre'] . " " . $edu['apellidos'];
        }
    }

    // Si no hay nada en la lista de avisos  o no esta el aviso on el mismo id se empieza a crear el aviso
    if (!empty($lista_nombres) && !in_array($aviso["id"], $avisos_mostrados)) {

        // Formateamos la fecha para mostrarla de forma mas legible
        $fecha_formateada = date("d/m/Y H:i", strtotime($aviso["fecha_hora"]));

        // Pintamos toda la información del aviso y la tabla de entregas si tiene circular
        echo "<div class='aviso'>";
        echo "<h3>" . htmlspecialchars($aviso["titulo"]) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($aviso["contenido"])) . "</p>";
        echo "<p style='font-size:14px; color:gray;'>$fecha_formateada</p>";
        echo "<p>Lugar: " . $aviso["lugar"] . "</p>";
        echo "<p>Municipio: " . $aviso["municipio"] . "</p>";
        echo "<p>Provincia: " . $aviso["provincia"] . "</p>";
        echo "<p>Responsable de la actividad: ".$aviso["responsable"]."</p>";
        echo "<p>Secciones: " . implode(", ", explode(",", $aviso["secciones"])) . "</p>";

        // No hay circular
        if ($aviso["circular"] == "no") {
            echo "<p>No hay circular adjunta</p>";
        }

        // Si hay circular, se crea la cabecera de la tabla
        if ($aviso["circular"] == "si") {
            echo "<table class='tabla-archivos'>
                <tr>
                    <th>Niñ@</th>
                    <th>Descargar archivo</th>
                    <th>Subir archivo</th>
                    <th>Entregado</th>
                </tr>";

            // Recorremos los nombres de los educandos que hemos almacenado que tiene un aviso
            foreach ($lista_nombres as $nombreCompleto) {

                // Preparamos apra ver si ya tiene un archivo subido
                $nombreCarpeta = limpiarTexto($nombreCompleto);
                $tituloLimpio  = limpiarTexto($aviso['titulo']);

                // Ruta del educando
                $ruta = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreCarpeta;

                $entregado = false;

                // Comprobamos si la carpeta existe
                if (is_dir($ruta)) {
                    $archivos = array_diff(scandir($ruta), ['.', '..']);
                    $prefijo = $tituloLimpio . '_' . $nombreCarpeta . '.';
                    // Y listamos los archivos para marcar los documentos entregados
                    // Concretamente comparamos el prefijo y el nombre del archivo, si coincide marcamos que se ha entregado
                    foreach ($archivos as $f) {
                        if (strpos($f, $prefijo) === 0) {
                            $entregado = true;
                            break;
                        }
                    }
                }

                // Si se ha entragado es true la clase es entragado, si no es pendiente
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

        // Almacenamos que el aviso ya se ha mostrado y que hay avisos
        $avisos_mostrados[] = $aviso["id"];
        $hayAvisos = true;
    }
}
    // Si no hay avisos se muestra un mensaje
if (!$hayAvisos) {
    echo "<div class='sin-avisos'>No hay avisos disponibles para tus hijos.</div>";
}
?>
</main>