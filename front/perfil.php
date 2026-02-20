<!-- Perfil de los padres -->
<?php 
include("inc/header.html");
include("inc/conexion_bd.php");

// Obtenemos el nombre del usuario de la sesión
session_start();
$nombre = $_SESSION["nombre"];

// Preparar y ejecutar la consulta para usuarios
$sql = "SELECT * FROM usuarios WHERE nombre = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$resultado = $stmt->get_result();
$fila = $resultado->fetch_assoc();
?>

<!-- Pintamos la informacion del ususario -->
<main>
    <section class="izquierda">
        <h1>Perfil</h1>
        <article>
            <div>
                <p><?=$fila["nombre"]?> <?=$fila["apellidos"] ?></p>
                <p><?=$fila["telefono"]?></p>
                <p><?=$fila["email"]?></p>
            </div>
            <div>
                <p><?=$fila["nombre2"]?> <?=$fila["apellidos2"] ?></p>
                <p><?=$fila["telefono2"]?></p>
                <p><?=$fila["email2"]?></p>
            </div>
        </article>
        <p><?=$fila["direccion"]?></p>
    </section>

    <section class="derecha">
        <h1>Hijos</h1>

<?php
// Preparar y ejecutar la consulta para educandos
$sql = "SELECT * FROM educandos WHERE id_usuario = ? ORDER BY FIELD(seccion, 'colonia', 'manada', 'tropa', 'posta', 'rutas')";
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

    // Pintamos al hijo con su clase correspondiente y un onclick para ir a su perfil
    echo "<div class='hijo $clase' onclick=\"window.location='educandos.php?id=".$educando['id']."'\">"
    .$educando['nombre']." ".$educando['apellidos']."</div>";
}
?>
    </section>
</main>

<?php 
include("inc/footer.html");
?>
