<?php
/**
 * conexion_bd.php (front) — Conexión a la base de datos
 * Carga la configuración centralizada y crea la conexión mysqli.
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../utils.php';

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Comprobar errores de conexión
if ($conexion->connect_error) {
    die('Error de conexión a la base de datos: ' . $conexion->connect_error);
}

// Forzar charset UTF-8
$conexion->set_charset('utf8mb4');
?>
