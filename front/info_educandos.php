<?php
session_start();
require_once __DIR__ . '/../inc/conexion_bd.php';
requerirSesion();

$id = isset($_GET['id_educando']) ? (int)$_GET['id_educando'] : 0;
if ($id > 0) {
    header('Location: educandos.php?id=' . $id);
} else {
    header('Location: perfil.php');
}
exit;