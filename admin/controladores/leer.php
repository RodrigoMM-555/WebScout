<!-- Pintar las tablas -->
<?php
include_once "../inc/conexion.php";

// Recogemos parámetros
$ordenarPor    = $_GET['ordenar_por'] ?? 'id';
$direccion     = $_GET['direccion'] ?? 'ASC'; // ASC o DESC
$tabla         = $_GET['tabla'] ?? 'educandos';
$seccionFiltro = $_GET['seccion'] ?? null;

// Saneamos valores básicos
$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
$direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

// Obtener columnas dinámicamente para: 1) pintar botones de orden; 2) validar ordenarPor
$opcionesOrden = [];
$columnas_result = $conexion->query("SHOW COLUMNS FROM `{$tabla}`");
if ($columnas_result) {
    while ($col = $columnas_result->fetch_assoc()) {
        // Excluir columnas que no queremos mostrar
        if (in_array($col['Field'], ['email2', 'telefono2', 'nombre2', 'apellidos2'])) {
            continue;
        }
        $nombre = $col['Field'];
        $label = ucfirst(str_replace('_', ' ', $nombre));
        if ($nombre === "id_usuario") {
            $label = "Madre/Padre";
        } elseif ($nombre === "anio") {
            $label = "Año";
        }
        $opcionesOrden[$nombre] = $label;
    }
}

// Si la columna de orden no existe, caemos a 'id' si existe, o a la primera
if (!array_key_exists($ordenarPor, $opcionesOrden)) {
    $ordenarPor = array_key_exists('id', $opcionesOrden) ? 'id' : array_key_first($opcionesOrden);
}
?>

<!-- Filtros de ordenacion -->
<div class="tabla-controles" style="display:flex; align-items:center; margin-bottom:10px; gap:10px; flex-wrap:wrap;">
    <!-- Botones de ordenación dinámicos -->
    <div style="display:flex; gap:5px; flex-wrap:wrap;">
        <?php foreach ($opcionesOrden as $columna => $label): 
            $nuevaDireccion = ($ordenarPor === $columna && $direccion === 'ASC') ? 'DESC' : 'ASC';
            $href = "?tabla=" . urlencode($tabla) .
                    "&ordenar_por=" . urlencode($columna) .
                    "&direccion=" . urlencode($nuevaDireccion) .
                    ($seccionFiltro ? "&seccion=" . urlencode($seccionFiltro) : "");
        ?>
            <a href="<?= htmlspecialchars($href) ?>"
               style="padding:5px 10px; background:#555; color:#fff; text-decoration:none; border-radius:4px; <?= $ordenarPor===$columna ? 'background:#007bff;' : '' ?>">
                <?= htmlspecialchars($label) ?> <?= ($ordenarPor === $columna) ? ($direccion === 'ASC' ? '↑' : '↓') : '' ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Botón de insertar -->
    <a href="?operacion=insertar&amp;tabla=<?= htmlspecialchars($tabla) ?>" class="boton_insertar" 
       style="margin-left:auto; padding:5px 10px; background:green; color:#fff; text-decoration:none; border-radius:4px;">+</a>
</div>

