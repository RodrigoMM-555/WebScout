<?php
/**
 * asistencia_documentacion.php — Vista de asistencia y documentación por aviso
 * ===============================================================================
 * Muestra educandos agrupados por estado de asistencia (asisten/pendientes/no)
 * y comprueba si han entregado la circular asociada (cuando existe).
 * Contiene consultas de cruce entre avisos, asistencias y archivos subidos,
 * además del renderizado de tablas de seguimiento para administración.
 */
session_start();
?>
<main>
<link rel="stylesheet" href="css/estilo.css">
<?php
include "../inc/conexion_bd.php";

// Solo admins pueden ver esta página
requerirAdmin();

if (!isset($_GET['id_aviso'])) {
    echo "Aviso no especificado";
    exit;
}

$id_aviso = (int)$_GET['id_aviso'];

// Obtener aviso (añadimos circular)
$stmt = $conexion->prepare("SELECT titulo, secciones, circular FROM avisos WHERE id = ?");
$stmt->bind_param("i", $id_aviso);
$stmt->execute();
$resAviso = $stmt->get_result();
$aviso = $resAviso->fetch_assoc();
$stmt->close();

$secciones = array_filter(array_map('trim', explode(',', $aviso['secciones'])));
$tituloAviso = $aviso['titulo'];
$tieneCircular = ($aviso['circular'] === 'si');

