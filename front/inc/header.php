<?php
/**
 * header.php — Cabecera del front (antes header.html)
 * =====================================================
 * - URLs dinámicas usando BASE_URL (ya no hardcodea localhost)
 * - Resalta la página actual en la navegación
 * - Incluye botón de cerrar sesión
 */

// Asegurarse de que la sesión está activa para detectar página y usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar config si aún no se cargó (necesitamos BASE_URL)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../tools/config.php';
}

// Detectar la página actual para marcar la nav activa
$paginaActual = basename($_SERVER['PHP_SELF']); // ej: "avisos.php"
?>
<!-- Header -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebScout</title>
</head>
    <link rel="stylesheet" href="css/webscout.css">
    <script src="js/lang.js"></script>
</head>
<body>
    <header>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center;">
                <!-- Logo y navegación principal -->
                <article>
                    <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo">
                    <a href="inicio.php" class="sin-icono-auto <?= $paginaActual === 'inicio.php' ? 'nav-activa' : '' ?>"><h2 data-i18n="inicio">Inicio</h2></a>
                </article>
                <article>
                    <a href="avisos.php" class="<?= $paginaActual === 'avisos.php' ? 'nav-activa' : '' ?>">
                        <img src="<?= BASE_URL ?>/img/exclama.png" alt="Avisos">
                    </a>
                    <a href="perfil.php" class="<?= $paginaActual === 'perfil.php' ? 'nav-activa' : '' ?>">
                        <img src="<?= BASE_URL ?>/img/perfil.png" alt="Perfil e hijos">
                    </a>
                    <!-- Botón de cerrar sesión -->
                    <a href="contrl/logout.php" class="btn-logout" title="Cerrar sesión">
                        <span data-i18n="salir">Salir</span>
                    </a>
                </article>
            </div>
            <!-- Botón de cambiar idioma SIEMPRE visible a la derecha -->
            <button id="lang-switch-btn" onclick="toggleLang()" style="padding: 6px 16px; font-weight: bold; border-radius: 6px; border: 1px solid #888; background: #f5f5f5; cursor: pointer; margin-left: 20px;"></button>
        </div>
    </header>