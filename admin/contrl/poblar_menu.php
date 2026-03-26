<?php
/**
 * poblar_menu.php — Genera dinámicamente los enlaces del sidebar
 * ================================================================
 * Lee las tablas de la BD y crea un link por cada una (excepto 'asistencias').
 * Resalta la tabla activa con clase 'activo'.
 */
    // Recogemos parámetros
    $tabla = $_GET['tabla'] ?? null;
    $operacion = $_GET['operacion'] ?? null;
    // Listado de tablas
    $resultado = $conexion->query("SHOW TABLES;");

while ($fila = $resultado->fetch_assoc()) {

    $nombreRealTabla = $fila['Tables_in_'.$db];

    // Saltar la tabla "asistencias"
    if($nombreRealTabla == "asistencias") {
        continue; // pasa a la siguiente iteración sin mostrar esta tabla
    }

    $clase = "";

    // Marca la tabla principal como activa
    if($nombreRealTabla == $tabla){
        $clase  = "activo";
    }

    $nombre_tabla = ucfirst(str_replace('_',' ',$nombreRealTabla));
    $icono = '';

    if ($fila['Tables_in_'.$db] === 'avisos') {
        $icono = '📣 ';
    } elseif ($fila['Tables_in_'.$db] === 'usuarios') {
        $icono = '👤 ';
    } elseif ($fila['Tables_in_'.$db] === 'educandos') {
        $icono = '🎒 ';
    } elseif ($fila['Tables_in_'.$db] === 'lista_espera') {
        $icono = '⏳ ';
    }

    if ($nombre_tabla == "Educandos") {
        $nombreRealTabla = "educandos&ordenar_por=seccion&direccion=ASC";

        echo '
            <a href="?tabla='.$nombreRealTabla.'" class="'.$clase.'">
                '.$icono.$nombre_tabla.'
            </a>
        ';

    } else {

        echo '
            <a href="?tabla='.$nombreRealTabla.'" class="'.$clase.'">
                '.$icono.$nombre_tabla.'
            </a>
            
        ';
    }
}

$claseIA = ($operacion === 'ia_admin') ? 'activo' : '';
echo '
    <a href="?operacion=ia_admin" class="'.$claseIA.'">
        IA Admin (Beta)
    </a>
';
?>