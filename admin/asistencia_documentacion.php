<?php
/**
 * asistencia_documentacion.php — Vista de asistencia y documentación por aviso
 * ===============================================================================
 * Muestra educandos agrupados por estado de asistencia (asisten/pendientes/no)
 * y comprueba si han entregado la circular (si aplica).
 *
 * ★ FIX: Requiere sesión de admin
 * ★ FIX: limpiarTexto() ya no se duplica (viene de utils.php)
 */
session_start();
?>
<main>
<link rel="stylesheet" href="css/estilo.css">
<?php
include "inc/conexion_bd.php";

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

            $nombreCarpeta = limpiarTexto($nombre);
            $tituloLimpio  = limpiarTexto($tituloAviso);
            $ruta = BASE_PATH . '/circulares/educandos/' . $nombreCarpeta;
            $entregado = false;

            if (is_dir($ruta)) {
                $archivos = array_diff(scandir($ruta), ['.', '..']);
                $prefijo = $tituloLimpio . '_' . $nombreCarpeta . '.';
                foreach ($archivos as $f) {
                    if (strpos($f, $prefijo) === 0) {
                        $entregado = true;
                        break;
                    }
                }
            }

            $color = $entregado ? 'green' : 'red';
            $txt = $entregado ? 'Sí' : 'No';

            echo "<td style='color:$color; font-weight:bold;'>$txt</td>";
        }

        echo "</tr>";
    }

    echo "</table>";
}
?>
</main>