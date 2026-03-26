<!-- ============================================================
     leer.php — Vista de listado/tabla CRUD para cualquier tabla
     ============================================================
     Muestra filtros de orden, chips de sección (educandos),
     tabla de datos con permisos inline y acciones editar/eliminar.
-->
<?php
// conexion_bd.php ya carga config.php (con las constantes PERM_*)
include_once "../../inc/conexion_bd.php";

// Necesitamos el token CSRF para los enlaces de eliminar
if (session_status() === PHP_SESSION_NONE) session_start();
$csrfToken = generarTokenCSRF();

// Mostrar mensaje flash si existe (ej: "Registro eliminado correctamente")
mostrarFlash();

// Recogemos parámetros
$ordenarPor    = $_GET['ordenar_por'] ?? 'id';
$direccion     = $_GET['direccion'] ?? 'ASC';
$tabla         = $_GET['tabla'] ?? 'educandos';
$seccionFiltro = $_GET['seccion'] ?? null;

$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
$direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

if ($tabla === 'educandos') {
    $cursoScoutActual = obtenerCursoScoutActual();
    $cursoSincronizadoSesion = (int)($_SESSION['curso_scout_sincronizado'] ?? 0);
    if ($cursoSincronizadoSesion !== $cursoScoutActual) {
        sincronizarSeccionesEducandos($conexion, $cursoScoutActual);
        $_SESSION['curso_scout_sincronizado'] = $cursoScoutActual;
    }
}

$normalizarEtiqueta = static function (string $texto): string {
    return str_ireplace(['nino', 'ninio'], 'niño', $texto);
};

$columnasOcultasOrden = [
    'email2', 'telefono2', 'nombre2', 'apellidos2',
    'apellidos', 'id_usuario', 'permisos', 'cambio_contraseña', 'id', 'contraseña'
];

$columnasOcultasTabla = [
    'email2', 'telefono2', 'nombre2', 'apellidos2',
    'apellidos', 'id_usuario', 'permisos', 'cambio_contraseña', 'id', 'contraseña'
];

if ($tabla === 'lista_espera') {
    $columnasOcultasOrden[] = 'apellidos_nino';
    $columnasOcultasOrden[] = 'comentarios';
    $columnasOcultasOrden[] = 'explicacion_relacion';
    $columnasOcultasOrden[] = 'hermano_en_grupo';
    $columnasOcultasOrden[] = 'relacion_con_miembro';
    $columnasOcultasOrden[] = 'familia_antiguo_scouter';
    $columnasOcultasOrden[] = 'estuvo_en_grupo';

    $columnasOcultasTabla[] = 'apellidos_nino';
    $columnasOcultasTabla[] = 'comentarios';
    $columnasOcultasTabla[] = 'explicacion_relacion';
}

$ordenColumnasListaEspera = [
    'nombre_nino',
    'fecha_nacimiento',
    'nombre_contacto',
    'telefono_contacto',
    'correo_contacto',
    'hermano_en_grupo',
    'relacion_con_miembro',
    'familia_antiguo_scouter',
    'estuvo_en_grupo'
];

$etiquetasListaEspera = [
    'nombre_nino' => 'Niño',
    'fecha_nacimiento' => 'Nacimiento',
    'nombre_contacto' => 'Contacto',
    'telefono_contacto' => 'Teléfono',
    'correo_contacto' => 'Correo',
    'hermano_en_grupo' => 'Hermano',
    'relacion_con_miembro' => 'Relación',
    'familia_antiguo_scouter' => 'Familia scout',
    'estuvo_en_grupo' => 'Estuvo antes'
];

