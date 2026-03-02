<?php
/**
 * actualizar_permiso.php — Alterna un bit de permiso vía AJAX (fetch)
 * =====================================================================
 * Recibe id y permiso por POST. Usa XOR para toggle del bit.
 * Protegido con sesión de admin y prepared statements.
 */
session_start();
include_once "../inc/conexion_bd.php";

// Solo admins pueden modificar permisos
requerirAdmin();

$id      = (int)($_POST['id'] ?? 0);
$permiso = (int)($_POST['permiso'] ?? 0);

// Validar que el permiso sea un bit válido (1, 2, 4 u 8)
if (!in_array($permiso, [PERM_COCHE, PERM_WHATSAPP, PERM_SOLO, PERM_FOTOS], true)) {
    http_response_code(400);
    die('Permiso no válido.');
}

// Obtener permisos actuales con prepared statement
$stmt = $conexion->prepare("SELECT permisos FROM educandos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fila) {
    http_response_code(404);
    die('Educando no encontrado.');
}

// Toggle del bit con XOR
$permisosNuevo = (int)$fila['permisos'] ^ $permiso;

// Actualizar con prepared statement
$stmt = $conexion->prepare("UPDATE educandos SET permisos = ? WHERE id = ?");
$stmt->bind_param("ii", $permisosNuevo, $id);
$stmt->execute();
$stmt->close();

echo "ok";