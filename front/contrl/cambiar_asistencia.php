<?php
/**
 * cambiar_asistencia.php — Cambia la asistencia de un educando a un aviso
 * =========================================================================
 * Inserta o actualiza (ON DUPLICATE KEY) el registro de asistencia.
 * ★ FIX: Requiere sesión activa para evitar acceso no autenticado.
 */
session_start();

// Cargar config y utils
include("../inc/conexion_bd.php");

// Requiere sesión de usuario (padres o admin)
requerirSesion();

// Recoger datos del formulario
$id_aviso    = (int)($_POST["id_aviso"] ?? 0);
$id_educando = (int)($_POST["id_educando"] ?? 0);
$asistencia  = ($_POST["asiste"] ?? '') === "1" ? 'si' : 'no';

// Insertar o actualizar asistencia
$stmt = $conexion->prepare("
    INSERT INTO asistencias (id_aviso, id_educando, asistencia)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE asistencia = VALUES(asistencia)
");
$stmt->bind_param("iis", $id_aviso, $id_educando, $asistencia);
$stmt->execute();
$stmt->close();

header("Location: ../avisos.php");
exit;