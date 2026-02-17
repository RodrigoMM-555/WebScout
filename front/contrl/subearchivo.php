<?php

include("../inc/conexion_bd.php");

// Mostrar errores durante desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != 0) {
    die("Error: no se recibió ningún archivo o hubo un error en la subida.");
}

$archivoTmp = $_FILES['archivo']['tmp_name'];
$nombreOriginal = $_FILES['archivo']['name'];
$extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);

// Recoger datos
$nombreEducando = $_POST['nombreCompleto'] ?? 'sin_nombre';
$tituloAviso = $_POST['tituloAviso'] ?? 'sin_aviso';

// Función segura
function limpiarTexto($texto) {
    $texto = str_replace(' ', '_', $texto);
    $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    if ($tmp !== false) $texto = $tmp;
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $texto);
}

$nombreEducando = limpiarTexto($nombreEducando);
$tituloAviso = limpiarTexto($tituloAviso);

// Carpeta destino CORRECTA
$baseDir = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreEducando;

// Crear carpeta si no existe
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0777, true)) {
        $err = error_get_last();
        die("Error: no se pudo crear la carpeta $baseDir. Motivo: " . ($err['message'] ?? 'desconocido'));
    }
}

$nuevoNombre = $tituloAviso . "_" . $nombreEducando . "." . $extension;
$rutaFinal = $baseDir . '/' . $nuevoNombre;

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