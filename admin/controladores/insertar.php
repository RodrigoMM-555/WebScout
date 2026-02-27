<form action="?operacion=procesainsertar&tabla=<?= $_GET['tabla'] ?>&seccion=<?= $_GET['seccion'] ?>&ordenar_por=<?= $_GET['ordenar_por'] ?>&direccion=<?= $_GET['direccion'] ?>" method="POST">
<?php
    // Sacamos el nombre de la tabla
    $tabla = $_GET['tabla'];
    $seccion = $_GET['seccion'] ?? "colonia";

    // Pedimos la estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");

    // Recorremos las columnas
    while ($fila = $resultado->fetch_assoc()) {
        $clave = $fila['Field']; // nombre de la columna

        $clave2 = ucfirst(str_replace('_', ' ', $clave));


        // Saltar columna auto_increment
        if ($fila['Extra'] === 'auto_increment') {
            continue;
        }

        // SECCIÓN
        elseif ($fila['Field'] === 'seccion') {
            echo '
            <div class="control_formulario">
                <label>'.$clave2.'</label>
                <select name="'.$clave.'" id="select-seccion">
                    <option value="colonia" '.($seccion=="colonia" ? "selected" : "").'>Colonia</option>
                    <option value="manada" '.($seccion=="manada" ? "selected" : "").'>Manada</option>
                    <option value="tropa" '.($seccion=="tropa" ? "selected" : "").'>Tropa</option>
                    <option value="posta" '.($seccion=="posta" ? "selected" : "").'>Posta</option>
                    <option value="rutas" '.($seccion=="rutas" ? "selected" : "").'>Rutas</option>
                </select>
            </div>
            ';
        }

        // SECCIONES MULTIPLES
        elseif( $fila["Field"] === "secciones") {
            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>$clave2</label><br>
                    <input type='checkbox' name='secciones[]' value='colonia'> Colonia
                    <input type='checkbox' name='secciones[]' value='manada'> Manada
                    <input type='checkbox' name='secciones[]' value='tropa'> Tropa
                    <input type='checkbox' name='secciones[]' value='posta'> Posta
                    <input type='checkbox' name='secciones[]' value='rutas'> Rutas
                    <input type='checkbox' name='secciones[]' value='colonia,manada,tropa,posta,rutas'> Todas las secciones
                </div>
            ";
        }

        // ID USUARIO
        elseif ($fila["Field"] === "id_usuario") {
            $sql = "SELECT id, nombre, apellidos, rol FROM usuarios";
            $resultadoUsuarios = $conexion->query($sql);

            echo "
                <div class='control_formulario'>
                    <label>Madre/Padre</label>
                    <select name='$clave'>
            ";

            while ($u = $resultadoUsuarios->fetch_assoc()) {
                if ($u["rol"] !== "usuario") continue;

                echo "<option value='{$u['id']}'>{$u['nombre']} {$u['apellidos']}</option>";
            }
            echo "
                    </select>
                </div>
            ";
        }

        // FECHAS
        elseif ($fila["Field"] === "fecha_hora_inicio" || $fila["Field"] === "fecha_hora_fin") {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='datetime-local'
                           name='$clave'
                           step='60'>
                </div>
            ";
        }

        // CIRCULAR
        elseif ($fila["Field"]== "circular") {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <select name='circular'>
                        <option value='si'>Si</option>
                        <option value='no'>No</option>
                    </select>
                </div>
            ";
        }

        // ROL
        elseif ($fila["Field"]== "rol") {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <select name='rol'>
                        <option value='usuario'>Usuario</option>
                        <option value='admin'>Administrador</option>
                    </select>
                </div>
            ";
        }

        // TIPO EVENTO
        elseif ($fila["Field"]== "tipo") {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <select name='tipo'>
                        <option value='sabado'>Sabado</option>
                        <option value='campamento'>Campamento</option>
                        <option value='reunion'>Reunion</option>
                        <option value='excursion'>Excursion</option>
                        <option value='otro'>Otro</option>
                    </select>
                </div>
            ";
        }

        // AÑO
        elseif ($fila["Field"] === "anio") {
            echo '
                <div class="control_formulario">
                    <label>Año</label>
                    <select name="anio" id="select-anio">
                        <option value="">—</option>
                    </select>
                </div>
            ';
        }

        // CUALQUIER OTRO CAMPO
        else {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='text' name='$clave' placeholder='$clave2'>
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

<!-- JS: año → sección automática -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const anio = document.getElementById("select-anio");
    const seccion = document.getElementById("select-seccion");

    // Determinar año scout (septiembre → año next)
    const ahora = new Date();
    const año = ahora.getFullYear();
    const mes = ahora.getMonth() + 1;
    const cursoScout = (mes >= 9) ? año + 1 : año;

    // Definir reglas:
    // - 2 años más jóvenes → colonia
    // - siguientes 3 → manada
    // - siguientes 3 → tropa
    // - siguientes 3 → posta
    // - siguientes 3 → rutas

    function calcularSeccion(añoNacido) {
        const dif = cursoScout - añoNacido; // edad scout relativa

        if (dif === 6 || dif === 7) return "colonia";  // 2 años

        if (dif >= 8 && dif <= 10) return "manada";    // 3 años
        if (dif >= 11 && dif <= 13) return "tropa";     // 3 años
        if (dif >= 14 && dif <= 16) return "posta";     // 3 años
        if (dif >= 17 && dif <= 19) return "rutas";     // 3 años

        return null;
    }

    function actualizarSeccion() {
        const y = Number(anio.value);
        const sec = calcularSeccion(y);
        if (sec) {
            seccion.value = sec;
        }
    }

    anio.addEventListener("change", actualizarSeccion);
});

// ---------------------------
// GENERAR AÑOS AUTOMÁTICOS
// ---------------------------
document.addEventListener("DOMContentLoaded", function () {

    const selectAnio = document.getElementById("select-anio");

    const ahora = new Date();
    const añoActual = ahora.getFullYear();

    // Generamos edades entre 6 y 20 años
    const edadMin = 6;
    const edadMax = 19;

    const añoMax = añoActual - edadMin; // más joven = 6 años
    const añoMin = añoActual - edadMax; // mayor = 20 años

    for (let y = añoMax; y >= añoMin; y--) {
        const option = document.createElement("option");
        option.value = y;
        option.textContent = y;
        selectAnio.appendChild(option);
    }
});
</script>