// Obtener educandos
$educandos = [];
if (!empty($secciones)) {
    $placeholders = implode(',', array_fill(0, count($secciones), '?'));
    $tipos = str_repeat('s', count($secciones));
    $stmtEdu = $conexion->prepare("
        SELECT id, nombre, apellidos, seccion 
        FROM educandos 
        WHERE seccion IN ($placeholders) 
        ORDER BY FIELD(seccion,'colonia','manada','tropa','posta','rutas')
    ");
    $stmtEdu->bind_param($tipos, ...$secciones);
    $stmtEdu->execute();
    $resEdu = $stmtEdu->get_result();
    while ($fila = $resEdu->fetch_assoc()) {
        $educandos[] = $fila;
    }
    $stmtEdu->close();
}

// Separar por asistencia 
$grupos = ['si'=>[], 'no'=>[], 'pendiente'=>[]];

foreach ($educandos as $edu) {
    $stmtAsis = $conexion->prepare("SELECT asistencia FROM asistencias WHERE id_aviso = ? AND id_educando = ?");
    $stmtAsis->bind_param("ii", $id_aviso, $edu['id']);
    $stmtAsis->execute();
    $resAsis = $stmtAsis->get_result();
    $asis = $resAsis->fetch_assoc();
    $estado = $asis ? $asis['asistencia'] : 'pendiente';
    $grupos[$estado][] = $edu;
    $stmtAsis->close();
}

// limpiarTexto() ya definida en utils.php (cargado por conexion_bd)

// Pintar tabla
echo "<div class='asi'>
        <h1>Asistencia y documentación: " . htmlspecialchars($tituloAviso) . "</h1>
        <a href='index.php?tabla=avisos'>Volver a avisos</a>
      </div>";

$ordenSecciones = ['colonia', 'manada', 'tropa', 'posta', 'rutas'];
$seccionesResumen = !empty($secciones)
    ? array_values(array_intersect($ordenSecciones, $secciones))
    : $ordenSecciones;

// Normalizamos el título para construir el prefijo del archivo de circular.
// Se reutiliza tanto en la tabla como en los donuts, evitando duplicar lógica.
$tituloLimpioAviso = limpiarTexto($tituloAviso);

// Caché en memoria por petición: id_educando => true/false (entregó circular).
// Así no repetimos escaneos de carpeta para el mismo educando.
$cacheEntregasCircular = [];

// Función auxiliar para comprobar si un educando entregó circular de ESTE aviso.
// Criterio: existe un archivo en su carpeta con prefijo "<tituloAviso>_<nombreEdu>."
$educandoHaEntregadoCircular = static function (array $edu) use (&$cacheEntregasCircular, $tituloLimpioAviso): bool {
    $idEdu = (int)($edu['id'] ?? 0);
    if ($idEdu > 0 && array_key_exists($idEdu, $cacheEntregasCircular)) {
        return $cacheEntregasCircular[$idEdu];
    }

    $nombreCompleto = ($edu['nombre'] ?? '') . ' ' . ($edu['apellidos'] ?? '');
    $nombreCarpeta = limpiarTexto($nombreCompleto);
    $ruta = BASE_PATH . '/circulares/educandos/' . $nombreCarpeta;
    $prefijo = $tituloLimpioAviso . '_' . $nombreCarpeta . '.';

    $entregado = false;
    if (is_dir($ruta)) {
        $archivos = array_diff(scandir($ruta), ['.', '..']);
        foreach ($archivos as $archivo) {
            if (strpos($archivo, $prefijo) === 0) {
                $entregado = true;
                break;
            }
        }
    }

    if ($idEdu > 0) {
        $cacheEntregasCircular[$idEdu] = $entregado;
    }

    return $entregado;
};

// Base de conteo por sección (incluye 0 explícitos para mantener consistencia visual).
$conteoBase = array_fill_keys($seccionesResumen, 0);

// Series base para donuts comunes.
$donutSeries = [
    'no' => $conteoBase,
    'pendiente' => $conteoBase,
    'si' => $conteoBase
];

// Rellenamos conteos por estado y sección a partir de $grupos.
foreach ($grupos as $estado => $listaEducandos) {
    if (!isset($donutSeries[$estado])) {
        continue;
    }
    foreach ($listaEducandos as $edu) {
        $sec = $edu['seccion'];
        if (!array_key_exists($sec, $donutSeries[$estado])) {
            $donutSeries[$estado][$sec] = 0;
        }
        $donutSeries[$estado][$sec]++;
    }
}

// Si el aviso tiene circular, partimos "si" en dos donuts:
// - sí con circular
// - sí sin circular
// Esto se basa en la comprobación real de archivos subidos.
if ($tieneCircular) {
    $donutSeries['si_con_circular'] = $conteoBase;
    $donutSeries['si_sin_circular'] = $conteoBase;

    foreach ($grupos['si'] as $edu) {
        $sec = $edu['seccion'];
        if (!array_key_exists($sec, $donutSeries['si_con_circular'])) {
            $donutSeries['si_con_circular'][$sec] = 0;
            $donutSeries['si_sin_circular'][$sec] = 0;
        }

        if ($educandoHaEntregadoCircular($edu)) {
            $donutSeries['si_con_circular'][$sec]++;
        } else {
            $donutSeries['si_sin_circular'][$sec]++;
        }
    }
}

// Configuración final de tarjetas a dibujar (3 o 4 según $tieneCircular).
$donutCharts = [
    ['id' => 'donut-no', 'titulo' => 'No asisten', 'series' => $donutSeries['no']],
    ['id' => 'donut-pendiente', 'titulo' => 'Pendientes', 'series' => $donutSeries['pendiente']]
];

if ($tieneCircular) {
    $donutCharts[] = ['id' => 'donut-si-sin-circular', 'titulo' => 'Sí (Sin circular)', 'series' => $donutSeries['si_sin_circular']];
    $donutCharts[] = ['id' => 'donut-si-con-circular', 'titulo' => 'Sí (Con circular)  ', 'series' => $donutSeries['si_con_circular']];
} else {
    $donutCharts[] = ['id' => 'donut-si', 'titulo' => 'Asisten', 'series' => $donutSeries['si']];
}

// Payload que viaja de PHP a JS.
// "keys" conserva las claves técnicas para resolver colores por sección.
// "labels" son los textos visibles para leyenda/tooltip.
// "values" son las cantidades por sección para cada donut.
$donutPayload = [
    'colores' => [
        'colonia' => '#ffe5b4',
        'manada' => '#fff9c4',
        'tropa' => '#bbdefb',
        'posta' => '#ffcdd2',
        'rutas' => '#c8e6c9'
    ],
    'charts' => array_map(static function (array $chart): array {
        return [
            'id' => $chart['id'],
            'titulo' => $chart['titulo'],
            'labels' => array_map('ucfirst', array_keys($chart['series'])),
            'keys' => array_keys($chart['series']),
            'values' => array_values($chart['series']),
            'total' => array_sum($chart['series'])
        ];
    }, $donutCharts)
];

// Render del contenedor de donuts.
// El total central se pinta en HTML/CSS y el anillo de datos en Chart.js.
echo "<section class='resumen-donuts' aria-label='Resumen gráfico de asistencia'>";
foreach ($donutPayload['charts'] as $chart) {
    echo "<article class='donut-card'>
            <h3>" . htmlspecialchars($chart['titulo']) . "</h3>
            <div class='donut-wrap'>
                <canvas id='" . htmlspecialchars($chart['id']) . "'></canvas>
                <div class='donut-total'>" . (int)$chart['total'] . "</div>
            </div>
          </article>";
}
echo "</section>";

foreach (['si','pendiente','no'] as $key) {

    $label = $key==='si' ? 'Asisten' : ($key==='no' ? 'No asisten' : 'Pendientes');
    echo "<h2>$label</h2>";

    if (empty($grupos[$key])) {
        echo "<p>No hay registros</p>";
        continue;
    }

    echo "<table class='asistencia'>
            <tr>
                <th>Niñ@</th>
                <th>Sección</th>";

    // Solo mostramos columna Entregado si hay circular
    if ($key==='si' && $tieneCircular) {
        echo "<th>Entregado</th>";
    }

    echo "</tr>";

    foreach ($grupos[$key] as $edu) {

        $nombre = $edu['nombre'] . ' ' . $edu['apellidos'];
        $seccion = htmlspecialchars($edu['seccion']);

        if ($key == 'si' || $key == 'pendiente') {
            echo "<tr class='seccion-{$edu['seccion']}'>
                    <td>" . htmlspecialchars($nombre) . "</td>
                    <td>$seccion</td>";
        } else {
            echo "<tr>
                    <td>" . htmlspecialchars($nombre) . "</td>
                    <td>$seccion</td>";
        }

        // Solo comprobamos archivos si hay circular
        if ($key==='si' && $tieneCircular) {
            $entregado = $educandoHaEntregadoCircular($edu);

            $color = $entregado ? 'green' : 'red';
            $txt = $entregado ? 'Sí' : 'No';

            echo "<td style='color:$color; font-weight:bold;'>$txt</td>";
        }

        echo "</tr>";
    }

    echo "</table>";
}

echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
// Inyectamos el payload serializado para que donuts.js lo consuma.
echo "<script>window.webScoutDonutData = " . json_encode($donutPayload, JSON_UNESCAPED_UNICODE) . ";</script>";
// Script externo: inicializa y pinta cada donut.
echo "<script src='js/donuts.js'></script>";
?>
</main>