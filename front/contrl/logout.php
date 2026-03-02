<?php
/**
 * logout.php — Cierra la sesión del usuario
 * ===========================================
 * Destruye la sesión y redirige al login.
 */
session_start();
session_unset();
session_destroy();

header("Location: ../index.php");
exit;
