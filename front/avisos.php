<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avisos</title>
</head>
<body>

    <?php
        include("inc/header.html");
        include("inc/conexion_bd.php");

        $sql = "SELECT seccion FROM educandos WHERE usuario_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_educando);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $educando = $resultado->fetch_assoc();
    ?>

</body>
</html>