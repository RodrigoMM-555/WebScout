<?php
/**
 * cambiar_asistencia.php — Cambia la asistencia de un educando a un aviso
 * =========================================================================
 * Inserta o actualiza (ON DUPLICATE KEY) el registro de asistencia.
 * ★ FIX: Requiere sesión activa para evitar acceso no autenticado.
 */
session_start();

// Cargar config y utils
include("../../inc/conexion_bd.php");

// Requiere sesión de usuario (padres o admin)
requerirSesion();

// Recoger datos del formulario
$id_aviso    = (int)($_POST["id_aviso"] ?? 0);
$id_educando = (int)($_POST["id_educando"] ?? 0);
$asistencia  = ($_POST["asiste"] ?? '') === "1" ? 'si' : 'no';
$return_anchor = $_POST["return_anchor"] ?? '';
$isAjax = (($_POST['ajax'] ?? '') === '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if (!preg_match('/^aviso-\d+$/', $return_anchor)) {
    $return_anchor = '';
}

// Insertar o actualizar asistencia
$stmt = $conexion->prepare("
    INSERT INTO asistencias (id_aviso, id_educando, asistencia)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE asistencia = VALUES(asistencia)
");
$stmt->bind_param("iis", $id_aviso, $id_educando, $asistencia);
$stmt->execute();
$stmt->close();

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'id_aviso' => $id_aviso,
        'id_educando' => $id_educando,
        'asistencia' => $asistencia,
    ]);
    exit;
}

$url = "../avisos.php";
if ($return_anchor !== '') {
    $url .= "#" . $return_anchor;
}

header("Location: " . $url);
exit;