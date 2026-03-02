<!-- ============================================================
     actualizar.php — Formulario dinámico para editar registros
     ============================================================
     Similar a insertar.php pero pre-rellena los valores existentes.
-->
<?php
// Sacamos el nombre de la tabla (validado contra whitelist)
$tabla = $_GET['tabla'];
$id = $_GET['id'] ?? 0; // Obtenemos el ID del registro a actualizar (nuevo)
echo "<h1>" . ($id ? "Actualizar" : "Insertar") . " en $tabla</h1>";

if ($tabla == "usuarios") {
    echo "<p style='color: red;'>ES NECESARIA LA CONTRASEÑA ORIGINAL PARA PODER MODIFICAR EL REGISTRO</p>";
}

// Si hay ID, cargamos los datos actuales
$valores = [];
if ($id) {
    $sql = "SELECT * FROM `$tabla` WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $valores = $resultado->fetch_assoc();
}
?>
<form action="?operacion=procesaactualizar&tabla=<?= htmlspecialchars($_GET['tabla']) ?>&seccion=<?= htmlspecialchars($_GET['seccion'] ?? '') ?>&ordenar_por=<?= htmlspecialchars($_GET['ordenar_por'] ?? 'id') ?>&direccion=<?= htmlspecialchars($_GET['direccion'] ?? 'ASC') ?>" method="POST">
<?php
    // Token CSRF para proteger el formulario
    echo campoCSRF();

    // Pedimos estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");
    while ($fila = $resultado->fetch_assoc()) {
        $clave = $fila['Field'];
        $clave2 = ucfirst(str_replace('_', ' ', $clave));

        // ID
        if ($fila['Extra'] === 'auto_increment') {
            if ($id) {
                echo "<input type='hidden' name='id' value='$id'>";
            }
            continue;
        }

        // SELECT SECCIÓN (añadido id='select-seccion')
        elseif ($clave === 'seccion') {
            $selected = $valores['seccion'] ?? '';
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <select name='$clave' id='select-seccion'>
                        <option value='colonia' " . ($selected=='colonia'?'selected':'') . ">Colonia</option>
                        <option value='manada' " . ($selected=='manada'?'selected':'') . ">Manada</option>
                        <option value='tropa' " . ($selected=='tropa'?'selected':'') . ">Tropa</option>
                        <option value='posta' " . ($selected=='posta'?'selected':'') . ">Posta</option>
                        <option value='rutas' " . ($selected=='rutas'?'selected':'') . ">Rutas</option>
                    </select>
                </div>
            ";
        }

        // SECCIONES MÚLTIPLES
        elseif ($clave === 'secciones') {
            $selected = explode(",", $valores['secciones'] ?? '');
            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>$clave2</label>
                    <label class='check-item seccion-colonia'><input type='checkbox' name='secciones[]' value='colonia' " . (in_array('colonia',$selected)?'checked':'') . "> <span>Colonia</span></label>
                    <label class='check-item seccion-manada'><input type='checkbox' name='secciones[]' value='manada' " . (in_array('manada',$selected)?'checked':'') . "> <span>Manada</span></label>
                    <label class='check-item seccion-tropa'><input type='checkbox' name='secciones[]' value='tropa' " . (in_array('tropa',$selected)?'checked':'') . "> <span>Tropa</span></label>
                    <label class='check-item seccion-posta'><input type='checkbox' name='secciones[]' value='posta' " . (in_array('posta',$selected)?'checked':'') . "> <span>Posta</span></label>
                    <label class='check-item seccion-rutas'><input type='checkbox' name='secciones[]' value='rutas' " . (in_array('rutas',$selected)?'checked':'') . "> <span>Rutas</span></label>
                </div>
            ";
        }

        // ID USUARIO
        elseif ($clave === "id_usuario") {
            $sql2 = "SELECT id, nombre, apellidos, rol FROM usuarios";
            $resultado2 = $conexion->query($sql2);
            $selected = $valores['id_usuario'] ?? '';
            echo "<div class='control_formulario'><label>Madre/Padre</label>
                  <select name='$clave'>";
            while ($u = $resultado2->fetch_assoc()) {
                if ($u["rol"] !== "usuario") continue;
                $sel = ($u['id'] == $selected) ? "selected" : "";
                echo "<option value='{$u['id']}' $sel>{$u['nombre']} {$u['apellidos']}</option>";
            }
            echo "</select></div>";
        }

        // FECHAS
        elseif ($clave === "fecha_hora_inicio" || $clave === "fecha_hora_fin") {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='datetime-local'
                           name='$clave'
                           value='" . (isset($valores[$clave]) ? date('Y-m-d\TH:i', strtotime($valores[$clave])) : '') . "'
                           step='60'>
                </div>
            ";
        }

        // AÑO (VACÍO, JS lo rellena)
        elseif ($clave === "anio") {
            echo '
                <div class="control_formulario">
                    <label>Año</label>
                    <select name="anio" id="select-anio">
                        <option value="">—</option>
                    </select>
                </div>
            ';
        }

        // PERMISOS (checkboxes de bits, igual que en la tabla)
        elseif ($clave === 'permisos') {
            $perm = (int)($valores['permisos'] ?? 0);
            echo "
                <input type='hidden' name='permisos' value='0'>
                <div class='control_formulario secciones-multiples'>
                    <label>Permisos</label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='1' " . (($perm & 1) ? 'checked' : '') . "> <span>Coche</span></label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='2' " . (($perm & 2) ? 'checked' : '') . "> <span>WhatsApp</span></label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='4' " . (($perm & 4) ? 'checked' : '') . "> <span>Solo</span></label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='8' " . (($perm & 8) ? 'checked' : '') . "> <span>Fotos</span></label>
                </div>
            ";
        }

        // SELECTS NORMALES
        elseif ($clave === "circular") {
            $sel = $valores['circular'] ?? '';
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <select name='circular'>
                        <option value='si' " . ($sel=='si'?'selected':'') . ">Sí</option>
                        <option value='no' " . ($sel=='no'?'selected':'') . ">No</option>
                    </select>
                </div>";
        }

        elseif ($clave === "rol") {
            $sel = $valores['rol'] ?? '';
            echo "
            <div class='control_formulario'>
                <label>$clave2</label>
                <select name='rol'>
                    <option value='usuario' " . ($sel=='usuario'?'selected':'') . ">Usuario</option>
                    <option value='admin' " . ($sel=='admin'?'selected':'') . ">Administrador</option>
                </select>
            </div>";
        }

        elseif ($clave === "tipo") {
            $sel = $valores['tipo'] ?? '';
            echo "
            <div class='control_formulario'>
                <label>$clave2</label>
                <select name='tipo'>
                    <option value='sabado' " . ($sel=='sabado'?'selected':'') . ">Sábado</option>
                    <option value='campamento' " . ($sel=='campamento'?'selected':'') . ">Campamento</option>
                    <option value='reunion' " . ($sel=='reunion'?'selected':'') . ">Reunión</option>
                    <option value='excursion' " . ($sel=='excursion'?'selected':'') . ">Excursión</option>
                    <option value='otro' " . ($sel=='otro'?'selected':'') . ">Otro</option>
                </select>
            </div>";
        }

        // CAMPOS NORMALES
        else {
            $value = $valores[$clave] ?? '';
            // Limpiar contraseña del formulario (no mostrar el hash)
            // ★ El campo vacío ahora se detecta en procesaactualizar y se omite
            $type = 'text';
            if ($clave === "contraseña") {
                $value = "";
                $type = 'password';
            }
            echo "
            <div class='control_formulario'>
                <label>$clave2</label>
                <input type='{$type}' name='$clave' value='" . htmlspecialchars($value) . "'"
                . ($clave === 'contraseña' ? " placeholder='Dejar vacío para mantener la actual'" : "")
                . ">
            </div>";
        }
    }
