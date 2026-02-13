<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avisos</title>
</head>
<body>
    <?php
        session_start();
        include("inc/header.html");
        include("inc/conexion_bd.php");

        $nombre = $_SESSION["nombre"];

        // Obtener id del usuario
        $sql = "SELECT id FROM usuarios WHERE nombre = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $id_usuario = $usuario["id"];

        // Obtener secciones de los hijos
        $sql = "SELECT seccion FROM educandos WHERE id_usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $secciones = [];
        while ($fila = $resultado->fetch_assoc()) {
            $secciones[] = $fila["seccion"];
        }

        // Buscar avisos de esas secciones (sin duplicados)
        $avisos_mostrados = [];

        foreach ($secciones as $sec) {

            $sql = "SELECT * FROM avisos WHERE secciones LIKE ?";
            $stmt = $conexion->prepare($sql);
            $param = "%" . $sec . "%";
            $stmt->bind_param("s", $param);
            $stmt->execute();
            $resultado = $stmt->get_result();

            while ($aviso = $resultado->fetch_assoc()) {
                if (!in_array($aviso["id"], $avisos_mostrados)) {
                    echo "<h3>" . $aviso["titulo"] . "</h3>";
                    $avisos_mostrados[] = $aviso["id"];
                }
            }
        }
    ?>

</body>
</html>