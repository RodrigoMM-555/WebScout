<!-- 
 ============================================================
 actualizar.php — Formulario dinámico para editar registros
============================================================
 * Este archivo genera un formulario para actualizar registros de cualquier tabla permitida.
 * Recibe el nombre de la tabla y el id del registro a editar por GET.
 * Si hay id, carga los valores actuales y los muestra en el formulario.
 * Usa DESCRIBE para obtener la estructura de la tabla y renderizar los campos dinámicamente.
 * Incluye lógica especial para campos de sección, checkboxes, fechas, permisos y normalización de nombres.
-->
<?php
$tabla = $_GET['tabla'];
$id = $_GET['id'] ?? 0; // Obtenemos el ID del registro a actualizar (nuevo)
$esListaEspera = ($tabla === 'lista_espera');
$camposCheckboxListaEspera = ['hermano_en_grupo', 'relacion_con_miembro', 'familia_antiguo_scouter', 'estuvo_en_grupo'];

// Recoge el nombre de la tabla y el id del registro a actualizar
// Detecta si es la tabla especial 'lista_espera' para lógica de checkboxes

$normalizarTexto = static function (string $texto): string {
    return str_ireplace(['ninio', 'nino'], 'niño', $texto);
};

// Función para normalizar nombres de campos y etiquetas (convierte 'nino' a 'niño')

$nombreTablaBonito = ucfirst($normalizarTexto(str_replace('_', ' ', $tabla)));
echo "<h1 class='titulo-form-admin'>" . ($id ? "Actualizar" : "Insertar") . " en " . htmlspecialchars($nombreTablaBonito) . "</h1>";

// Renderiza el título del formulario según si es actualización o inserción

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

