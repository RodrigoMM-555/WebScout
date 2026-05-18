<?php
/**
 * procesaeliminar.php — Elimina un registro de cualquier tabla CRUD
 * ==================================================================
 * Este script actúa como endpoint de borrado para el panel de administración.
 * - Recibe la tabla y el id del registro a eliminar desde los enlaces de la vista de listado.
 * - Valida el token CSRF para evitar ataques de falsificación de petición.
 * - Valida que la tabla sea permitida mediante una whitelist.
 * - Utiliza prepared statements para evitar SQL injection al eliminar por id.
 * - Elimina el registro correspondiente y muestra un mensaje de éxito o error.
 * - Redirige de vuelta al listado, conservando el contexto de ordenación y navegación.
 */
session_start(); // Inicia la sesión PHP para acceder a variables de sesión
include "../../inc/conexion_bd.php"; // Incluye la conexión a la base de datos

// Solo los administradores pueden ejecutar esta acción de borrado
requerirAdmin();

// Validar el token CSRF (se envía como parámetro GET en el enlace) para evitar ataques de falsificación de petición
$token = $_GET['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403); // Devuelve código 403 Forbidden si el token no es válido
    die('Token CSRF inválido.'); // Detiene la ejecución y muestra mensaje de error
}

// Validar que la tabla recibida esté permitida usando una whitelist
$tabla = validarTabla($_GET['tabla'] ?? '');

// Obtener el id del registro a eliminar y preparar la consulta para evitar SQL injection
$id = (int)($_GET['id'] ?? 0); // Convierte el id recibido a entero
$sql = "DELETE FROM `{$tabla}` WHERE id = ?"; // Consulta SQL con placeholder
$stmt = $conexion->prepare($sql); // Prepara la consulta
$stmt->bind_param("i", $id); // Asocia el id como parámetro entero

// Ejecutar la consulta y mostrar mensaje según el resultado
if ($stmt->execute()) {
    setFlash('exito', 'Registro eliminado correctamente.'); // Mensaje de éxito
} else {
    setFlash('error', 'Error al eliminar: ' . $conexion->error); // Mensaje de error con detalle
}

// Cierra el statement para liberar recursos
$stmt->close();

// Redirigir al listado, conservando el contexto de tabla, orden y dirección
header("Location: ../?tabla=" . urlencode($tabla)
    . "&ordenar_por=" . urlencode($_GET['ordenar_por'] ?? 'id')
    . "&direccion=" . urlencode($_GET['direccion'] ?? 'ASC'));
exit; // Finaliza el script después de la redirección
?>