<!-- Tabla -->
<table>
    <?php
        // Cabecera: si hay al menos una fila, usamos sus claves. Si no, usamos SHOW COLUMNS.
        $resultado = $conexion->query("SELECT * FROM `{$tabla}` LIMIT 1;");
        $pintoCabecera = false;

        if ($resultado && $resultado->num_rows > 0) {
            $filaEjemplo = $resultado->fetch_assoc();
            echo "<tr>";
            foreach ($filaEjemplo as $clave => $valor) {
                if (in_array($clave, ['email2', 'telefono2', 'nombre2', 'apellidos2'])) {
                    continue;
                }
                if ($clave === "id_usuario") {
                    echo "<th>Madre/Padre</th>";
                    continue;
                } elseif ($clave === "anio") {
                    echo "<th>Año</th>";
                    continue;
                } else {
                    $claveLegible = ucfirst(str_replace('_', ' ', $clave));
                    echo "<th>" . htmlspecialchars($claveLegible) . "</th>";
                }
            }
            echo "<th>Editar</th><th>Eliminar</th>";
            echo "</tr>";
            $pintoCabecera = true;
        }

        if (!$pintoCabecera && !empty($opcionesOrden)) {
            echo "<tr>";
            foreach ($opcionesOrden as $col => $lbl) {
                echo "<th>" . htmlspecialchars($lbl) . "</th>";
            }
            echo "<th>Editar</th><th>Eliminar</th>";
            echo "</tr>";
        }

        // ORDER BY especial para 'seccion'
        if ($ordenarPor === 'seccion') {
            $orderBy = "FIELD(seccion, 'colonia', 'manada', 'tropa', 'posta', 'rutas') {$direccion}";
        } else {
            $orderBy = "`{$ordenarPor}` {$direccion}";
        }

        // Consulta de filas (con / sin filtro por seccion)
        if ($seccionFiltro !== null && $seccionFiltro !== '') {
            $seccionFiltroSQL = $conexion->real_escape_string($seccionFiltro);
            $sqlListado = "SELECT * FROM `{$tabla}` WHERE seccion = '{$seccionFiltroSQL}' ORDER BY {$orderBy};";
        } else {
            $sqlListado = "SELECT * FROM `{$tabla}` ORDER BY {$orderBy};";
        }
        $resultadoListado = $conexion->query($sqlListado);

        // Colores por sección
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
                    if (in_array($clave, ['email2', 'telefono2', 'nombre2', 'apellidos2'])) {
                        continue;
                    }
                    if ($clave === "id_usuario") {
                        $id = (int)$valor;
                        $resNombre = $conexion->query("SELECT nombre, apellidos FROM `usuarios` WHERE id = {$id};");
                        $user = $resNombre ? $resNombre->fetch_assoc() : null;
                        $nombreComp = $user ? trim(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? '')) : '';
                        echo "<td>" . htmlspecialchars($nombreComp) . "</td>";
                    } else {
                        echo "<td>" . htmlspecialchars((string)$valor) . "</td>";
                    }
                }

                // Acciones
                echo '<td><a href="?operacion=actualizar&amp;tabla=' . urlencode($tabla) . '&amp;id=' . (int)$fila['id'] . '">📝</a></td>';
                echo '<td><a class="eliminar" href="controladores/procesaeliminar.php?tabla=' . urlencode($tabla) . '&amp;id=' . (int)$fila['id'] . '">❌</a></td>';
                echo "</tr>";

                // Bloque para "avisos" circulares: pintar lista de educandos y si entregaron
                if ($tabla === "avisos" && isset($fila["circular"]) && $fila["circular"] === "si") {
                    // Obtenemos las secciones y el título del aviso
                    $avisoId = (int)$fila['id'];
                    $stmt = $conexion->prepare("SELECT secciones, titulo FROM `avisos` WHERE id = ?");
                    $stmt->bind_param("i", $avisoId);
                    $stmt->execute();
                    $resAviso = $stmt->get_result();
                    $datosAviso = $resAviso ? $resAviso->fetch_assoc() : null;
                    $stmt->close();

                    $seccionesCSV = $datosAviso['secciones'] ?? '';
                    $tituloAviso  = $datosAviso['titulo'] ?? '';

                    $seccionesArray = array_filter(array_map('trim', explode(',', $seccionesCSV)));
                    $aviso_educandos = [];

                    if (!empty($seccionesArray)) {
                        $stmtEdu = $conexion->prepare("SELECT nombre, apellidos FROM `educandos` WHERE seccion = ?");
                        foreach ($seccionesArray as $sec) {
                            $secTrim = (string)$sec;
                            $stmtEdu->bind_param("s", $secTrim);
                            $stmtEdu->execute();
                            $educandos_result = $stmtEdu->get_result();
                            while ($a = $educandos_result->fetch_assoc()) {
                                $aviso_educandos[] = $a;
                            }
                        }
                        $stmtEdu->close();
                    }

                    // Pintamos la subtabla con entregas
                    echo "<tr class='" . htmlspecialchars($claseFila) . "'>";
                    echo "<td>" . (int)$fila['id'] . "</td>";
                    echo "<td colspan='100%'>";

                    echo "<table class='tabla-archivos'>
                            <tr>
                                <th>Niñ@</th>
                                <th>Entregado</th>
                            </tr>";

                    $vistos = [];
                    $lista_nombres = []; // Inicializar
                    foreach ($aviso_educandos as $edu) {
                        $nombreCompleto = trim(($edu['nombre'] ?? '') . '_' . ($edu['apellidos'] ?? ''));
                        if ($nombreCompleto === '' || isset($vistos[$nombreCompleto])) {
                            continue;
                        }
                        $vistos[$nombreCompleto] = true;
                        $lista_nombres[] = $nombreCompleto;
                    }

                    foreach ($lista_nombres as $nombreCompleto) {
                        // Comprobamos entrega
                        $nombreCarpeta = function_exists('limpiarTexto') ? limpiarTexto($nombreCompleto) : $nombreCompleto;
                        $tituloLimpio  = function_exists('limpiarTexto') ? limpiarTexto($tituloAviso) : $tituloAviso;

                        $ruta = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreCarpeta;

                        $entregado = false;
                        if (is_dir($ruta)) {
                            $archivos = @scandir($ruta);
                            if ($archivos !== false) {
                                $archivos = array_diff($archivos, ['.', '..']);
                                $prefijo = $tituloLimpio . '_' . $nombreCarpeta . '.';
                                foreach ($archivos as $f) {
                                    if (strpos($f, $prefijo) === 0) {
                                        $entregado = true;
                                        break;
                                    }
                                }
                            }
                        }

                        $filaClaseEntrega = $entregado ? "tr-entregado" : "tr-pendiente";
                        $nombreCompleto = str_replace('_', ' ', $nombreCompleto);

                        echo "<tr class='" . htmlspecialchars($filaClaseEntrega) . "'>
                                <td>" . htmlspecialchars($nombreCompleto) . "</td>
                                <td>" . ($entregado
                                    ? "<span style='color:green; font-weight:bold;'>Sí</span>"
                                    : "<span style='color:red; font-weight:bold;'>No</span>"
                                ) . "</td>
                              </tr>";
                    }

                    echo "</table>";
                    echo "</td></tr>";
                }
            }
        }
    ?>
</table>

<script>
document.querySelectorAll('.eliminar').forEach(function(enlace) {
    enlace.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm("¿Estás seguro de que quieres eliminar este elemento?")) {
            window.location.href = this.href;
        }
    });
});
</script>