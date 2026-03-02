<!-- Perfil de los hijos -->
<?php 
include("inc/header.php");
include("inc/conexion_bd.php");

// limpiarTexto() ya definida en utils.php (cargado por conexion_bd)

$subidaAviso = $_GET['subida_aviso'] ?? '';
$permisosCalc = isset($_GET['permisos_calc']) ? (int)$_GET['permisos_calc'] : null;


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

<script>
function volverAtrasInteligente() {
    try {
        if (document.referrer) {
            const ref = new URL(document.referrer);
            if (ref.pathname.endsWith('/front/educandos.php')) {
                history.go(-2);
                return;
            }
        }
    } catch (e) {
        // Si falla el parseo del referrer, usamos comportamiento por defecto.
    }

    history.back();
}
</script>

<main>
    <!-- Pintamos la informacion del educando -->
    <section class="izquierda <?=$clase_color?>">
        <h1><?=$educando['nombre']?> <?=$educando['apellidos']?></h1>
        <p>Sección: <?=$educando['seccion']?></p>
        <p>Año: <?=$educando['anio']?></p>
        <p>DNI: <?=$educando['dni']?></p>
        <!-- Boton para volver al perfil de los padres -->
        <button type="button" onclick="volverAtrasInteligente()">&larr; Atrás</button>
    </section>

    <!-- Apartado de documentación -->
    <section class="derecha">
        <h1>Documentación</h1>

        <article>
        <!-- Cada documento tiene un formulario para subir el archivo -->
        <div class="documentacion" id="doc1">
            <p>1-Ficha de inscripción</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/1-Ficha de inscripción.pdf" target="_blank">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc1" title="Seleccionar archivo">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc1" type='file' name='archivo' required>
                <span class="archivo-nombre">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='1-Ficha de inscripción'>
                <input class="btn-archivo btn-subir" type='submit' value='Subir'>
            </form>
        </div>

        <div class="documentacion" id="doc2">
            <p>2-Ficha sanitaria</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/2-Ficha sanitaria menor edad.pdf" target="_blank">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc2" title="Seleccionar archivo">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc2" type='file' name='archivo' required>
                <span class="archivo-nombre">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='2-Ficha sanitaria'>
                <input class="btn-archivo btn-subir" type='submit' value='Subir'>
            </form>
        </div>

        <div class="documentacion" id="doc3">
            <p>3-Exclusión de responsabilidad</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/4-Exclusión de responsabilidad.pdf" target="_blank">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc3" title="Seleccionar archivo">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc3" type='file' name='archivo' required>
                <span class="archivo-nombre">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='3-Exclusión de responsabilidad'>
                <input class="btn-archivo btn-subir" type='submit' value='Subir'>
            </form>
        </div>

        <div class="documentacion" id="doc4">
            <p>4-Autorización ausentarse de actividades</p>
            <a class="btn-archivo btn-descargar" href="../circulares/plantillas/5-Autorización ausentarse actividades.pdf" target="_blank">Descargar</a>
            <form class="form-archivo" action='contrl/subearchivo.php?ori=educandos' method='post' enctype='multipart/form-data'>
                <label class="btn-archivo btn-archivo-select-emoji" for="archivo_doc4" title="Seleccionar archivo">📎 Elegir</label>
                <input class="input-archivo input-archivo-oculto" id="archivo_doc4" type='file' name='archivo' required>
                <span class="archivo-nombre">Sin archivo</span>
                <input type='hidden' name='nombreCompleto' value="<?=htmlspecialchars($nombreCompleto)?>">
                <input type='hidden' name='tituloAviso' value='4-Autorización ausentarse de actividades'>
                <input class="btn-archivo btn-subir" type='submit' value='Subir'>
            </form>
        </div>
        </article>

    </section>
</main>

<script>
document.querySelectorAll('.input-archivo-oculto').forEach(function(input) {
    input.addEventListener('change', function() {
        const nombre = this.files && this.files.length ? this.files[0].name : 'Sin archivo';
        const form = this.closest('form');
        const target = form ? form.querySelector('.archivo-nombre') : null;
        if (target) target.textContent = nombre;
    });
});
</script>

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
$ruta = BASE_PATH . '/circulares/educandos/' . $nombreCarpeta;

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

if ($subidaAviso === 'ocr_conversion_fallida') {
    echo "<script>alert('El PDF se ha subido correctamente, pero no se pudo convertir temporalmente para leer las casillas. Revisa los permisos manualmente.');</script>";
}

if ($subidaAviso === 'ocr_sin_educando') {
    echo "<script>alert('El PDF se ha subido, pero no se encontró el educando para actualizar permisos en la base de datos.');</script>";
}

if ($subidaAviso === 'ocr_columna_permisos_falta') {
    echo "<script>alert('El PDF se ha subido, pero falta la columna permisos en la tabla educandos. Ejecuta la migración SQL.');</script>";
}

if ($subidaAviso === 'ocr_update_preparacion_fallida' || $subidaAviso === 'ocr_update_fallido') {
    echo "<script>alert('El PDF se ha subido, pero falló la actualización de permisos en base de datos. Revisa logs del servidor.');</script>";
}

if ($permisosCalc !== null) {
    echo "<script>console.log('Permisos calculados automáticamente: ' + " . $permisosCalc . ");</script>";
}
?>
