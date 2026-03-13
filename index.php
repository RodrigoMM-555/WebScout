<?php
/**
 * index.php — Página de login
 * ============================
 * Punto de entrada público de la aplicación.
 * Muestra el formulario de acceso, carga estilos globales y redirige
 * el envío de credenciales a `front/contrl/procesar_login.php`.
 * También pinta mensajes de error de autenticación cuando corresponde.
 */
require_once __DIR__ . '/tools/config.php';
?>
<!-- Pagina de login -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebScout — Iniciar sesión</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/front/css/webscout.css">
</head>
<body class="login">
    <div class="circulitos">
    <?php
        include 'inc/circulos.html';
    ?>
    </div>
    <main>
        <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo WebScout">
        <h2>Iniciar sesión</h2>
        <!-- Formulario de login -->
        <form action="<?= BASE_URL ?>/front/contrl/procesar_login.php" method="POST">
            <input type="email" id="email" name="email" placeholder="Correo electrónico:" required><br><br>
            <input type="password" id="password" name="password" placeholder="Contraseña:" required><br><br>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
                <p style="display:block">Correo o contraseña incorrectos</p>
            <?php endif; ?>
            <input type="submit" value="🔐 Iniciar sesión">
        </form>
        <a href="<?= BASE_URL ?>/front/formListaEspera.php" class="btn-lista-espera sin-icono-auto" title="Lista de espera">
        Lista de espera</a>
    </main>
</body>
</html>