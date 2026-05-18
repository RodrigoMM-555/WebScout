<?php
/**
 * index.php — Página de login principal de WebScout
 * -------------------------------------------------
 * Este archivo es el punto de entrada público de la aplicación WebScout.
 * - Muestra el formulario de acceso para usuarios.
 * - Carga los estilos globales y el favicon.
 * - Incluye elementos visuales decorativos (circulitos).
 * - Redirige el envío de credenciales a 'front/contrl/procesar_login.php'.
 * - Muestra mensajes de error si el login falla.
 *
 * Recibe: Nada directamente (GET para errores, POST en el form).
 * Devuelve: HTML con el formulario de login y mensajes de error si corresponde.
 */

// Carga la configuración global (constantes, rutas, etc.)
require_once __DIR__ . '/tools/config.php';
?>

<!-- Página de login principal -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebScout — Iniciar sesión</title>
    <!-- Carga de estilos globales y favicon -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/front/css/webscout.css">
    <link rel="icon" href="<?= BASE_URL ?>/img/logo.png" type="image/png">
</head>
<body class="login">
    <!-- Elementos decorativos de fondo -->
    <div class="circulitos">
    <?php
        // Incluye los círculos decorativos del fondo
        include 'inc/circulos.html';
    ?>
    </div>
    <main>
        <!-- Logo y título -->
        <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo WebScout">
        <h2>Iniciar sesión</h2>
        <!-- Formulario de login de usuario -->
        <form action="<?= BASE_URL ?>/front/contrl/procesar_login.php" method="POST">
            <input type="email" id="email" name="email" placeholder="Correo electrónico:" required><br><br>
            <input type="password" id="password" name="password" placeholder="Contraseña:" required><br><br>
            <?php 
            // Si hay error de login, muestra mensaje de error
            if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
                <p style="display:block">Correo o contraseña incorrectos</p>
            <?php endif; ?>
            <input type="submit" value="🔐 Iniciar sesión">
        </form>
        <!-- Enlace a la lista de espera -->
        <a href="<?= BASE_URL ?>/front/formListaEspera.php" class="btn-lista-espera sin-icono-auto" title="Lista de espera">
        Lista de espera</a>
    </main>
</body>
</html>