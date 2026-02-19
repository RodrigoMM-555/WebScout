<!-- Pagina de login -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/webscout.css">
</head>
<body class="login">
    <main>
        <img src="http://localhost/WebScout/img/logo.png" alt="placeholder">
        <h2>Iniciar sesión</h2>
        <!-- Formulario de login -->
        <form action="contrl/procesar_login.php" method="POST">
            <input type="text" id="usuario" name="usuario" placeholder="Usuario:" required ><br><br>
            <input type="password" id="password" name="password" placeholder="Contraseña:" required><br><br>
            <p>Usuario o contraseña incorrectos</p>
            <input type="submit" value="Iniciar sesión">
        </form>
        <!-- Lista de espera -->
        <a href="lista_espera.php">Lista de espera</a>
    </main>
</body>
</html>

<!-- Mensajes de error -->
<?php
if (isset($_GET["error"]) && $_GET["error"] == "invalid") {
    echo "<script>
        document.querySelector('form p').style.display = 'block';
    </script>";
} else {
    echo "<script>
        document.querySelector('form p').style.display = 'none';
    </script>";
}
?>