if ($tabla === "avisos") {
    echo '<div class="avisos-botones-wrap" style="margin-bottom:18px;display:flex;gap:12px;justify-content:center;">';
    echo '<button type="button" class="btn-avisos-calendario">Ver calendario</button>';
    echo '<button type="button" class="btn-avisos-tabla activo">Ver tabla</button>';
    echo '</div>';
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnCalendario = document.querySelector(".btn-avisos-calendario");
        const btnTabla = document.querySelector(".btn-avisos-tabla");
        const calendario = document.querySelector(".admin-calendario, .calendario-trimestre");
        const tabla = document.querySelector("table");
        const filtros = document.querySelector(".tabla-controles");
        if (calendario) calendario.style.display = "none";
        if (tabla) tabla.style.display = "";
        if (filtros) filtros.style.display = "";
        btnCalendario.addEventListener("click", function() {
            btnCalendario.classList.add("activo");
            btnTabla.classList.remove("activo");
            if (calendario) calendario.style.display = "";
            if (tabla) tabla.style.display = "none";
            if (filtros) filtros.style.display = "none";
        });
        btnTabla.addEventListener("click", function() {
            btnTabla.classList.add("activo");
            btnCalendario.classList.remove("activo");
            if (calendario) calendario.style.display = "none";
            if (tabla) tabla.style.display = "";
            if (filtros) filtros.style.display = "";
        });
    });
    </script>';
}

$opcionesOrden = [];
$columnas_result = $conexion->query("SHOW COLUMNS FROM `{$tabla}`");

if ($columnas_result) {
    while ($col = $columnas_result->fetch_assoc()) {

        // olumnas ocultas
        if (in_array($col['Field'], $columnasOcultasOrden, true)) {
            continue;
        }

        $nombre = $col['Field'];
        $label = ucfirst($normalizarEtiqueta(str_replace('_', ' ', $nombre)));

        if ($tabla === 'lista_espera' && $nombre === 'nombre_nino') {
            $label = 'Niño';
        }

        if ($nombre === "anio") {
            $label = "Año";
        }

        $opcionesOrden[$nombre] = $label;
    }
}

if (!array_key_exists($ordenarPor, $opcionesOrden)) {
    $ordenarPor = array_key_exists('id', $opcionesOrden) ? 'id' : array_key_first($opcionesOrden);
}
?>

<!-- Filtros y ordenación -->
<div class="tabla-controles">
    <div class="orden-botones">
        <?php foreach ($opcionesOrden as $columna => $label): 
            $nuevaDireccion = ($ordenarPor === $columna && $direccion === 'ASC') ? 'DESC' : 'ASC';
            $href = "?tabla=" . urlencode($tabla) .
                    "&ordenar_por=" . urlencode($columna) .
                    "&direccion=" . urlencode($nuevaDireccion) .
                    ($seccionFiltro ? "&seccion=" . urlencode($seccionFiltro) : "");
            $claseOrden = "orden-link" . ($ordenarPor === $columna ? " activo" : "");
        ?>
            <a href="<?= htmlspecialchars($href) ?>"
               class="<?= $claseOrden ?>">
                <?= htmlspecialchars($label) ?> <?= ($ordenarPor === $columna) ? ($direccion === 'ASC' ? '↑' : '↓') : '' ?>
            </a>
        <?php endforeach; ?>
    </div>

    <a href="?operacion=insertar&amp;tabla=<?= htmlspecialchars($tabla) ?>&seccion=<?= htmlspecialchars($seccionFiltro) ?>&ordenar_por=<?= htmlspecialchars($ordenarPor) ?>&direccion=<?= htmlspecialchars($direccion) ?>" 
         class="boton_insertar">+</a>
</div>

<?php if ($tabla === 'educandos'): ?>
<?php
    $urlSync = "?operacion=sincronizar_secciones"
             . "&csrf_token=" . urlencode($csrfToken)
             . "&tabla=" . urlencode($tabla)
             . "&ordenar_por=" . urlencode($ordenarPor)
             . "&direccion=" . urlencode($direccion);
?>
<div class="subtablas-seccion">
    <div class="subtablas-seccion-izq">
    <?php
        $secciones = [
            'colonia' => 'Colonia',
            'manada' => 'Manada',
            'tropa' => 'Tropa',
            'posta' => 'Posta',
            'rutas' => 'Rutas'
        ];

        $hrefTodas = "?tabla=" . urlencode($tabla) .
                     "&ordenar_por=" . urlencode($ordenarPor) .
                     "&direccion=" . urlencode($direccion);
    ?>
    <a href="<?= htmlspecialchars($hrefTodas) ?>" class="chip-seccion <?= empty($seccionFiltro) ? 'activo' : '' ?>">Todas</a>

    <?php foreach ($secciones as $claveSeccion => $nombreSeccion):
        $hrefSeccion = "?tabla=" . urlencode($tabla) .
                      "&ordenar_por=" . urlencode($ordenarPor) .
                      "&direccion=" . urlencode($direccion) .
                      "&seccion=" . urlencode($claveSeccion);
        $clases = "chip-seccion seccion-" . $claveSeccion . (($seccionFiltro === $claveSeccion) ? " activo" : "");
    ?>
        <a href="<?= htmlspecialchars($hrefSeccion) ?>" class="<?= htmlspecialchars($clases) ?>"><?= htmlspecialchars($nombreSeccion) ?></a>
    <?php endforeach; ?>
    </div>
    <a href="<?= htmlspecialchars($urlSync) ?>" class="orden-link sync-secciones-btn" title="Recalcular secciones scout">Sincronizar secciones</a>
