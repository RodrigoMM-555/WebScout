<?php
include('../inc/conexion_bd.php');

$tabla = $_GET['tabla'];
$id = $_POST['id'];

$columnas = [];
$valores = [];

foreach($_POST as $clave => $valor){
    if($clave == "contraseña"){
        $valor = password_hash($valor, PASSWORD_DEFAULT);
    }

    // Si es un array (caso de secciones[]), convertir a string
    if (is_array($valor)){
        $valor = implode(",", $valor);
    }

    if($clave == "año"){
        $clave = "anio";
    }

    $valor = $conexion->real_escape_string($valor);

    $columnas[] = "`$clave`";
    $valores[] = "'$valor'";
}

// Convertimos en asignaciones para UPDATE
$asignaciones = [];
foreach($columnas as $i => $columna){
    $asignaciones[] = $columna . '=' . $valores[$i];
}

// Construimos la consulta UPDATE
$sql = "UPDATE `$tabla` SET ".implode(",", $asignaciones)." WHERE id = '$id';";

// Ejecutamos
$resultado = $conexion->query($sql);
if(!$resultado){
    die("Error en la consulta: " . $conexion->error);
}

// Redirección al listado
header("Location: ?tabla=".$tabla);
exit;
?>
