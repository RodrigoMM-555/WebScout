<?php
// -----------------------------
// subearchivo.php
// Script para subir un archivo a la carpeta del educando
// -----------------------------

// Comprobar que se recibió un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != 0) {
    die("Error: no se recibió ningún archivo o hubo un error en la subida.");
}

// Datos del archivo subido
$archivoTmp = $_FILES['archivo']['tmp_name'];   // ruta temporal
$nombreArchivo = $_FILES['archivo']['name'];    // nombre original

// Nombre del educando desde el formulario
$nombreEducando = $_POST['nombreCompleto'] ?? 'sin_nombre';
$nombreEducando = str_replace(' ', '_', $nombreEducando); // reemplazar espacios

// Carpeta base absoluta: uploads/circulares/educandos/<nombreEducando>
$baseDir = $_SERVER['DOCUMENT_ROOT'] . '/WebScout/circulares/educandos/' . $nombreEducando;

// Crear carpetas si no existen
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0777, true)) {  // true = crea carpetas padres
        die("Error: no se pudo crear la carpeta $baseDir. Comprueba permisos.");
    } else {
        echo "Carpeta creada correctamente: $baseDir <br>";
    }
}

// Ruta final del archivo
$rutaFinal = $baseDir . '/' . $nombreArchivo;

// Depuración antes de mover archivo
echo "Archivo temporal: $archivoTmp <br>";
echo "Ruta destino: $rutaFinal <br>";
echo "Archivo válido subido?: " . (is_uploaded_file($archivoTmp) ? "Sí" : "No") . "<br>";
echo "Carpeta escribible?: " . (is_writable($baseDir) ? "Sí" : "No") . "<br>";

// Mover el archivo
if (move_uploaded_file($archivoTmp, $rutaFinal)) {
    echo "<strong>Archivo subido correctamente a: $rutaFinal</strong>";
} else {
    echo "<strong>Error al subir el archivo</strong>";
}
?>
