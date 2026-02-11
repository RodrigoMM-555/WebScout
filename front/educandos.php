<?php 
include("inc/header.html");
include("inc/conexion_bd.php");

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

        <div class="documentacion">algo</div>
        <div class="documentacion">algo</div>
        <div class="documentacion">algo</div>

    </section>
</main>

<?php 
include("inc/footer.html");
?>
