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
    require_once __DIR__ . '/../../config.php';
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
    <link rel="stylesheet" href="css/webscout.css">
</head>
<body>
    <header>
        <div>
            <!-- Logo y navegación principal -->
            <article>
                <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo">
                <a href="inicio.php" class="<?= $paginaActual === 'inicio.php' ? 'nav-activa' : '' ?>"><h2>Inicio</h2></a>
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
                    Salir
                </a>
            </article>
        </div>
    </header>