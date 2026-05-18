<?php
/**
 * logout.php — Cierra la sesión del usuario
 * -----------------------------------------
 * Destruye la sesión y redirige al login principal.
 *
 * Recibe: Nada
 * Devuelve: Redirección a index.php
 */
// --- INICIO BLOQUE DE CIERRE DE SESIÓN ---
// Destruye la sesión y limpia variables
// --- FIN BLOQUE DE CIERRE DE SESIÓN ---

session_start();
session_unset();
session_destroy();

header("Location: ../../index.php");
exit;
