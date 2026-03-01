<?php
include_once "../inc/conexion_bd.php";

$id = (int)$_POST['id'];
$permiso = (int)$_POST['permiso'];

// Obtener permisos actuales
$result = $conexion->query("SELECT permisos FROM educandos WHERE id = $id");
$fila = $result->fetch_assoc();
$permisosActuales = (int)$fila['permisos'];

// Alternar el permiso marcado
if ($permisosActuales & $permiso) {
    $permisosNuevo = $permisosActuales & (~$permiso); // desactivar
} else {
    $permisosNuevo = $permisosActuales | $permiso;  // activar
}

// Actualizar BD
$conexion->query("UPDATE educandos SET permisos = $permisosNuevo WHERE id = $id");

echo "ok";