// Si se pasa un id, consulta los valores actuales del registro para pre-rellenar el formulario
?>
<form action="?operacion=procesaactualizar&tabla=<?= htmlspecialchars($_GET['tabla']) ?>&seccion=<?= htmlspecialchars($_GET['seccion'] ?? '') ?>&ordenar_por=<?= htmlspecialchars($_GET['ordenar_por'] ?? 'id') ?>&direccion=<?= htmlspecialchars($_GET['direccion'] ?? 'ASC') ?>" method="POST">
<?php
    // Token CSRF para proteger el formulario
    echo campoCSRF();

    // Campo oculto CSRF para proteger contra ataques de falsificación

    // Pedimos estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");
    while ($fila = $resultado->fetch_assoc()) {
        $clave = $fila['Field'];
        $tipoColumna = strtolower((string)$fila['Type']);
        $clave2 = ucfirst($normalizarTexto(str_replace('_', ' ', $clave)));

            // Recorre cada columna de la tabla y genera el campo correspondiente

        // ID
        if ($fila['Extra'] === 'auto_increment') {
            if ($id) {
                echo "<input type='hidden' name='id' value='$id'>";
            }
            continue;
        }

            // Si el campo es auto_increment, lo oculta o lo ignora en el formulario

        // SELECT SECCIÓN (añadido id='select-seccion')
        elseif ($clave === 'seccion') {
            $selected = $valores['seccion'] ?? '';
            if ($tabla === 'educandos') {
                echo "
                    <div class='control_formulario'>
                        <label>$clave2 <small>(automática por año)</small></label>
                        <select id='select-seccion' disabled>
                            <option value='colonia' " . ($selected=='colonia'?'selected':'') . ">Colonia</option>
                            <option value='manada' " . ($selected=='manada'?'selected':'') . ">Manada</option>
                            <option value='tropa' " . ($selected=='tropa'?'selected':'') . ">Tropa</option>
                            <option value='posta' " . ($selected=='posta'?'selected':'') . ">Posta</option>
                            <option value='rutas' " . ($selected=='rutas'?'selected':'') . ">Rutas</option>
                        </select>
                    </div>
                ";
                continue;
            }

                // Si es el campo 'seccion' de educandos, lo muestra como select deshabilitado

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

            // Si es el campo 'seccion' de otra tabla, muestra un select editable

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

            // Si el campo es 'secciones', muestra checkboxes para varias secciones

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

            // Si el campo es 'id_usuario', muestra un select con los usuarios de rol usuario

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

            // Si el campo es fecha/hora, muestra un input datetime-local

        // FECHA DE REGISTRO (solo fecha)
        elseif ($clave === 'fecha_registro') {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='date' name='$clave' value='" . (isset($valores[$clave]) && $valores[$clave] !== null ? date('Y-m-d', strtotime($valores[$clave])) : '') . "'>
                </div>
            ";
        }

            // Si el campo es 'fecha_registro', muestra un input date

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

            // Si el campo es 'anio', muestra un select vacío que se rellena por JS

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

            // Si el campo es 'permisos', muestra checkboxes para cada bit de permiso

        // CHECKBOXES LISTA DE ESPERA (agrupados y ordenados)
        elseif ($esListaEspera && in_array($clave, $camposCheckboxListaEspera, true)) {
            if ($clave !== 'hermano_en_grupo') {
                continue;
            }

                // Si es la tabla lista_espera, muestra checkboxes agrupados para relación con el grupo

            $checkedHermano = ((int)($valores['hermano_en_grupo'] ?? 0) === 1) ? 'checked' : '';
            $checkedRelacion = ((int)($valores['relacion_con_miembro'] ?? 0) === 1) ? 'checked' : '';
            $checkedFamilia = ((int)($valores['familia_antiguo_scouter'] ?? 0) === 1) ? 'checked' : '';
            $checkedEstuvo = ((int)($valores['estuvo_en_grupo'] ?? 0) === 1) ? 'checked' : '';

            echo "
                <div class='control_formulario secciones-multiples'>
                    <label>Relación con el grupo</label>
                    <label class='check-item'>
                        <input type='hidden' name='hermano_en_grupo' value='0'>
                        <input type='checkbox' name='hermano_en_grupo' value='1' $checkedHermano>
                        <span>Hermano en el grupo</span>
                    </label>
                    <label class='check-item'>
                        <input type='hidden' name='relacion_con_miembro' value='0'>
                        <input type='checkbox' name='relacion_con_miembro' value='1' $checkedRelacion>
                        <span>Relación con miembro</span>
                    </label>
                    <label class='check-item'>
                        <input type='hidden' name='familia_antiguo_scouter' value='0'>
                        <input type='checkbox' name='familia_antiguo_scouter' value='1' $checkedFamilia>
                        <span>Familia de antiguo scouter</span>
                    </label>
                    <label class='check-item'>
                        <input type='hidden' name='estuvo_en_grupo' value='0'>
                        <input type='checkbox' name='estuvo_en_grupo' value='1' $checkedEstuvo>
                        <span>Estuvo en el grupo antes</span>
                    </label>
                </div>
            ";
        }

        // USUARIOS: mostrar estado real de cambio_contraseña según BD.
        elseif ($tabla === 'usuarios' && $clave === 'cambio_contraseña') {
            $checked = ((int)($valores[$clave] ?? 0) === 1) ? 'checked' : '';
            echo "
                <div class='control_formulario booleano'>
                    <label class='check-item'>
                        <input type='hidden' name='$clave' value='0'>
                        <input type='checkbox' name='$clave' value='1' $checked>
                        <span>$clave2</span>
                    </label>
                </div>
            ";
        }

            // Si es el campo 'cambio_contraseña' de usuarios, muestra un checkbox booleano

        // BOOLEAN / TINYINT(1)
        elseif (preg_match('/^(tinyint\(1\)|boolean|bool)/', $tipoColumna)) {
            $checked = ((int)($valores[$clave] ?? 0) === 1) ? 'checked' : '';
            echo "
                <div class='control_formulario booleano'>
                    <label class='check-item'>
                        <input type='hidden' name='$clave' value='0'>
                        <input type='checkbox' name='$clave' value='1' $checked>
                        <span>$clave2</span>
                    </label>
                </div>
            ";
        }

            // Si el campo es booleano, muestra un checkbox

        // DATE
        elseif ($tipoColumna === 'date') {
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <input type='date' name='$clave' value='" . htmlspecialchars($valores[$clave] ?? '') . "'>
                </div>
            ";
        }

            // Si el campo es date, muestra un input date

        // TEXT/LONGTEXT
        elseif (str_contains($tipoColumna, 'text')) {
            $value = $valores[$clave] ?? '';
            echo "
                <div class='control_formulario'>
                    <label>$clave2</label>
                    <textarea name='$clave'>" . htmlspecialchars($value) . "</textarea>
                </div>
            ";
        }

            // Si el campo es text/longtext, muestra un textarea

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

            // Si el campo es 'circular', muestra un select sí/no

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

            // Si el campo es 'rol', muestra un select usuario/admin

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

            // Si el campo es 'tipo', muestra un select con los tipos de evento

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
            elseif (str_contains($clave, 'email') || str_contains($clave, 'correo')) {
                $type = 'email';
            }
            elseif (str_contains($clave, 'telefono')) {
                $type = 'tel';
            }
            echo "
            <div class='control_formulario'>
                <label>$clave2</label>
                <input type='{$type}' name='$clave' value='" . htmlspecialchars($value) . "'"
                . (str_contains($clave, 'telefono') ? " maxlength='20' inputmode='tel'" : "")
                . ($clave === 'contraseña' ? " placeholder='Dejar vacío para mantener la actual'" : "")
                . ">
            </div>";
        }

            // Para cualquier otro campo, muestra un input de tipo adecuado (text, email, tel, password)
    }
