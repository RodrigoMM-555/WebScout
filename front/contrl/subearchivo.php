<!-- Subir archivos segun nombres -->
<?php
include("../inc/conexion_bd.php");

// Validar que se recibió un archivo sin errores
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != 0) {
    die("Error: no se recibió ningún archivo o hubo un error en la subida.");
}

// Obtener información del archivo
$archivoTmp = $_FILES['archivo']['tmp_name'];                               // Ruta temporal del archivo
$nombreOriginal = $_FILES['archivo']['name'];                               // Nombre original del archivo
$extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);    // Extensión del archivo

// Recoger datos, si no hay se asigna un valor por defecto
$nombreEducando = $_POST['nombreCompleto'] ?? 'sin_nombre';
$tituloAviso = $_POST['tituloAviso'] ?? 'sin_aviso';

// Función para limpiar el texto
function limpiarTexto($texto) {
    $texto = str_replace(' ', '_', $texto);
    $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    if ($tmp !== false) $texto = $tmp;
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $texto);
}

// Limpiar los textos para evitar problemas con caracteres especiales
$nombreEducando = limpiarTexto($nombreEducando);
$tituloAviso = limpiarTexto($tituloAviso);

// Carpeta destino del archivo
$baseDir = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreEducando;

// Crear carpeta si no existe, conpermisos adecuados y mensaje de error
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0777, true)) {
        $err = error_get_last();
        die("Error: no se pudo crear la carpeta $baseDir. Motivo: " . ($err['message'] ?? 'desconocido'));
    }
}

// Preparamos el nombre y ruta final
$nuevoNombre = $tituloAviso . "_" . $nombreEducando . "." . $extension;
$rutaFinal = $baseDir . '/' . $nuevoNombre;

// Mover el archivo a la ubicación final, tambien sacamos el id del educando para redireccionar a su pagina, si no se encuentra se redirecciona al inicio
// y en caso de problema con la subida se muestra un mensaje de error
if (move_uploaded_file($archivoTmp, $rutaFinal)) {
    $origen = $_GET['ori'] ?? 'inicio';
    if ($origen == "educandos") {
        $sql = "SELECT id FROM educandos WHERE nombre = ? AND apellidos = ?";
        $stmt = $conexion->prepare($sql);
        $nombres = explode('_', $nombreEducando);
        echo $nombres;
        $nombre = $nombres[0] ?? '';
        $apellidos = $nombres[1] ?? '';
        $stmt->bind_param("ss", $nombre, $apellidos);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $origen = "educandos.php?id=" . $row['id'];
            header("Location: ../$origen");
        } else {
            $origen = "inicio";
        }
    }
    else {
        header("Location: ../$origen.php");
    }

} else {
    echo "<strong>Error al subir el archivo</strong>";
}

?>