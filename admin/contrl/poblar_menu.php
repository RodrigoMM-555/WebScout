<?php
/**
 * poblar_menu.php — Genera dinámicamente los enlaces del sidebar
 * =============================================================
 * Este archivo construye el menú lateral de administración:
 * - Lee todas las tablas de la base de datos (SHOW TABLES)
 * - Omite la tabla 'asistencias' (no se muestra en el menú)
 * - Marca la tabla activa según el parámetro GET
 * - Añade iconos según el tipo de tabla
 * - Para 'educandos', fuerza el orden por sección
 * - Añade un enlace especial para IA Admin (Beta)
 */

// Recoge parámetros de la URL
$tabla = $_GET['tabla'] ?? null; // Tabla seleccionada
$operacion = $_GET['operacion'] ?? null; // Operación activa

// Consulta todas las tablas de la base de datos
$resultado = $conexion->query("SHOW TABLES;");

// Recorre cada tabla encontrada
while ($fila = $resultado->fetch_assoc()) {
    $nombreRealTabla = $fila['Tables_in_'.$db]; // Nombre real de la tabla

    // Omite la tabla de asistencias (no se muestra en el menú)
    if($nombreRealTabla == "asistencias") {
        continue;
    }

    $clase = "";
    // Si es la tabla activa, añade la clase 'activo'
    if($nombreRealTabla == $tabla){
        $clase  = "activo";
    }

    // Formatea el nombre para mostrarlo bonito
    $nombre_tabla = ucfirst(str_replace('_',' ',$nombreRealTabla));
    $icono = '';
    // Asigna un icono según el tipo de tabla
    if ($fila['Tables_in_'.$db] === 'avisos') {
        $icono = '📣 ';
    } elseif ($fila['Tables_in_'.$db] === 'usuarios') {
        $icono = '👤 ';
    } elseif ($fila['Tables_in_'.$db] === 'educandos') {
        $icono = '🎒 ';
    } elseif ($fila['Tables_in_'.$db] === 'lista_espera') {
        $icono = '⏳ ';
    }

    // Para educandos, fuerza el orden por sección
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

// Enlace especial para IA Admin (Beta)
$claseIA = ($operacion === 'ia_admin') ? 'activo' : '';
echo '
    <a href="?operacion=ia_admin" class="'.$claseIA.'">
        IA Admin (Beta)
    </a>
';
?>