<!-- ============================================================
     insertar.php — Formulario dinámico para insertar registros
     ============================================================
     Genera campos según la estructura de la tabla (DESCRIBE).
     Incluye lógica especial para secciones, fechas, año, rol, etc.
-->
<?php
    // Sacamos el nombre de la tabla (validado)
    $tabla = $_GET['tabla'];
    $seccion = $_GET['seccion'] ?? "colonia";
    $esListaEspera = ($tabla === 'lista_espera');
    $camposCheckboxListaEspera = ['hermano_en_grupo', 'relacion_con_miembro', 'familia_antiguo_scouter', 'estuvo_en_grupo'];

    $normalizarTexto = static function (string $texto): string {
        return str_ireplace(['ninio', 'nino'], 'niño', $texto);
    };

    $nombreTablaBonito = ucfirst($normalizarTexto(str_replace('_', ' ', $tabla)));
    echo "<h1 class='titulo-form-admin'>Insertar en " . htmlspecialchars($nombreTablaBonito) . "</h1>";
    echo "<form action=?operacion=procesainsertar&tabla=" . htmlspecialchars($_GET['tabla']) . "&seccion=" . htmlspecialchars($_GET['seccion'] ?? '') . "&ordenar_por=" . htmlspecialchars($_GET['ordenar_por'] ?? 'id') . "&direccion=" . htmlspecialchars($_GET['direccion'] ?? 'ASC') . "\" method=\"POST\">";
    // Token CSRF para proteger el formulario
    echo campoCSRF();


    // Pedimos la estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");

    $columnas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $columnas[] = $fila;
    }

    // Campos obligatorios segun esquema (NOT NULL, excepto auto_increment).
    $camposObligatorios = [];
    foreach ($columnas as $columna) {
        $esNotNull = (($columna['Null'] ?? 'YES') === 'NO');
        $esAutoIncrement = (($columna['Extra'] ?? '') === 'auto_increment');
        if ($esNotNull && !$esAutoIncrement) {
            $camposObligatorios[] = (string)$columna['Field'];
        }
    }

    $esCampoObligatorio = static function (string $campo) use ($camposObligatorios): bool {
        return in_array($campo, $camposObligatorios, true);
    };

    // Recorremos las columnas
    foreach ($columnas as $fila) {
        $clave = $fila['Field']; // nombre de la columna
        $tipoColumna = strtolower((string)$fila['Type']);

        $clave2 = ucfirst($normalizarTexto(str_replace('_', ' ', $clave)));
        $avisoObligatorio = $esCampoObligatorio($clave)
            ? "<small class='aviso-obligatorio'>*Obligatorio</small>"
            : "<small class='aviso-obligatorio is-hidden' aria-hidden='true'>&nbsp;</small>";


        // Saltar columna auto_increment
        if ($fila['Extra'] === 'auto_increment') {
            continue;
        }

        // SECCIÓN
        elseif ($fila['Field'] === 'seccion') {
            if ($tabla === 'educandos') {
                echo '
                <div class="control_formulario">
                    <label>'.$clave2.' <small>(automática por año)</small></label>
                    <select id="select-seccion" disabled>
                        <option value="colonia">Colonia</option>
                        <option value="manada">Manada</option>
                        <option value="tropa">Tropa</option>
                        <option value="posta">Posta</option>
                        <option value="rutas">Rutas</option>
                    </select>
                    '.$avisoObligatorio.'
                </div>
                ';
                continue;
            }

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
                '.$avisoObligatorio.'
            </div>
            ';
        }

        // SECCIONES MULTIPLES
        elseif( $fila["Field"] === "secciones") {
            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>$clave2</label>
                    <label class='check-item seccion-colonia'><input type='checkbox' name='secciones[]' value='colonia'> <span>Colonia</span></label>
                    <label class='check-item seccion-manada'><input type='checkbox' name='secciones[]' value='manada'> <span>Manada</span></label>
                    <label class='check-item seccion-tropa'><input type='checkbox' name='secciones[]' value='tropa'> <span>Tropa</span></label>
                    <label class='check-item seccion-posta'><input type='checkbox' name='secciones[]' value='posta'> <span>Posta</span></label>
                    <label class='check-item seccion-rutas'><input type='checkbox' name='secciones[]' value='rutas'> <span>Rutas</span></label>
                    <label class='check-item seccion-todas'><input type='checkbox' name='secciones[]' value='colonia,manada,tropa,posta,rutas'> <span>Todas</span></label>
                    $avisoObligatorio
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
                    $avisoObligatorio
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
                    $avisoObligatorio
                </div>
            ";
        }

        // LISTA ESPERA: ocultamos fecha_registro para que no sea editable.
        elseif ($esListaEspera && $fila['Field'] === 'fecha_registro') {
            echo "<input type='hidden' name='fecha_registro' value='" . date('Y-m-d') . "'>";
            continue;
        }

        // FECHA DE REGISTRO (solo fecha)
        elseif ($fila['Field'] === 'fecha_registro') {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='date' name='$clave' value='" . date('Y-m-d') . "'>
                    $avisoObligatorio
                </div>
            ";
        }

        // CIRCULAR
        elseif ($fila["Field"]== "circular") {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <select name='circular'>
                        <option value='no'>No</option>
                        <option value='si'>Si</option>
                    </select>
                    $avisoObligatorio
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
                    $avisoObligatorio
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
                    $avisoObligatorio
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
                    '.$avisoObligatorio.'
                </div>
            ';
        }

        // PERMISOS (checkboxes de bits, igual que en la tabla)
        elseif ($fila['Field'] === 'permisos') {
            echo "
                <input type='hidden' name='permisos' value='0'>
                <div class='control_formulario secciones-multiples'>
                    <label>Permisos</label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='1'> <span>Coche</span></label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='2'> <span>WhatsApp</span></label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='4'> <span>Solo</span></label>
                    <label class='check-item'><input type='checkbox' name='permisos[]' value='8'> <span>Fotos</span></label>
                    $avisoObligatorio
                </div>
            ";
        }

        // CHECKBOXES LISTA DE ESPERA (agrupados y ordenados)
        elseif ($esListaEspera && in_array($clave, $camposCheckboxListaEspera, true)) {
            if ($clave !== 'hermano_en_grupo') {
                continue;
            }

            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>Relación con el grupo</label>
                    <label class='check-item'>
                        <input type='hidden' name='hermano_en_grupo' value='0'>
                        <input type='checkbox' name='hermano_en_grupo' value='1'>
                        <span>Hermano en el grupo</span>
                    </label>
                    <label class='check-item'>
                        <input type='hidden' name='relacion_con_miembro' value='0'>
                        <input type='checkbox' name='relacion_con_miembro' value='1'>
                        <span>Relación con miembro</span>
                    </label>
                    <label class='check-item'>
                        <input type='hidden' name='familia_antiguo_scouter' value='0'>
                        <input type='checkbox' name='familia_antiguo_scouter' value='1'>
                        <span>Familia de antiguo scouter</span>
                    </label>
                    <label class='check-item'>
                        <input type='hidden' name='estuvo_en_grupo' value='0'>
                        <input type='checkbox' name='estuvo_en_grupo' value='1'>
                        <span>Estuvo en el grupo antes</span>
                    </label>
                    $avisoObligatorio
                </div>
            ";
        }

        // USUARIOS: al crear, forzar cambio de contraseña activado.
        elseif ($tabla === 'usuarios' && $clave === 'cambio_contraseña') {
            echo "
                <div class='control_formulario booleano'>
                    <label class='check-item'>
                        <input type='hidden' name='cambio_contraseña' value='1'>
                        <input type='checkbox' name='cambio_contraseña_visual' value='1' checked disabled>
                        <span>$clave2 (obligatorio al crear)</span>
                    </label>
                    $avisoObligatorio
                </div>
            ";
        }

        // BOOLEAN / TINYINT(1)
        elseif (preg_match('/^(tinyint\(1\)|boolean|bool)/', $tipoColumna)) {
            echo "
                <div class='control_formulario booleano'>
                    <label class='check-item'>
                        <input type='hidden' name='$clave' value='0'>
                        <input type='checkbox' name='$clave' value='1'>
                        <span>$clave2</span>
                    </label>
                    $avisoObligatorio
                </div>
            ";
        }

        // DATE
        elseif ($tipoColumna === 'date') {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='date' name='$clave'>
                    $avisoObligatorio
                </div>
            ";
        }

        // TEXT/LONGTEXT
        elseif (str_contains($tipoColumna, 'text')) {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <textarea name='$clave' placeholder='$clave2'></textarea>
                    $avisoObligatorio
                </div>
            ";
        }

        // CONTRASEÑA
        elseif ($clave === 'contraseña') {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='password' name='$clave' placeholder='$clave2'>
                    $avisoObligatorio
                </div>
            ";
        }

        // EMAIL
        elseif (str_contains($clave, 'email') || str_contains($clave, 'correo')) {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='email' name='$clave' placeholder='$clave2'>
                    $avisoObligatorio
                </div>
            ";
        }

        // TELÉFONO
        elseif (str_contains($clave, 'telefono')) {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='tel' name='$clave' placeholder='$clave2' maxlength='20' inputmode='tel'>
                    $avisoObligatorio
                </div>
            ";
        }

        // CUALQUIER OTRO CAMPO
        else {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='text' name='$clave' placeholder='$clave2'>
                    $avisoObligatorio
                </div>
            ";
        }
    }
