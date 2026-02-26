<form action="?operacion=procesainsertar&tabla=<?= $_GET['tabla'] ?>" method="POST">
<?php
    // Sacamos el nombre de la tabla
    $tabla = $_GET['tabla'];
    $seccion = $_GET['seccion'] ?? "colonia";

    // Pedimos la estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");

    // Recorremos las columnas
    while ($fila = $resultado->fetch_assoc()) {
        $clave = $fila['Field']; // nombre de la columna

        // Saltar columna auto_increment
        if ($fila['Extra'] === 'auto_increment') {
            continue;
        }

        // SECCIÓN
        elseif ($fila['Field'] === 'seccion') {
            $clave2 = ucfirst(str_replace('_', ' ', $clave));
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

        // SECCIONES MÚLTIPLES
        elseif( $fila["Field"] === "secciones") {
            $clave2 = ucfirst(str_replace('_', ' ', $clave));
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
            $clave2 = ucfirst(str_replace('_', ' ', $clave));
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
            $clave2 = ucfirst(str_replace('_', ' ', $clave));
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
            $clave2 = ucfirst(str_replace('_', ' ', $clave));
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
            $clave2 = ucfirst(str_replace('_', ' ', $clave));
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

        // AÑO (este se controla por JS)
        elseif ($fila["Field"] === "anio") {
            echo '
                <div class="control_formulario">
                    <label>Año</label>
                    <select name="año" id="select-anio">
                        <!-- Estas opciones serán reemplazadas dinámicamente -->
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                    </select>
                </div>
            ';
        }

        // CUALQUIER OTRO CAMPO
        else {
            $clave2 = ucfirst(str_replace('_', ' ', $clave));
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

<!-- JS que filtra los años según la sección seleccionada -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const seccion = document.getElementById("select-seccion");
    const anio = document.getElementById("select-anio");

    // Fecha actual
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth() + 1;

    // Año scout: cambia en septiembre
    const cursoScout = month >= 9 ? year + 1 : year;

    function obtenerAniosPorSeccion(sec) {
        switch (sec) {

            case "colonia": // 6 - 7 años
                return [cursoScout - 6, cursoScout - 7];

            case "manada":  // 8 - 10 años
                return [cursoScout - 8, cursoScout - 9, cursoScout - 10];

            case "tropa":   // 11 - 13 años
                return [cursoScout - 11, cursoScout - 12, cursoScout - 13];

            case "posta":   // 14 - 16 años
                return [cursoScout - 14, cursoScout - 15, cursoScout - 16];

            case "rutas":   // 17 - 19 años
                return [cursoScout - 17, cursoScout - 18, cursoScout - 19];

            default:
                return [cursoScout];
        }
    }

    function actualizarOpciones() {
        const valor = seccion.value;
        const opciones = obtenerAniosPorSeccion(valor);
        anio.innerHTML = "";

        opciones.forEach(a => {
            const op = document.createElement("option");
            op.value = a;
            op.textContent = a;
            anio.appendChild(op);
        });
    }

    seccion.addEventListener("change", actualizarOpciones);
    actualizarOpciones(); // ejecutar al cargar
});
</script>