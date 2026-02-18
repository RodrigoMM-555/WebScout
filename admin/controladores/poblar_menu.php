<?php
	// Ahora lo que quiero es un listado de las tablas en la base de datos
	$resultado = $conexion->query("
    SHOW TABLES;
    ");
    while ($fila = $resultado->fetch_assoc()) {
        $clase = "";							// De entrada no tienes clase	
        if($fila['Tables_in_'.$db] == $_GET['tabla']){	// Pero si el nombre de esta tabla coincide con la tabla cargada
            $clase  = "activo";			// En ese caso tu clase es "activo"
        }
        
        $nombre_tabla = ucfirst(str_replace('_',' ',$fila['Tables_in_'.$db]));

        if ($nombre_tabla == "Educandos") {
            echo '
                <a href="?tabla='.$fila['Tables_in_'.$db].'" class="'.$clase.'">
                '.$nombre_tabla.'
                </a>
                <form>
                    <select>
                        <option value="colonia">Colonia</option>
                        <option value="manada">Manada</option>
                        <option value="tropa">Tropa</option>
                        <option value="posta">Posta</option>
                        <option value="rutas">Rutas</option>
                    </select>
                </form>
            ';
        } else {
            echo '
                <a href="?tabla='.$fila['Tables_in_'.$db].'" class="'.$clase.'">
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