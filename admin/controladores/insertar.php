<form action="?operacion=procesainsertar&tabla=<?= $_GET['tabla'] ?>" method="POST">
<?php
    // Sacamos el nombre de la tabla
    $tabla = $_GET['tabla'];

    // Pedimos la estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");
    // Recorremos las columnas
    while ($fila = $resultado->fetch_assoc()) {
        $clave = $fila['Field']; // nombre de la columna

        // Saltar columna auto_increment para no pedirla en el formulario
        if ($fila['Extra'] === 'auto_increment') {
            continue;
        }
        elseif ($fila['Field'] === 'seccion') {
            echo "
                <div class='control_formulario'>
                    <label>$clave</label>
                    <select name='$clave'>
                        <option value='colonia'>Colonia</option>
                        <option value='manada'>Manada</option>
                        <option value='tropa'>Tropa</option>
                        <option value='posta'>Posta</option>
                        <option value='rutas'>Rutas</option>
                    </select>
                </div>
            ";
        }
        elseif( $fila["Field"] === "secciones") {
            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>$clave</label><br>
                    <input type='checkbox' name='secciones[]' value='colonia'> Colonia
                    <input type='checkbox' name='secciones[]' value='manada'> Manada
                    <input type='checkbox' name='secciones[]' value='tropa'> Tropa
                    <input type='checkbox' name='secciones[]' value='posta'> Posta
                    <input type='checkbox' name='secciones[]' value='rutas'> Rutas
                </div>
            ";
        }
        elseif ($fila["Field"] === "id_usuario") {
            // Consulta
            $sql = "SELECT id, nombre, apellidos FROM usuarios";
            $resultado = $conexion->query($sql);
            // Primera parte 
            echo "
                <div class='control_formulario'>
                    <label>Madre/Padre</label>
                    <select name='$clave'>
            ";
            // Creamos las opciones
            while ($u = $resultado->fetch_assoc()) {
                $id = $u['id'];
                $nombre = $u['nombre'];
                $apellidos = $u['apellidos'];
                echo "<option value='$id'>$nombre $apellidos</option>";
            }
            // Cerramos
            echo "
                    </select>
                </div>
            ";
        }
        elseif ($fila["Field"] === "fecha_hora") {
            echo "
                <div class='control_formulario'>
                    <label>$clave</label>
                    <input 
                        type='datetime-local'
                        name='$clave'
                        step='60'  <!-- step en segundos, 60 = solo minutos -->
                </div>
            ";
        }
        elseif ($fila["Field"]== "circular") {
            echo "
                <div class='control_formulario'>
                    <label>circular</label>
                    <select name='circular'>
                        <option value='si'>Si</option>
                        <option value='no'>No</option>
                    </select>
                </div>
        ";
        }
        else {
            if ($fila["Field"] === "anio") {
                $clave = "año";
            }
            echo "
                <div class='control_formulario'>
                    <label>$clave</label>
                    <input 
                        type='text'
                        name='$clave'
                        placeholder='$clave'>
                </div>
            ";
        }
    }
?>
    <div class="control_formulario">
        <input type="submit" value="Insertar">
    </div>
</form>

<link rel="stylesheet" href="css/estilo.css">