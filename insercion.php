<!-- Conexiona la BBDD del front -->
<?php
    $host = "localhost";
    $user = "Uwebscout";
    $pass = "Uwebscout5$";
    $db   = "WebScout";

    $conexion = new mysqli($host, $user, $pass, $db);

    $sql = "INSERT INTO `usuarios` (`nombre`,`apellidos`,`contraseña`,`email`,`telefono`,`direccion`,`rol`)
VALUES ('r','r','" . password_hash("r", PASSWORD_DEFAULT) ."','r',1,'r','admin')";

    if ($conexion->query($sql) === TRUE) {
        echo "Nuevo registro creado correctamente";
    } else {
        echo "Error: " . $sql . "<br>" . $conexion->error;
    }

    $conexion->close();
?>