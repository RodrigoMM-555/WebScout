<body>
<?php
    session_start();
    include("inc/header.html");
    include("inc/conexion_bd.php");
?>

<main class="avisos">
    <h1>Avisos</h1>

<?php
    $nombre = $_SESSION["nombre"];

    // Obtener ID del usuario
    $sql = "SELECT id FROM usuarios WHERE nombre = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $id_usuario = $usuario["id"];

    // Obtener todos los educandos del usuario
    $sql = "SELECT nombre, apellidos, seccion FROM educandos WHERE id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $educandos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $educandos[] = $fila;
    }

    // Obtener todos los avisos que correspondan a cualquiera de las secciones del usuario
    $sql = "SELECT * FROM avisos";
    $resultado = $conexion->query($sql); // Aquí podríamos filtrar con LIKE luego

    $avisos_mostrados = [];
    $hayAvisos = false;

    while ($aviso = $resultado->fetch_assoc()) {
        $lista_nombres = [];

        // Revisar qué educandos del usuario corresponden a las secciones del aviso
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
            echo "<p>Educandos: " . implode(", ", $lista_nombres) . "</p>";
            echo "<p>Circular: ". ($aviso["circular"] == "si" ? "<a href='../circulares/plantillas/6-Autorización participación actividad.pdf' target='_blank'>Ver circular adjunta</a>" : "No hay circular adjunta") . "</p>";
            if ($aviso["circular"] == "si") {
                echo "<table class='tabla-archivos'>
                <tr>
                    <th>Niñ@</th>
                    <th>Descargar archivo</th>
                    <th>Subir archivo</th>
                </tr>
                ";
                foreach($lista_nombres as $nombreCompleto) {
                    echo "<tr>
                    <td>$nombreCompleto</td>
                    <td><a href='../circulares/plantillas/6-Autorización participación actividad.pdf' target='_blank'>⬇️</a></td>
                    <td>
                        <form action='contrl/subearchivo.php' method='post' enctype='multipart/form-data'>
                        <input type='file' name='archivo' value='Seleccionar archivo'>
                        <input type='hidden' name='nombreCompleto' value='$nombreCompleto'>
                        <input type='submit' value='⬆️'>
                        </form>
                    </td>";
                }
                echo "</table>";

                // Cambiar el color de la fila de rojo a verde segun el estado del aviso
            
            echo "</div>";

            $avisos_mostrados[] = $aviso["id"];
            $hayAvisos = true;
            }
        }
    }

    if (!$hayAvisos) {
        echo "<div class='sin-avisos'>No hay avisos disponibles para tus hijos.</div>";
    }
?>


</main>
</body>
