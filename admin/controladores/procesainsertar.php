<?php

include('../inc/conexion_bd.php');
$tabla = $_GET['tabla'];

// Creamos un array con las columnas y otro con los valores
$columnas = [];
$valores = [];

foreach($_POST as $clave=>$valor){

    if($clave == "contraseña"){
        $valor = password_hash($valor, PASSWORD_DEFAULT);
    }

    if($clave == "año"){
        $clave = "anio";
    }

    // Si es un array (caso de secciones[]), convertir a string
    if (is_array($valor)) {
        $valor = implode(",", $valor); // Convertir a "colonia,manada,tropa"
    }

    // Escapar valor ya seguro
    $valor = $conexion->real_escape_string($valor);

    $columnas[] = "`$clave`";
    $valores[] = "'$valor'";
}

// Convertimos arrays en cadenas para SQL
$sql = "INSERT INTO `$tabla` (".implode(",", $columnas).") VALUES (".implode(",", $valores).");";

// Ejecutamos y comprobamos errores
$resultado = $conexion->query($sql);
if(!$resultado){
    die("Error en la consulta: " . $conexion->error);
}

// Redirección
header("Location: ?tabla=".$tabla);
exit;
?>