</div>
<?php endif; ?>

<table class="<?= $tabla === 'lista_espera' ? 'tabla-lista-espera' : '' ?>">
<?php

$resultado = $conexion->query("SELECT * FROM `{$tabla}` LIMIT 1;");
$pintoCabecera = false;
$totalColumnasCabecera = 0;

if ($resultado && $resultado->num_rows > 0) {
    $filaEjemplo = $resultado->fetch_assoc();
    echo "<tr>";

    if ($tabla === 'lista_espera') {
        foreach ($ordenColumnasListaEspera as $clave) {
            if (!array_key_exists($clave, $filaEjemplo)) {
                continue;
            }
            echo "<th>" . htmlspecialchars($etiquetasListaEspera[$clave] ?? ucfirst($normalizarEtiqueta(str_replace('_', ' ', $clave)))) . "</th>";
            $totalColumnasCabecera++;
        }
    } else {
        foreach ($filaEjemplo as $clave => $valor) {

            if (in_array($clave, $columnasOcultasTabla, true)) {
                continue;
            }

            if ($clave === "anio") {
                echo "<th>Año</th>";
            } else {
                echo "<th>" . htmlspecialchars(ucfirst($normalizarEtiqueta(str_replace('_', ' ', $clave)))) . "</th>";
            }
            $totalColumnasCabecera++;
        }
    }

    if ($tabla === "educandos") {
        echo "<th>WatsApp</th>";
        echo "<th>Irse solo</th>";
        echo "<th>Imagen</th>";
        echo "<th>Vehiculo privado</th>";
        echo "<th>Mas info</th>";
        $totalColumnasCabecera += 5;
    }

    if ($tabla === 'lista_espera') {
        echo "<th>Detalle</th>";
        $totalColumnasCabecera++;
    }

    echo "<th>Editar</th><th>Eliminar</th>";
    $totalColumnasCabecera += 2;
    echo "</tr>";
    $pintoCabecera = true;
}

// Orden especial sección
if ($ordenarPor === 'seccion') {
    $orderBy = "FIELD(seccion, 'colonia', 'manada', 'tropa', 'posta', 'rutas') {$direccion}";
} else {
    $orderBy = "`{$ordenarPor}` {$direccion}";
}

if ($seccionFiltro !== null && $seccionFiltro !== '') {
    $seccionFiltroSQL = $conexion->real_escape_string($seccionFiltro);
    $sqlListado = "SELECT * FROM `{$tabla}` WHERE seccion = '{$seccionFiltroSQL}' ORDER BY {$orderBy};";
} else {
    $sqlListado = "SELECT * FROM `{$tabla}` ORDER BY {$orderBy};";
}

$resultadoListado = $conexion->query($sqlListado);

$coloresSeccion = [
    "colonia" => "seccion-colonia",
    "manada"  => "seccion-manada",
    "tropa"   => "seccion-tropa",
    "posta"   => "seccion-posta",
    "rutas"   => "seccion-rutas"
];

