<?php
/**
 * perfil.php — Perfil del padre/madre y listado de hijos
 * ========================================================
 * Vista privada para familias.
 * Muestra datos personales del usuario autenticado y las tarjetas de
 * sus educandos, incluyendo enlaces al detalle y color por sección.
 * Contiene consultas a usuarios/educandos y renderizado HTML de perfil.
 */
session_start();

// Comprobar que hay sesión activa
if (empty($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}
?>
<!-- Perfil de los padres -->
<?php
include("../inc/header.php");
include("../inc/conexion_bd.php");

// Preparar y ejecutar la consulta para el usuario
$idUsuarioSesion = (int)$_SESSION["id_usuario"];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idUsuarioSesion);

$stmt->execute();
$resultado = $stmt->get_result();
$fila = $resultado->fetch_assoc();

if (!$fila) {
    header("Location: ../index.php");
    exit;
}
?>
<main>
    <!-- Datos del usuario -->
    <section class="izquierda">
        <h1 data-i18n="perfil">Perfil</h1>
        <div>
            <p>
                <?= htmlspecialchars($fila["nombre"]) ?> <?= htmlspecialchars($fila["apellidos"]) ?>
            </p>
            <?php if (!empty($fila["nombre2"])): ?>
            <p>
                <?= htmlspecialchars($fila["nombre2"]) ?> <?= htmlspecialchars($fila["apellidos2"]) ?>
            </p>
            <?php endif; ?>
        </div>
        <div>
            <p>
                <?= htmlspecialchars($fila["telefono"]) ?>
            </p>
            <?php if (!empty($fila["telefono2"])): ?>
            <p>
                <?= htmlspecialchars($fila["telefono2"]) ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="perfil-emails">
            <p>
                <?= htmlspecialchars($fila["email"]) ?>
            </p>
            <?php if (!empty($fila["email2"])): ?>
            <p>
                <?= htmlspecialchars($fila["email2"]) ?>
            </p>
            <?php endif; ?>
        </div>
        <div>
            <p><?= htmlspecialchars($fila["direccion"]) ?></p>
        </div>
        <button class="sin-icono-auto" type="button" onclick="window.location.href='inicio.php'">&larr; <span data-i18n="inicio">Atrás</span></button>
    </section>

    <!-- Tarjetas de los hijos -->
    <section class="derecha">
        <h1 data-i18n="hijos">Hijos</h1>
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

    // Pintamos al hijo con su clase de sección y enlace a su perfil
    echo "<div class='hijo $clase' onclick=\"window.location='educandos.php?id=".(int)$educando['id']."'\">"
    . htmlspecialchars($educando['nombre'] . " " . $educando['apellidos']) . "</div>";
}
?>
    </section>
</main>

<?php 
include("../inc/footer.html");
?>
