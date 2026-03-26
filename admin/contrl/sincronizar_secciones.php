<?php
/**
 * sincronizar_secciones.php — Sincronización manual de secciones de educandos
 * ===========================================================================
 * Recalcula la sección scout de todos los educandos según su año de nacimiento
 * y el curso scout actual (regla septiembre → cambio de ronda).
 */
session_start();
include('../../inc/conexion_bd.php');

requerirAdmin();

$token = (string)($_GET['csrf_token'] ?? '');
$ordenarPor = (string)($_GET['ordenar_por'] ?? 'seccion');
$direccion = strtoupper((string)($_GET['direccion'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
$seccion = (string)($_GET['seccion'] ?? '');

$urlVuelta = '?tabla=educandos'
          . '&ordenar_por=' . urlencode($ordenarPor)
          . '&direccion=' . urlencode($direccion)
          . ($seccion !== '' ? '&seccion=' . urlencode($seccion) : '');

if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    setFlash('error', 'No se pudo sincronizar secciones: token CSRF inválido.');
    header('Location: ' . $urlVuelta);
    exit;
}

$actualizados = sincronizarSeccionesEducandos($conexion);

if ($actualizados > 0) {
    setFlash('exito', 'Secciones scout sincronizadas correctamente. Registros actualizados: ' . $actualizados . '.');
} else {
    setFlash('info', 'Sincronización completada. No había cambios pendientes en secciones.');
}

header('Location: ' . $urlVuelta);
exit;
