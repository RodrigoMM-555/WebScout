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
    if ($valor === "" || $valor === null) {
        $valores[] = "NULL";
    } else {
        $valor = $conexion->real_escape_string($valor);
        $valores[] = "'$valor'";
    }
}

// Convertimos arrays en cadenas para SQL
$sql = "INSERT INTO `$tabla` (".implode(",", $columnas).") VALUES (".implode(",", $valores).");";

// Ejecutamos y comprobamos errores
$resultado = $conexion->query($sql);
if(!$resultado){
    die("Error en la consulta: " . $conexion->error);
}


if($tabla === "avisos"){

    $id_aviso = $conexion->insert_id;

    if(isset($_POST['secciones']) && is_array($_POST['secciones'])){

        $secciones = $_POST['secciones'];

        // Escapamos y agregamos comillas
        $secciones_escapadas = array_map(function($s) use ($conexion) {
            return "'" . $conexion->real_escape_string($s) . "'";
        }, $secciones);

        $lista = implode(",", $secciones_escapadas);

        $sqlEducandos = "SELECT id FROM educandos WHERE seccion IN ($lista)";
        $resEducandos = $conexion->query($sqlEducandos);

        if(!$resEducandos){
            die("Error en consulta educandos: " . $conexion->error);
        }

        while($fila = $resEducandos->fetch_assoc()){
            $id_educando = $fila['id'];
            $sqlAsistencia = "
                INSERT INTO asistencias (id_aviso, id_educando)
                VALUES ($id_aviso, $id_educando)
            ";
            if(!$conexion->query($sqlAsistencia)){
                die("Error insertando asistencia: " . $conexion->error);
            }
        }
    }
}

// Redirección
header("Location: ?tabla=".$tabla."&ordenar_por=".urlencode($_GET['ordenar_por'])."&direccion=".urlencode($_GET['direccion']));
exit;
?>
