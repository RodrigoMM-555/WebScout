<?php
/**
 * sincronizar_secciones.php — Sincronización manual de secciones de educandos
 * ===========================================================================
 * Este archivo recalcula la sección scout de todos los educandos según su año de nacimiento
 * y el curso scout actual (regla septiembre → cambio de ronda).
 * - Solo accesible para administradores (requerirAdmin)
 * - Protegido con token CSRF
 * - Llama a la función sincronizarSeccionesEducandos($conexion)
 * - Redirige de vuelta al listado mostrando mensaje de éxito o info
 */

session_start(); // Inicia sesión para acceder a $_SESSION
include('../../inc/conexion_bd.php'); // Conexión a la base de datos

requerirAdmin(); // Solo admins pueden ejecutar

// Recoge parámetros de la URL
$token = (string)($_GET['csrf_token'] ?? ''); // Token CSRF
$ordenarPor = (string)($_GET['ordenar_por'] ?? 'seccion'); // Campo de orden
$direccion = strtoupper((string)($_GET['direccion'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC'; // Dirección de orden
$seccion = (string)($_GET['seccion'] ?? ''); // Filtro de sección

// Construye la URL de vuelta al listado de educandos
$urlVuelta = '?tabla=educandos'
          . '&ordenar_por=' . urlencode($ordenarPor)
          . '&direccion=' . urlencode($direccion)
          . ($seccion !== '' ? '&seccion=' . urlencode($seccion) : '');

// Valida el token CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    setFlash('error', 'No se pudo sincronizar secciones: token CSRF inválido.');
    header('Location: ' . $urlVuelta);
    exit;
}

// Ejecuta la sincronización de secciones
$actualizados = sincronizarSeccionesEducandos($conexion);

// Muestra mensaje según si hubo cambios o no
if ($actualizados > 0) {
    setFlash('exito', 'Secciones scout sincronizadas correctamente. Registros actualizados: ' . $actualizados . '.');
} else {
    setFlash('info', 'Sincronización completada. No había cambios pendientes en secciones.');
}

// Redirige de vuelta al listado
header('Location: ' . $urlVuelta);
exit;
