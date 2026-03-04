<?php
/**
 * insercion.php — Script de semilla para crear el primer admin
 * ==============================================================
 * ADVERTENCIA: Ejecutar una sola vez para crear la cuenta de prueba.
 * Eliminar o proteger este archivo en producción.
 */
require_once __DIR__ . '/config.php';

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}

// Usar prepared statement para insertar el usuario admin semilla
$sql = "INSERT INTO `usuarios` (`nombre`,`apellidos`,`contraseña`,`email`,`telefono`,`direccion`,`rol`)
        VALUES (?, ?, ?, ?, ?, ?, 'admin')";
$stmt = $conexion->prepare($sql);
$nombre = 'r';
$apellidos = 'r';
$hash = password_hash('r', PASSWORD_DEFAULT);
$email = 'r';
$telefono = 1;
$direccion = 'r';
$stmt->bind_param("ssssis", $nombre, $apellidos, $hash, $email, $telefono, $direccion);

if ($stmt->execute()) {
    echo "Cuenta admin de prueba creada. <strong>Elimina este archivo en producción.</strong>";
} else {
    echo "Error: " . $conexion->error;
}

$stmt->close();
$conexion->close();
?>