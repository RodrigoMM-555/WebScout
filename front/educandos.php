<!-- Perfil de los hijos -->
<?php 
include("inc/header.html");
include("inc/conexion_bd.php");

// Funcion para depurar texto
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

// COnvertimos el id a entero por seguridad
$id_educando = intval($_GET['id']);

// Consultamos la información del educando
$sql = "SELECT * FROM educandos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_educando);
$stmt->execute();
$resultado = $stmt->get_result();
$educando = $resultado->fetch_assoc();

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

// Nombre compelto del educando
$nombreCompleto = $educando['nombre'] . " " . $educando['apellidos'];

?>

<main>
    <!-- Pintamos la informacion del educando -->
    <section class="izquierda <?=$clase_color?>">
        <h1><?=$educando['nombre']?> <?=$educando['apellidos']?></h1>
        <p>Sección: <?=$educando['seccion']?></p>
        <p>Año: <?=$educando['anio']?></p>
        <p>DNI: <?=$educando['dni']?></p>
        <!-- Boton para volver al perfil de los padres -->
        <button type="button" onclick="history.back()">&larr; Atrás</button>
    </section>

    <!-- Apartado de documentación -->
    <section class="derecha">
        <h1>Documentación</h1>

        <article>
        <!-- Cada documento tiene un formulario para subir el archivo -->
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

// Nombres de los archivos
$titulos = [
    1 => "1-Ficha de inscripción",
    2 => "2-Ficha sanitaria",
    3 => "3-Exclusión de responsabilidad",
    4 => "4-Autorización ausentarse de actividades"
];

// Limpiamos el nombre completo para usarlo como parte del nombre de los archivos
$nombreCarpeta = limpiarTexto($nombreCompleto);

// Ruta de la carpeta del educando en el servidor
$ruta = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreCarpeta;

// Comprobamos si la carpeta existe y listamos los archivos para marcar los documentos entregados
if (is_dir($ruta)) {

    // Recopilamos los archivos subidos a la ruta
    $archivos = array_diff(scandir($ruta), ['.', '..']);

    // Por cada titulo/archivo
    foreach ($titulos as $num => $titulo) {
        // Limpiamos el titulo y lo juntamos con el nombre
        $tituloLimpio = limpiarTexto($titulo);
        $prefijo = $tituloLimpio . '_' . $nombreCarpeta;

        // Buscamos el archivo igual al prefijo para marcar que si se ha entregado
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
