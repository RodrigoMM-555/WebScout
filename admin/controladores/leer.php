<!-- Pintar las tablas -->
<?php
include_once "../inc/conexion.php";

// Recogemos parámetros de ordenación y filtro
$ordenarPor    = $_GET['ordenar_por'] ?? 'id';  // Columna por la que ordenar, por defecto 'id'
$direccion     = $_GET['direccion'] ?? 'ASC';   // Dirección de ordenamiento, ASC o DESC
$tabla         = $_GET['tabla'] ?? 'educandos'; // Tabla que se va a mostrar
$seccionFiltro = $_GET['seccion'] ?? null;      // Filtro opcional por sección

// Saneamos valores básicos para evitar inyección o caracteres inválidos
$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
$direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

// Obtener columnas dinámicamente para:
// 1) pintar botones de ordenación
// 2) validar que la columna de orden existe
$opcionesOrden = [];
$columnas_result = $conexion->query("SHOW COLUMNS FROM `{$tabla}`");
if ($columnas_result) {
    while ($col = $columnas_result->fetch_assoc()) {
        // Excluir columnas privadas o duplicadas que no queremos mostrar
        if (in_array($col['Field'], ['email2', 'telefono2', 'nombre2', 'apellidos2'])) {
            continue;
        }

        $nombre = $col['Field'];
        $label = ucfirst(str_replace('_', ' ', $nombre)); // Transformamos nombre_columna → Nombre columna

        // Etiquetas especiales para algunas columnas
        if ($nombre === "id_usuario") {
            $label = "Madre/Padre";
        } elseif ($nombre === "anio") {
            $label = "Año";
        }

        $opcionesOrden[$nombre] = $label;
    }
}

// Si la columna de orden no existe, usamos 'id' o la primera columna disponible
if (!array_key_exists($ordenarPor, $opcionesOrden)) {
    $ordenarPor = array_key_exists('id', $opcionesOrden) ? 'id' : array_key_first($opcionesOrden);
}
?>

<!-- Filtros y botones de ordenación -->
<div class="tabla-controles" style="display:flex; align-items:center; margin-bottom:10px; gap:10px; flex-wrap:wrap;">
    <!-- Botones de ordenación dinámicos -->
    <div style="display:flex; gap:5px; flex-wrap:wrap;">
        <?php foreach ($opcionesOrden as $columna => $label): 
            // Cambiamos dirección al pulsar en la misma columna
            $nuevaDireccion = ($ordenarPor === $columna && $direccion === 'ASC') ? 'DESC' : 'ASC';
            // Construimos enlace con parámetros
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

    <!-- Botón de insertar nuevo registro -->
    <a href="?operacion=insertar&amp;tabla=<?= htmlspecialchars($tabla) ?>" class="boton_insertar" 
       style="margin-left:auto; padding:5px 10px; background:green; color:#fff; text-decoration:none; border-radius:4px;">+</a>
</div>

