<?php
/**
 * actualizar_permiso.php — Alterna un bit de permiso vía AJAX (fetch)
 * =====================================================================
 * Endpoint llamado desde la tabla de admin para activar/desactivar permisos
 * individuales de un educando en tiempo real.
 * Recibe `id` y `permiso`, lee el valor actual y aplica operación XOR para
 * conmutar el bit solicitado sin editar manualmente el resto de permisos.
 */
// --- INICIO BLOQUE DE PROTECCIÓN Y VALIDACIÓN DE SESIÓN ---
session_start();
include_once "../../inc/conexion_bd.php";

// Inicia la sesión y carga la conexión a la base de datos

// Solo admins pueden modificar permisos
requerirAdmin();

// Verifica que el usuario tenga rol de administrador, si no, detiene la ejecución

$id      = (int)($_POST['id'] ?? 0);
$permiso = (int)($_POST['permiso'] ?? 0);

// Recoge el id del educando y el permiso a alternar desde POST, convirtiendo a entero

// Validar que el permiso sea un bit válido (1, 2, 4 u 8)
if (!in_array($permiso, [PERM_COCHE, PERM_WHATSAPP, PERM_SOLO, PERM_FOTOS], true)) {
    http_response_code(400);
    die('Permiso no válido.');
}

// Si el permiso recibido no es uno de los bits válidos, responde con error 400 y termina

// Obtener permisos actuales con prepared statement
$stmt = $conexion->prepare("SELECT permisos FROM educandos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Consulta segura: obtiene los permisos actuales del educando

if (!$fila) {
    http_response_code(404);
    die('Educando no encontrado.');
}

// Si no existe el educando, responde con error 404 y termina

// Toggle del bit con XOR
$permisosNuevo = (int)$fila['permisos'] ^ $permiso;

// Aplica XOR para alternar solo el bit del permiso solicitado, sin afectar los demás

// Actualizar con prepared statement
$stmt = $conexion->prepare("UPDATE educandos SET permisos = ? WHERE id = ?");
$stmt->bind_param("ii", $permisosNuevo, $id);
$stmt->execute();
$stmt->close();

// Actualiza los permisos en la base de datos de forma segura

echo "ok";

// Devuelve "ok" si todo fue correcto (respuesta esperada por AJAX)