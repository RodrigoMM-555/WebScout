<?php 
include("inc/header.html");
include("inc/conexion_bd.php");

session_start();
$nombre = $_SESSION["nombre"];

// Preparar y ejecutar la consulta para el usuario
$sql = "SELECT * FROM usuarios WHERE nombre = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$resultado = $stmt->get_result();

// Sacamos la fila del resultado del usuario
$fila = $resultado->fetch_assoc();
?>
<main>
    <section class="izquierda">
        <h1>Perfil</h1>
        <p><?=$fila["nombre"]?> <?=$fila["apellidos"] ?></p>
        <p><?=$fila["telefono"]?></p>
        <p><?=$fila["email"]?></p>
        <p><?=$fila["direccion"]?></p>
    </section>

    <section class="derecha">
        <h1>Hijos</h1>

<?php
// Preparar y ejecutar la consulta para educandos
$sql = "SELECT * FROM educandos WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $fila["id"]);  // id es un entero
$stmt->execute();
$resultado_educandos = $stmt->get_result();

// Recorremos todos los educandos y generamos un div por cada uno
while($educando = $resultado_educandos->fetch_assoc()) {

    // Determinar clase según sección
    switch(strtolower($educando['seccion'])) {
        case 'colonia':
            $clase = 'colonia';
            break;
        case 'manada':
            $clase = 'manada';
            break;
        case 'tropa':
            $clase = 'tropa';
            break;
        case 'posta':
            $clase = 'posta';
            break;
        case 'rutas':
            $clase = 'rutas';
            break;
        default:
            $clase = 'otros';
    }

    echo "<div class='hijo $clase' onclick=\"window.location='educandos.php?id=".$educando['id']."'\">"
    .$educando['nombre']." ".$educando['apellidos']."</div>";
}
?>

    </section>
</main>

<?php 
include("inc/footer.html");
?>
