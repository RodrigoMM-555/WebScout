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
            $claseColonia = ($seccion == "colonia") ? "seccion-colonia" : "";
            $claseManada  = ($seccion == "manada") ? "seccion-manada" : "";
            $claseTropa   = ($seccion == "tropa") ? "seccion-tropa" : "";
            $clasePosta   = ($seccion == "posta") ? "seccion-posta" : "";
            $claseRutas   = ($seccion == "rutas") ? "seccion-rutas" : "";
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
a.seccion-colonia { 
    background-color: #ffe5b4; 
    color: var(--morado-oscuro);
    border: 1px solid #000;
}

a.seccion-manada { 
    background-color: #fff9c4; 
    color: var(--morado-oscuro);
    border: 1px solid #000;
}
a.seccion-tropa { 
    background-color: #bbdefb;
    color: var(--morado-oscuro);
    border: 1px solid #000;
}
a.seccion-posta { 
    background-color: #ffcdd2;
    color: var(--morado-oscuro);
    border: 1px solid #000;
}
a.seccion-rutas { background-color: #c8e6c9; 
    color: var(--morado-oscuro);
    border: 1px solid #000;
}
</style>