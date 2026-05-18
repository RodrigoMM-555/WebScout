<?php
/**
 * admin/index.php — Panel principal de administración de WebScout
 * --------------------------------------------------------------
 * Este archivo es el punto de entrada para el área de administración.
 * - Verifica que el usuario tenga rol de administrador (protección de acceso).
 * - Carga el menú lateral y la navegación.
 * - Enruta las operaciones CRUD (crear, leer, actualizar, IA, etc.)
 *   según el parámetro 'operacion' recibido por GET.
 * - Muestra mensajes flash de éxito/error.
 *
 * Recibe: Parámetro GET 'operacion' para determinar la acción CRUD.
 * Devuelve: HTML con la interfaz de administración y el resultado de la operación.
 */

// Inicia la sesión y protege el acceso solo para administradores
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no es admin, redirige al login
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
        <!-- Carga de estilos y favicon para el panel admin -->
        <link rel="stylesheet" href="css/estilo.css">
        <link rel="icon" href="../img/logo.png" type="image/png">
    </head>
    <body>
        <?php 
        // Registro de logs de actividad admin
        require_once __DIR__ . '/../tools/log.php'; 
        // Conexión a la base de datos
        include_once __DIR__ . "/../inc/conexion_bd.php"; 
        ?>
        <nav>
            <!-- Logo y menú lateral -->
            <img src="../img/logo.png" alt="Logo WebScout" class="logo">
            <?php include "contrl/poblar_menu.php" ?>
            <!-- Botón de cerrar sesión de administrador -->
            <a href="../front/contrl/logout.php" class="logout-admin">Cerrar sesión</a>
        </nav>
        <main>
            <?php
                // Mostrar mensajes flash de éxito, error o información
                mostrarFlash();

                // Enrutador principal: carga el controlador según la operación CRUD solicitada
                $operacion = $_GET['operacion'] ?? null;

                if (!$operacion) {
                    // Vista por defecto: leer registros
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
                    // Operación especial: IA para administración
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