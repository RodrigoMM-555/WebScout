<!-- ============================================================
     leer.php — Vista de listado/tabla CRUD para cualquier tabla
     ============================================================
     Muestra filtros de orden, chips de sección (educandos),
     tabla de datos con permisos inline y acciones editar/eliminar.
-->
<?php
// conexion_bd.php ya carga config.php (con las constantes PERM_*)
include_once "../inc/conexion_bd.php";

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

$opcionesOrden = [];
$columnas_result = $conexion->query("SHOW COLUMNS FROM `{$tabla}`");

if ($columnas_result) {
    while ($col = $columnas_result->fetch_assoc()) {

        // olumnas ocultas
        if (in_array($col['Field'], [
            'email2', 'telefono2', 'nombre2', 'apellidos2',
            'apellidos', 'id_usuario', 'permisos', 'id'
        ])) {
            continue;
        }

        $nombre = $col['Field'];
        $label = ucfirst(str_replace('_', ' ', $nombre));

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
<div class="subtablas-seccion">
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
<?php endif; ?>

<table>
<?php

$resultado = $conexion->query("SELECT * FROM `{$tabla}` LIMIT 1;");
$pintoCabecera = false;

if ($resultado && $resultado->num_rows > 0) {
    $filaEjemplo = $resultado->fetch_assoc();
    echo "<tr>";

    foreach ($filaEjemplo as $clave => $valor) {

        if (in_array($clave, [
            'email2', 'telefono2', 'nombre2', 'apellidos2',
            'apellidos', 'permisos', 'id_usuario', 'id'
        ])) {
            continue;
        }

        if ($clave === "anio") {
            echo "<th>Año</th>";
        } else {
            echo "<th>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $clave))) . "</th>";
        }
    }

    if ($tabla === "educandos") {
        echo "<th>WatsApp</th>";
        echo "<th>Irse solo</th>";
        echo "<th>Imagen</th>";
        echo "<th>Vehiculo privado</th>";
        echo "<th>Mas info</th>";
    }

    echo "<th>Editar</th><th>Eliminar</th>";
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

        foreach ($fila as $clave => $valor) {

            if (in_array($clave, [
                'email2', 'telefono2', 'nombre2', 'apellidos2',
                'apellidos', 'id_usuario', 'permisos', 'id'
            ])) {
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
              . '&amp;ordenar_por=' . urlencode($ordenarPor) . '&amp;direccion=' . urlencode($direccion) . '"></a></td>';

        // Enlace de eliminar con CSRF token y confirmación JS
        $urlEliminar = 'controladores/procesaeliminar.php?tabla=' . urlencode($tabla)
                     . '&id=' . (int)$fila['id']
                     . '&ordenar_por=' . urlencode($ordenarPor)
                     . '&direccion=' . urlencode($direccion)
                     . '&csrf_token=' . urlencode($csrfToken);
          echo '<td><a class="eliminar" href="' . htmlspecialchars($urlEliminar) . '"'
              . ' onclick="return confirm(\'¿Seguro que quieres eliminar este registro?\')"'
              . '></a></td>';

        echo "</tr>";

        if ($tabla === "avisos") {
            echo "<tr><td colspan='100%'><a href='asistencia_documentacion.php?id_aviso=" . (int)$fila['id'] . "'>Ver asistencia y documentación</a></td></tr>";
        }
    }
}
?>
</table>

<!-- QActualizacion automatica por medio de fetch -->
<script>
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
</script>
