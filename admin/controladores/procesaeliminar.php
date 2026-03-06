<?php
/**
 * procesaeliminar.php — Elimina un registro de cualquier tabla CRUD
 * ==================================================================
 * Seguridad aplicada:
 *   - Requiere sesión de admin
 *   - Valida tabla contra whitelist
 *   - Usa prepared statement para el id
 *   - Token CSRF validado
 */
session_start();
include "../../inc/conexion_bd.php";

// Solo admins pueden eliminar
requerirAdmin();

// Validar token CSRF (se envía como parámetro GET en el enlace)
$token = $_GET['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Token CSRF inválido.');
}

// Validar tabla contra la whitelist
$tabla = validarTabla($_GET['tabla'] ?? '');

// Prepared statement para evitar SQL injection en el id
$id = (int)($_GET['id'] ?? 0);
$sql = "DELETE FROM `{$tabla}` WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    setFlash('exito', 'Registro eliminado correctamente.');
} else {
    setFlash('error', 'Error al eliminar: ' . $conexion->error);
}

$stmt->close();

// Redirigir al listado
header("Location: ../?tabla=" . urlencode($tabla)
     . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
     . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC'));
exit;
?>