<!-- Tabla de datos -->
<table>
    <?php
        // Cabecera: si hay al menos una fila, usamos sus claves
        // Si no hay filas, usamos columnas obtenidas con SHOW COLUMNS
        $resultado = $conexion->query("SELECT * FROM `{$tabla}` LIMIT 1;");
        $pintoCabecera = false;

        if ($resultado && $resultado->num_rows > 0) {
            $filaEjemplo = $resultado->fetch_assoc();
            echo "<tr>";
            foreach ($filaEjemplo as $clave => $valor) {
                // Excluimos columnas privadas
                if (in_array($clave, ['email2', 'telefono2', 'nombre2', 'apellidos2'])) {
                    continue;
                }

                // Etiquetas especiales para ciertas columnas
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
            // Columnas de acciones
            echo "<th>Editar</th><th>Eliminar</th>";
            echo "</tr>";
            $pintoCabecera = true;
        }

        // Si no hay filas, pintamos cabecera usando SHOW COLUMNS
        if (!$pintoCabecera && !empty($opcionesOrden)) {
            echo "<tr>";
            foreach ($opcionesOrden as $col => $lbl) {
                echo "<th>" . htmlspecialchars($lbl) . "</th>";
            }
            echo "<th>Editar</th><th>Eliminar</th>";
            echo "</tr>";
        }

        // Ordenamiento especial para columna 'seccion'
        if ($ordenarPor === 'seccion') {
            $orderBy = "FIELD(seccion, 'colonia', 'manada', 'tropa', 'posta', 'rutas') {$direccion}";
        } else {
            $orderBy = "`{$ordenarPor}` {$direccion}";
        }

        // Consulta de filas (con o sin filtro por sección)
        if ($seccionFiltro !== null && $seccionFiltro !== '') {
            $seccionFiltroSQL = $conexion->real_escape_string($seccionFiltro);
            $sqlListado = "SELECT * FROM `{$tabla}` WHERE seccion = '{$seccionFiltroSQL}' ORDER BY {$orderBy};";
        } else {
            $sqlListado = "SELECT * FROM `{$tabla}` ORDER BY {$orderBy};";
        }
        $resultadoListado = $conexion->query($sqlListado);

        // Colores por sección para filas
        $coloresSeccion = [
            "colonia" => "seccion-colonia",
            "manada"  => "seccion-manada",
            "tropa"   => "seccion-tropa",
            "posta"   => "seccion-posta",
            "rutas"   => "seccion-rutas"
        ];

        if ($resultadoListado) {
            while ($fila = $resultadoListado->fetch_assoc()) {
                // Determinar clase CSS según la sección del educando
                $claseFila = "";
                if (isset($fila['seccion'])) {
                    $seccionValor = strtolower((string)$fila['seccion']);
                    if (isset($coloresSeccion[$seccionValor])) {
                        $claseFila = $coloresSeccion[$seccionValor];
                    }
                }

                echo "<tr class='" . htmlspecialchars($claseFila) . "'>";
                foreach ($fila as $clave => $valor) {
                    // Omitimos columnas privadas
                    if (in_array($clave, ['email2', 'telefono2', 'nombre2', 'apellidos2'])) {
                        continue;
                    }

                    // Mostrar nombre completo de padre/madre
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

                // Acciones: editar y eliminar
                echo '<td><a href="?operacion=actualizar&amp;tabla=' . urlencode($tabla) . '&amp;id=' . (int)$fila['id'] . '">📝</a></td>';
                echo '<td><a class="eliminar" href="controladores/procesaeliminar.php?tabla=' . urlencode($tabla) . '&amp;id=' . (int)$fila['id'] . '">❌</a></td>';
                echo "</tr>";

                // Bloque especial para avisos circulares
                // Se pinta una subtabla con los educandos y si entregaron la circular
                if ($tabla === "avisos" && isset($fila["circular"]) && $fila["circular"] === "si") {
                    $avisoId = (int)$fila['id'];

                    // Obtenemos secciones y título del aviso
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

                    // Obtener todos los educandos de las secciones del aviso
                    if (!empty($seccionesArray)) {
                        $placeholders = implode(',', array_fill(0, count($seccionesArray), '?'));
                        $tipos = str_repeat('s', count($seccionesArray));

                        $stmtEdu = $conexion->prepare(query: "SELECT nombre, apellidos, seccion FROM educandos WHERE seccion IN ($placeholders) ORDER BY FIELD(seccion, 'colonia', 'manada', 'tropa', 'posta', 'rutas')");
                        $stmtEdu->bind_param($tipos, ...$seccionesArray);
                        $stmtEdu->execute();
                        $educandos_result = $stmtEdu->get_result();
                        
                        while ($a = $educandos_result->fetch_assoc()) {
                            $aviso_educandos[] = $a;
                        }
                        $stmtEdu->close();
                    }

                    // Eliminamos duplicados por nombre completo
                    $vistos = [];
                    $aviso_educandos_unicos = [];
                    foreach ($aviso_educandos as $edu) {
                        $nombreCompleto = trim(($edu['nombre'] ?? '') . '_' . ($edu['apellidos'] ?? ''));
                        if ($nombreCompleto === '' || isset($vistos[$nombreCompleto])) {
                            continue;
                        }
                        $vistos[$nombreCompleto] = true;
                        $aviso_educandos_unicos[] = $edu;
                    }
                    $aviso_educandos = $aviso_educandos_unicos;

                    // Pintamos la subtabla con información de entrega
                    echo "<tr class='" . htmlspecialchars($claseFila) . "'>";
                    echo "<td>" . (int)$fila['id'] . "</td>";
                    echo "<td colspan='100%'>";

                    echo "<table class='tabla-archivos'>
                            <tr>
                                <th>Niñ@</th>
                                <th>Sección</th>
                                <th>Entregado</th>
                            </tr>";

                    $lista_secciones = [];
                    foreach ($aviso_educandos as $edu) {
                        $lista_secciones[] = $edu['seccion'] ?? '';
                    }

                    $nsec = 0;
                    foreach ($aviso_educandos as $edu) {
                        $nombreCompleto = trim(($edu['nombre'] ?? '') . '_' . ($edu['apellidos'] ?? ''));
                        $nombreCarpeta = function_exists('limpiarTexto') ? limpiarTexto($nombreCompleto) : $nombreCompleto;
                        $tituloLimpio  = function_exists('limpiarTexto') ? limpiarTexto($tituloAviso) : $tituloAviso;

                        $ruta = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreCarpeta;

                        // Comprobamos si existe el archivo correspondiente a la entrega
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
                        $seccion = htmlspecialchars($lista_secciones[$nsec] ?? '');

                        echo "<tr class='" . htmlspecialchars($filaClaseEntrega) . " seccion-{$seccion}'>
                                <td>" . htmlspecialchars($nombreCompleto) . "</td>
                                <td>" . $seccion . "</td>
                                <td>" . ($entregado
                                    ? "<span style='color:green; font-weight:bold;'>Sí</span>"
                                    : "<span style='color:red; font-weight:bold;'>No</span>"
                                ) . "</td>
                            </tr>";

                        $nsec++;
                    }

                    echo "</table>";
                    echo "</td></tr>";
                }
            }
        }
    ?>
</table>

<!-- Script para confirmación al eliminar -->
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