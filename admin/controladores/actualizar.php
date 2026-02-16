<?php
// Sacamos el nombre de la tabla
$tabla = $_GET['tabla'];
$id = $_GET['id'] ?? 0; // Obtenemos el ID del registro a actualizar (nuevo)
echo "<h1>" . ($id ? "Actualizar" : "Insertar") . " en $tabla</h1>";

// Si hay ID, cargamos los datos actuales de la fila
$valores = [];
if ($id) {
    $sql = "SELECT * FROM `$tabla` WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $valores = $resultado->fetch_assoc(); // Array asociativo con los valores actuales
}
?>
<form action="?operacion=procesaactualizar&tabla=<?= $_GET['tabla'] ?>" method="POST">
<?php
    // Pedimos la estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");
    while ($fila = $resultado->fetch_assoc()) {
        $clave = $fila['Field'];

        // Saltar columna auto_increment pero guardar ID en oculto
        if ($fila['Extra'] === 'auto_increment') {
            if ($id) {
                echo "<input type='hidden' name='id' value='$id'>";
            }
            continue;
        }
        elseif ($fila['Field'] === 'seccion') {
            $selected = $valores['seccion'] ?? '';
            echo "
                <div class='control_formulario'>
                    <label>$clave</label>
                    <select name='$clave'>
                        <option value='colonia' " . ($selected=='colonia'?'selected':'') . ">Colonia</option>
                        <option value='manada' " . ($selected=='manada'?'selected':'') . ">Manada</option>
                        <option value='tropa' " . ($selected=='tropa'?'selected':'') . ">Tropa</option>
                        <option value='posta' " . ($selected=='posta'?'selected':'') . ">Posta</option>
                        <option value='rutas' " . ($selected=='rutas'?'selected':'') . ">Rutas</option>
                    </select>
                </div>
            ";
        }
        elseif ($fila["Field"] === "secciones") {
            $selected = explode(",", $valores['secciones'] ?? '');
            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>$clave</label><br>
                    <input type='checkbox' name='secciones[]' value='colonia' " . (in_array('colonia',$selected)?'checked':'') . "> Colonia
                    <input type='checkbox' name='secciones[]' value='manada' " . (in_array('manada',$selected)?'checked':'') . "> Manada
                    <input type='checkbox' name='secciones[]' value='tropa' " . (in_array('tropa',$selected)?'checked':'') . "> Tropa
                    <input type='checkbox' name='secciones[]' value='posta' " . (in_array('posta',$selected)?'checked':'') . "> Posta
                    <input type='checkbox' name='secciones[]' value='rutas' " . (in_array('rutas',$selected)?'checked':'') . "> Rutas
                </div>
            ";
        }
        elseif ($fila["Field"] === "id_usuario") {
            $sql2 = "SELECT id, nombre, apellidos FROM usuarios";
            $resultado2 = $conexion->query($sql2);
            $selected = $valores['id_usuario'] ?? '';
            echo "
                <div class='control_formulario'>
                    <label>$clave</label>
                    <select name='$clave'>
            ";
            while ($u = $resultado2->fetch_assoc()) {
                $id_u = $u['id'];
                $nombre_u = $u['nombre'];
                $apellidos_u = $u['apellidos'];
                $sel = ($id_u == $selected) ? 'selected' : '';
                echo "<option value='$id_u' $sel>$nombre_u $apellidos_u</option>";
            }
            echo "
                    </select>
                </div>
            ";
        }
        elseif ($fila["Field"] === "fecha_hora") {
            $value = $valores['fecha_hora'] ?? '';
            // Convertimos a formato compatible con datetime-local
            if ($value) $value = date('Y-m-d\TH:i', strtotime($value));
            echo "
                <div class='control_formulario'>
                    <label>$clave</label>
                    <input type='datetime-local' name='$clave' step='60' value='$value'>
                </div>
            ";
        }
        else {
            $value = $valores[$clave] ?? '';
            echo "
                <div class='control_formulario'>
                    <label>$clave</label>
                    <input type='text' name='$clave' placeholder='$clave' value='" . htmlspecialchars($value) . "'>
                </div>
            ";
        }
    }
?>
    <div class="control_formulario">
        <input type="submit" value="<?= $id ? 'Actualizar' : 'Insertar' ?>">
    </div>
</form>

<link rel="stylesheet" href="css/estilo.css">