if ($resultadoListado) {
    while ($fila = $resultadoListado->fetch_assoc()) {

        $claseFila = "";
        if (isset($fila['seccion'])) {
            $seccionValor = strtolower((string)$fila['seccion']);
            if (isset($coloresSeccion[$seccionValor])) {
                $claseFila = $coloresSeccion[$seccionValor];
            }
        }

        echo "<tr class='" . htmlspecialchars($claseFila) . "'>";

        if ($tabla === 'lista_espera') {
            $comentariosDetalle = trim((string)($fila['comentarios'] ?? ''));
            $explicacionDetalle = trim((string)($fila['explicacion_relacion'] ?? ''));
            $tieneDetalle = ($comentariosDetalle !== '' || $explicacionDetalle !== '');

            foreach ($ordenColumnasListaEspera as $clave) {
                if (!array_key_exists($clave, $fila)) {
                    continue;
                }

                $valor = $fila[$clave];

                if ($clave === 'nombre_nino') {
                    $apellidosNinio = trim((string)($fila['apellidos_nino'] ?? ''));
                    $nombreNinio = trim((string)($fila['nombre_nino'] ?? ''));
                    $valor = trim($nombreNinio . ' ' . $apellidosNinio);
                }

                if ($clave === 'fecha_nacimiento' && !empty($valor)) {
                    $timestampNacimiento = strtotime((string)$valor);
                    if ($timestampNacimiento !== false) {
                        $valor = date('d/m/Y', $timestampNacimiento);
                    }
                }

                if (in_array($clave, ['hermano_en_grupo', 'relacion_con_miembro', 'familia_antiguo_scouter', 'estuvo_en_grupo'], true)) {
                    $checked = ((int)$valor === 1) ? 'checked' : '';
                    echo "<td><input type='checkbox' class='estado-check' $checked disabled></td>";
                    continue;
                }

                $valorCelda = htmlspecialchars((string)($valor ?: '-'));
                if (!in_array($clave, ['correo_contacto'], true)) {
                    $valorCelda = ucfirst($valorCelda);
                }

                echo "<td>" . $valorCelda . "</td>";
            }

            if ($tieneDetalle) {
                echo "<td><button type='button' class='btn-detalle-lista sin-icono-auto' data-detalle-id='" . (int)$fila['id'] . "' aria-expanded='false' title='Ver detalle'>⏬</button></td>";
            } else {
                echo "<td></td>";
            }
        } else {
            foreach ($fila as $clave => $valor) {

                if (in_array($clave, $columnasOcultasTabla, true)) {
                    continue;
                }

                elseif ($clave === "nombre") {
                    $valor = htmlspecialchars($valor) . (isset($fila['apellidos']) ? " " . ucfirst(htmlspecialchars($fila['apellidos'])) : "");
                }

                // ★ FIX: paréntesis para evaluar ambas condiciones correctamente
            else if (($clave === "fecha_hora_inicio" || $clave === "fecha_hora_fin") && !empty($valor)) {
                    $valor = date("d/m/Y", strtotime($valor)) . " " . date("H:i", strtotime($valor));
                }

                $valorCelda = htmlspecialchars($valor ?: '-');
                if (!in_array($clave, ['email', 'email2'], true)) {
                    $valorCelda = ucfirst($valorCelda);
                }
                echo "<td>" . $valorCelda . "</td>";
            }
        }

        if ($tabla === "educandos") {

            $permisos = (int)$fila['permisos'];

            $checkedWhatsapp = ($permisos & PERM_WHATSAPP) ? "checked" : "";
            $checkedSolo     = ($permisos & PERM_SOLO) ? "checked" : "";
            $checkedFotos    = ($permisos & PERM_FOTOS) ? "checked" : "";
            $checkedCoche    = ($permisos & PERM_COCHE) ? "checked" : "";

            echo "<td><input type='checkbox' class='permiso-check' data-id='{$fila['id']}' data-permiso='2' $checkedWhatsapp></td>";
            echo "<td><input type='checkbox' class='permiso-check' data-id='{$fila['id']}' data-permiso='4' $checkedSolo></td>";
            echo "<td><input type='checkbox' class='permiso-check' data-id='{$fila['id']}' data-permiso='8' $checkedFotos></td>";
            echo "<td><input type='checkbox' class='permiso-check' data-id='{$fila['id']}' data-permiso='1' $checkedCoche></td>";
            echo '<td><a href="info_educandos.php?id_educando=' . (int)$fila['id'] . '">Info</a></td>';
        }

          echo '<td><a href="?operacion=actualizar&amp;tabla=' . urlencode($tabla) . '&amp;id=' . (int)$fila['id']
              . '&amp;seccion=' . urlencode((string)($seccionFiltro ?? ''))
              . '&amp;ordenar_por=' . urlencode($ordenarPor) . '&amp;direccion=' . urlencode($direccion) . '"></a></td>';

        // Enlace de eliminar con CSRF token y confirmación JS
        $urlEliminar = 'controladores/procesaeliminar.php?tabla=' . urlencode($tabla)
                     . '&id=' . (int)$fila['id']
                     . '&seccion=' . urlencode((string)($seccionFiltro ?? ''))
                     . '&ordenar_por=' . urlencode($ordenarPor)
                     . '&direccion=' . urlencode($direccion)
                     . '&csrf_token=' . urlencode($csrfToken);
          echo '<td><a class="eliminar" href="' . htmlspecialchars($urlEliminar) . '"'
              . ' onclick="return confirm(\'¿Seguro que quieres eliminar este registro?\')"'
              . '></a></td>';

        echo "</tr>";

        if ($tabla === 'lista_espera' && $tieneDetalle) {
            echo "<tr id='detalle-fila-" . (int)$fila['id'] . "' class='detalle-lista-fila' style='display:none;'>";
            echo "<td colspan='" . (int)$totalColumnasCabecera . "'>";
            echo "<div class='detalle-lista-contenido'>";

            if ($explicacionDetalle !== '') {
                echo "<p><strong>Explicación de la relación:</strong> " . nl2br(htmlspecialchars($explicacionDetalle)) . "</p>";
            }

            if ($comentariosDetalle !== '') {
                echo "<p><strong>Comentarios:</strong> " . nl2br(htmlspecialchars($comentariosDetalle)) . "</p>";
            }

            echo "</div>";
            echo "</td>";
            echo "</tr>";
        }

        if ($tabla === "avisos") {
            echo "<tr><td colspan='100%'><a href='asistencia_documentacion.php?id_aviso=" . (int)$fila['id'] . "&ordenar_por=" . htmlspecialchars($_GET['ordenar_por'] ?? 'id') . "&direccion=" . htmlspecialchars($_GET['direccion'] ?? 'ASC') . "'>Ver asistencia y documentación</a></td></tr>";
        }
    }
}
?>
</table>

