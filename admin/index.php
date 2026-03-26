<?php
/**
 * admin/index.php — Panel de administración
 * ============================================
 * Punto de entrada del admin. Comprueba sesión de admin,
 * carga el sidebar con tablas y enruta la operación CRUD.
 */
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Redirigir al login si no es admin (antes era un rickroll)
    header("Location: ../index.php");
    exit;
}
?>

<!doctype html>
<html lang="es">
	<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>WebScout — Admin</title>
        <link rel="stylesheet" href="css/estilo.css">
    </head>
    <body>
        <?php include_once __DIR__ . "/../inc/conexion_bd.php"; ?>
        <nav>
            <img src="../img/logo.png" alt="Logo WebScout" class="logo">
            <?php include "contrl/poblar_menu.php" ?>
            <!-- Botón de cerrar sesión -->
            <a href="../front/contrl/logout.php" class="logout-admin">Cerrar sesión</a>
        </nav>
        <main>
            <?php
                // ── Mostrar mensajes flash (éxito, error, info) ──
                mostrarFlash();

                // ── Enrutador: muestra el controlador según la operación ──
                $operacion = $_GET['operacion'] ?? null;

                if (!$operacion) {
                    include "contrl/leer.php";
                }
                elseif ($operacion === "insertar") {
                    include "contrl/insertar.php";
                }
                elseif ($operacion === "procesainsertar") {
                    include "contrl/procesainsertar.php";
                }
                elseif ($operacion === "actualizar") {
                    include "contrl/actualizar.php";
                }
                elseif ($operacion === "procesaactualizar") {
                    include "contrl/procesaactualizar.php";
                }
                elseif ($operacion === "ia_admin") {
                    include "contrl/ia.php";
                }
                elseif ($operacion === "sincronizar_secciones") {
                    include "contrl/sincronizar_secciones.php";
                }
                else {
                    echo "<p>Operación no reconocida.</p>";
                }
            ?>
        </main>
    </body>
</html>