?>
    <div class="control_formulario botones-form">
        <input type="submit" value="<?= $id ? 'Actualizar' : 'Insertar' ?>">
        <a href="?tabla=<?= htmlspecialchars($_GET['tabla']) ?>&seccion=<?= htmlspecialchars($_GET['seccion'] ?? '') ?>&ordenar_por=<?= htmlspecialchars($_GET['ordenar_por'] ?? 'id') ?>&direccion=<?= htmlspecialchars($_GET['direccion'] ?? 'ASC') ?>" class="btn-cancelar">Cancelar</a>
    </div>
</form>

<link rel="stylesheet" href="css/estilo.css">

<!-- ============================
     SCRIPTS (UNIFICADOS Y CORREGIDOS)
============================= -->
<script>
const anioGuardado = <?= isset($valores["anio"]) ? json_encode((int)$valores["anio"]) : 'null' ?>;

document.addEventListener("DOMContentLoaded", function () {

    const selectAnio = document.getElementById("select-anio");
    const selectSeccion = document.getElementById("select-seccion");

    const ahora = new Date();
    const actual = ahora.getFullYear();
    const mes = ahora.getMonth() + 1;
    const cursoScout = (mes >= 9) ? actual + 1 : actual;

    function calcularSeccion(añoNacido) {
        const dif = cursoScout - añoNacido;

        if (dif === 6 || dif === 7) return "colonia";
        if (dif >= 8 && dif <= 10) return "manada";
        if (dif >= 11 && dif <= 13) return "tropa";
        if (dif >= 14 && dif <= 16) return "posta";
        if (dif >= 17 && dif <= 19) return "rutas";
        return null;
    }

    function actualizarSeccion() {
        if (!selectAnio || !selectSeccion) return;
        const y = Number(selectAnio.value);
        const sec = calcularSeccion(y);
        if (sec) selectSeccion.value = sec;
    }

    // === Generar años dinámicos ===
    if (selectAnio) {
        const edadMin = 6;
        const edadMax = 19;

        const max = actual - edadMin;
        const min = actual - edadMax;

        for (let y = max; y >= min; y--) {
            const option = document.createElement("option");
            option.value = y;
            option.textContent = y;

            if (anioGuardado && anioGuardado == y) {
                option.selected = true;
            }

            selectAnio.appendChild(option);
        }

        // Si había año guardado → actualizar sección automáticamente
        if (anioGuardado) actualizarSeccion();

        // Cambios manuales
        selectAnio.addEventListener("change", actualizarSeccion);
    }
});
</script>