<?php
if ($tabla === "avisos") {
    echo "<div class='admin-calendario' style='display: none;'>";
    global $conexion;
    include_once __DIR__ . "/../../inc/calendario.php";
    echo "</div>";
}
?>



<!--
-
    JavaScript de tabla admin
    =========================
    - Toggle de permisos por fetch (actualización instantánea).
    - Mostrar/ocultar detalle extendido en filas de lista de espera.
-->
<script>
// Cada checkbox representa un bit de permiso (1,2,4,8).
// El backend hace XOR para activar/desactivar ese bit sin recargar.
document.querySelectorAll(".permiso-check").forEach(function(checkbox){

    checkbox.addEventListener("change", function(){

        const id = this.dataset.id;
        const permiso = this.dataset.permiso;

        fetch("controladores/actualizar_permiso.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "id=" + id + "&permiso=" + permiso
        });

    });

});

// Expande o contrae fila de detalle para campos largos (explicación/comentarios).
document.querySelectorAll('.btn-detalle-lista').forEach(function(boton) {
    boton.addEventListener('click', function() {
        const id = this.dataset.detalleId;
        const filaDetalle = document.getElementById('detalle-fila-' + id);
        if (!filaDetalle) return;

        const visible = filaDetalle.style.display !== 'none';
        filaDetalle.style.display = visible ? 'none' : 'table-row';
        this.setAttribute('aria-expanded', visible ? 'false' : 'true');
        this.textContent = visible ? '⏬' : '⏫';
    });
});
</script>

<script>
// Guardar posición de scroll antes de entrar a un formulario de insertar/editar.
document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href') || '';
    if (href.indexOf('operacion=insertar') !== -1 || href.indexOf('operacion=actualizar') !== -1) {
        sessionStorage.setItem('adminScrollRestore', Math.round(window.scrollY));
    }
});
// Restaurar scroll al volver desde un formulario (post redirect).
(function () {
    var y = sessionStorage.getItem('adminScrollRestore');
    if (y !== null) {
        sessionStorage.removeItem('adminScrollRestore');
        window.scrollTo(0, parseInt(y, 10));
    }
})();
</script>
