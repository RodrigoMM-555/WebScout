<?php
    // Recogemos parámetros
    $tabla = $_GET['tabla'] ?? null;
    $seccion = $_GET['seccion'] ?? null;

    // Listado de tablas
    $resultado = $conexion->query("SHOW TABLES;");

    while ($fila = $resultado->fetch_assoc()) {

        $nombreRealTabla = $fila['Tables_in_'.$db];
        $clase = "";

        // Marca la tabla principal como activa
        if($nombreRealTabla == $tabla){
            $clase  = "activo";
        }

        $nombre_tabla = ucfirst(str_replace('_',' ',$nombreRealTabla));

        if ($nombre_tabla == "Educandos") {

            // Clases activas por sección
            $claseColonia = ($seccion == "colonia") ? "activo" : "";
            $claseManada  = ($seccion == "manada") ? "activo" : "";
            $claseTropa   = ($seccion == "tropa") ? "activo" : "";
            $clasePosta   = ($seccion == "posta") ? "activo" : "";
            $claseRutas   = ($seccion == "rutas") ? "activo" : "";

            echo '
                <a href="?tabla='.$nombreRealTabla.'" class="'.$clase.'">
                    '.$nombre_tabla.'
                </a>

                <a href="?tabla='.$nombreRealTabla.'&seccion=colonia" class="colonia '.$claseColonia.'">Colonia</a>
                <a href="?tabla='.$nombreRealTabla.'&seccion=manada" class="manada '.$claseManada.'">Manada</a>
                <a href="?tabla='.$nombreRealTabla.'&seccion=tropa" class="tropa '.$claseTropa.'">Tropa</a>
                <a href="?tabla='.$nombreRealTabla.'&seccion=posta" class="posta '.$clasePosta.'">Posta</a>
                <a href="?tabla='.$nombreRealTabla.'&seccion=rutas" class="rutas '.$claseRutas.'">Rutas</a>
            ';

        } else {

            echo '
                <a href="?tabla='.$nombreRealTabla.'" class="'.$clase.'">
                    '.$nombre_tabla.'
                </a>
            ';
        }
    }
?>

<style>
    .activo{
        border: 2px solid #000;
    }
</style>