?>
    <div class="control_formulario botones-form">
        <input type="submit" value="<?= $id ? '💾 Actualizar' : '➕ Insertar' ?>">
        <a href="?tabla=<?= htmlspecialchars($_GET['tabla']) ?>&seccion=<?= htmlspecialchars($_GET['seccion'] ?? '') ?>&ordenar_por=<?= htmlspecialchars($_GET['ordenar_por'] ?? 'id') ?>&direccion=<?= htmlspecialchars($_GET['direccion'] ?? 'ASC') ?>" class="btn-cancelar">Cancelar</a>
    </div>
</form>

    <!-- Botones de acción: guardar o cancelar -->

<link rel="stylesheet" href="css/estilo.css">

<!-- Estilos del formulario -->

<!-- ============================
     SCRIPTS (UNIFICADOS Y CORREGIDOS)
============================= -->
<script>
// Valor existente en BD (si estamos editando un educando).
const anioGuardado = <?= isset($valores["anio"]) ? json_encode((int)$valores["anio"]) : 'null' ?>;
const cursoScout = <?= json_encode(obtenerCursoScoutActual()) ?>;
const rangoAniosScout = <?= json_encode(obtenerRangoAniosScout()) ?>;
const reglasSeccionScout = <?= json_encode(obtenerReglasSeccionScout(), JSON_UNESCAPED_UNICODE) ?>;

document.addEventListener("DOMContentLoaded", function () {

    const selectAnio = document.getElementById("select-anio");
    const selectSeccion = document.getElementById("select-seccion");

    function calcularSeccion(añoNacido) {
        if (!Number.isFinite(añoNacido)) return null;
        const dif = cursoScout - añoNacido;
        const regla = reglasSeccionScout.find(function (item) {
            return dif >= item.min && dif <= item.max;
        });
        return regla ? regla.seccion : null;
    }

    function actualizarSeccion() {
        if (!selectAnio || !selectSeccion) return;
        const y = Number(selectAnio.value);
        const sec = calcularSeccion(y);

        // Solo autocompleta cuando existe un mapeo válido.
        if (sec) selectSeccion.value = sec;
    }

    // === Generar años dinámicos ===
    if (selectAnio) {
        const max = Number(rangoAniosScout.max);
        const min = Number(rangoAniosScout.min);

        for (let y = max; y >= min; y--) {
            const option = document.createElement("option");
            option.value = y;
            option.textContent = y;

            if (anioGuardado && anioGuardado == y) {
                option.selected = true;
            }

            selectAnio.appendChild(option);
        }

        // Si había año guardado, sincronizamos sección al cargar.
        if (anioGuardado) actualizarSeccion();

        // Cambios manuales
        selectAnio.addEventListener("change", actualizarSeccion);
    }
});
</script>

// Script: autocompleta la sección scout según el año de nacimiento seleccionado
// Genera dinámicamente las opciones de año y sincroniza la sección al cambiar