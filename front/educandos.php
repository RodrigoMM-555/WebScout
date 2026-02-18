<?php 
include("inc/header.html");
include("inc/conexion_bd.php");

function limpiarTexto($texto) {
    $texto = str_replace(' ', '_', $texto);
    $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    if ($tmp !== false) {
        $texto = $tmp;
    }
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $texto);
}


// Comprobamos que nos pasan el id por GET
if(!isset($_GET['id'])) {
    die("No se ha especificado un educando.");
}

$id_educando = intval($_GET['id']); // Siempre convertir a entero por seguridad

// Consultamos la información del educando
$sql = "SELECT * FROM educandos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_educando);
$stmt->execute();
$resultado = $stmt->get_result();
$educando = $resultado->fetch_assoc();

if(!$educando) {
    die("Educando no encontrado.");
}

// Determinar clase de color según sección
switch(strtolower($educando['seccion'])) {
    case 'colonia':
        $clase_color = 'colonia';
        break;
    case 'manada':
        $clase_color = 'manada';
        break;
    case 'tropa':
        $clase_color = 'tropa';
        break;
    case 'posta':
        $clase_color = 'posta';
        break;
    case 'rutas':
        $clase_color = 'rutas';
        break;
    default:
        $clase_color = 'otros';
}

$nombreCompleto = $educando['nombre'] . " " . $educando['apellidos'];

?>

<main>
    <section class="izquierda <?=$clase_color?>">
        <h1><?=$educando['nombre']?> <?=$educando['apellidos']?></h1>
        <p>Sección: <?=$educando['seccion']?></p>
        <p>Año: <?=$educando['año']?></p>
        <p>DNI: <?=$educando['dni']?></p>
        <button type="button" onclick="history.back()">&larr; Atrás</button>
    </section>

    <section class="derecha">
        <h1>Documentación</h1>

        <article>
        <div class="documentacion" id="doc1">
            <p>1-Ficha de inscripción</p>
            <a href="../circulares/plantillas/1-Ficha de inscripción.pdf" target="_blank">⬇️</a>
            <form action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <input type='file' name='archivo' required>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='1-Ficha de inscripción'>
                <input type='submit' value='⬆️'>
            </form>
        </div>

        <div class="documentacion" id="doc2">
            <p>2-Ficha sanitaria</p>
            <a href="../circulares/plantillas/2-Ficha sanitaria menor edad.pdf" target="_blank">⬇️</a>
            <form action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <input type='file' name='archivo' required>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='2-Ficha sanitaria'>
                <input type='submit' value='⬆️'>
            </form>
        </div>

        <div class="documentacion" id="doc3">
            <p>3-Exclusión de responsabilidad</p>
            <a href="../circulares/plantillas/4-Exclusión de responsabilidad.pdf" target="_blank">⬇️</a>
            <form action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <input type='file' name='archivo' required>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='3-Exclusión de responsabilidad'>
                <input type='submit' value='⬆️'>
            </form>
        </div>

        <div class="documentacion" id="doc4">
            <p>4-Autorización ausentarse de actividades</p>
            <a href="../circulares/plantillas/5-Autorización ausentarse actividades.pdf" target="_blank">⬇️</a>
            <form action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <input type='file' name='archivo' required>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='4-Autorización ausentarse de actividades'>
                <input type='submit' value='⬆️'>
            </form>
        </div>
        </article>

    </section>
</main>

<?php

$titulos = [
    1 => "1-Ficha de inscripción",
    2 => "2-Ficha sanitaria",
    3 => "3-Exclusión de responsabilidad",
    4 => "4-Autorización ausentarse de actividades"
];

$nombreCarpeta = limpiarTexto($nombreCompleto);

$ruta = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreCarpeta;

if (is_dir($ruta)) {

    $archivos = array_diff(scandir($ruta), ['.', '..']);

    foreach ($titulos as $num => $titulo) {

        $tituloLimpio = limpiarTexto($titulo);
        $prefijo = $tituloLimpio . '_' . $nombreCarpeta;

        foreach ($archivos as $f) {
            if (strpos($f, $prefijo) === 0) {
                echo "<script>
                        document.getElementById('doc$num').classList.add('entregado');
                      </script>";
                break;
            }
        }
    }
}

include("inc/footer.html");
?>
