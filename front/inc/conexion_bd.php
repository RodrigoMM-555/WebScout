<!-- Conexiona la BBDD del front -->
<?php
    $host = "localhost";
    $user = "Uwebscout";
    $pass = "Uwebscout5$";
    $db   = "WebScout";

    $conexion = new mysqli($host, $user, $pass, $db);
?>
