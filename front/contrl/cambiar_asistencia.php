<?php

include("../inc/conexion_bd.php");

$id_aviso = $_POST["id_aviso"];
$id_educando = $_POST["id_educando"];
$asistencia = $_POST["asiste"] === "1" ? 'si' : 'no';

// Si existe, actualiza
$stmt = $conexion->prepare("
    INSERT INTO asistencias (id_aviso, id_educando, asistencia)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE asistencia = VALUES(asistencia)
");

// iis -> id_aviso (int), id_educando (int), asistencia (string)
$stmt->bind_param("iis", $id_aviso, $id_educando, $asistencia);
$stmt->execute();

header("Location: ../avisos.php");
exit;