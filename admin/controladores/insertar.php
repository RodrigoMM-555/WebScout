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

        // Segun como se llame la columna, pintamos un tipo de input u otro para evitar introducciones erróneas
        // seccion
        elseif ($fila['Field'] === 'seccion') {
            $clave2 = ucfirst($clave);
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
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
        // secciones (para actividades)
        elseif( $fila["Field"] === "secciones") {
            $clave2 = ucfirst($clave);
            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>$clave2</label><br>
                    <input type='checkbox' name='secciones[]' value='colonia'> Colonia
                    <input type='checkbox' name='secciones[]' value='manada'> Manada
                    <input type='checkbox' name='secciones[]' value='tropa'> Tropa
                    <input type='checkbox' name='secciones[]' value='posta'> Posta
                    <input type='checkbox' name='secciones[]' value='rutas'> Rutas
                </div>
            ";
        }
        // id_usuario
        elseif ($fila["Field"] === "id_usuario") {
            // Consulta
            $sql = "SELECT id, nombre, apellidos, rol FROM usuarios";
            $resultado = $conexion->query($sql);
            // Primera parte 
            echo "
                <div class='control_formulario'>
                    <label>Madre/Padre</label>
                    <select name='$clave'>
            ";
            // Creamos las opciones
            while ($u = $resultado->fetch_assoc()) {
                if ($u["rol"] !== "usuario") {
                    continue; // Solo mostrar usuarios normales, no admins
                }
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
        // fecha_hora
        elseif ($fila["Field"] === "fecha_hora") {
            $clave2 = ucfirst($clave);
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input 
                        type='datetime-local'
                        name='$clave'
                        step='60'  <!-- step en segundos, 60 = solo minutos -->
                </div>
            ";
        }
        // circular
        elseif ($fila["Field"]== "circular") {
            echo "
                <div class='control_formulario'>
                    <label>Circular</label>
                    <select name='circular'>
                        <option value='si'>Si</option>
                        <option value='no'>No</option>
                    </select>
                </div>
        ";
        }
        // rol
        elseif ($fila["Field"]== "rol") {
            echo "
                <div class='control_formulario'>
                    <label>Rol</label>
                    <select name='rol'>
                        <option value='usuario'>Usuario</option>
                        <option value='administrador'>Administrador</option>
                    </select>
                </div>
        ";
        }
        // otra cosa
        else {
            if ($fila["Field"] === "anio") {
                $clave = "año";
            }
            $clave2 = ucfirst($clave);
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
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