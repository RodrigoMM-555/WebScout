<?php
/**
 * front/index.php — Página de login
 * ====================================
 * ★ FIX: URLs dinámicas con BASE_URL en vez de localhost hardcodeado.
 * ★ FIX: Mensaje de error solo aparece si hay ?error=invalid.
 */
require_once __DIR__ . '/../config.php';
?>
<!-- Pagina de login -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebScout — Iniciar sesión</title>
    <link rel="stylesheet" href="css/webscout.css">
</head>
<body class="login">
    <main>
        <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo WebScout">
        <h2>Iniciar sesión</h2>
        <!-- Formulario de login -->
        <form action="contrl/procesar_login.php" method="POST">
            <input type="text" id="usuario" name="usuario" placeholder="Usuario:" required><br><br>
            <input type="password" id="password" name="password" placeholder="Contraseña:" required><br><br>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
                <p style="display:block">Usuario o contraseña incorrectos</p>
            <?php endif; ?>
            <input type="submit" value="Iniciar sesión">
        </form>
    </main>
</body>
</html>