?>
    <div class="control_formulario botones-form">
        <input type="submit" value="➕ Insertar">
        <a href="?tabla=<?= htmlspecialchars($_GET['tabla']) ?>&seccion=<?= htmlspecialchars($_GET['seccion'] ?? '') ?>&ordenar_por=<?= htmlspecialchars($_GET['ordenar_por'] ?? 'id') ?>&direccion=<?= htmlspecialchars($_GET['direccion'] ?? 'ASC') ?>" class="btn-cancelar">Cancelar</a>
    </div>
</form>

<link rel="stylesheet" href="css/estilo.css">

<!-- JS: año → sección automática -->
<script>
const cursoScout = <?= json_encode(obtenerCursoScoutActual()) ?>;
const rangoAniosScout = <?= json_encode(obtenerRangoAniosScout()) ?>;
const reglasSeccionScout = <?= json_encode(obtenerReglasSeccionScout(), JSON_UNESCAPED_UNICODE) ?>;

// Bloque 1: Autocompletar sección scout desde año de nacimiento.
document.addEventListener("DOMContentLoaded", function () {

    const anio = document.getElementById("select-anio");
    const seccion = document.getElementById("select-seccion");

    function calcularSeccion(añoNacido) {
        if (!Number.isFinite(añoNacido)) return null;
        const dif = cursoScout - añoNacido; // edad scout relativa
        const regla = reglasSeccionScout.find(function (item) {
            return dif >= item.min && dif <= item.max;
        });
        if (regla) return regla.seccion;

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

// Bloque 2: Generar automáticamente las opciones del select de año.
document.addEventListener("DOMContentLoaded", function () {

    const selectAnio = document.getElementById("select-anio");

    if (!selectAnio) return;

    const añoMax = Number(rangoAniosScout.max);
    const añoMin = Number(rangoAniosScout.min);

    for (let y = añoMax; y >= añoMin; y--) {
        const option = document.createElement("option");
        option.value = y;
        option.textContent = y;
        selectAnio.appendChild(option);
    }
});

// Bloque 3: Evitar combinación incoherente "Todas" + individuales.
document.addEventListener("DOMContentLoaded", function(){

    // Todos los checkboxes dentro del div de secciones múltiples
    const checkboxes = document.querySelectorAll(".secciones-multiples input[type=checkbox]");

    // Checkbox de "Todas las secciones"
    const todas = Array.from(checkboxes).find(cb => cb.value === "colonia,manada,tropa,posta,rutas");

    checkboxes.forEach(cb => {
        cb.addEventListener("change", function(){
            if(this === todas && this.checked){
                // Si marcamos "Todas", desmarcar el resto
                checkboxes.forEach(c => { if(c !== todas) c.checked = false; });
            } else if(this !== todas && this.checked){
                // Si marcamos alguna individual, desmarcar "Todas"
                todas.checked = false;
            }
        });
    });

});
</script>