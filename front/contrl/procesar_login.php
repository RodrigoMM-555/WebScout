<?php
/**
 * procesar_login.php — Procesa el formulario de login
 * =====================================================
 * Verifica usuario/contraseña contra la BD.
 * Crea sesión con nombre y rol, redirige según el tipo de usuario.
 */
include '../inc/conexion_bd.php';

// Recogemos la información de login
$nombre = trim($_POST['usuario']);
$contraseña = $_POST['password'];

// Obtener contraseña y rol
$sql = "SELECT contraseña, rol FROM usuarios WHERE nombre = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$resultado = $stmt->get_result();

// Si el usuario no existe
if ($resultado->num_rows === 0) {
    header("Location: ../index.php?error=invalid");
    exit;
}

// Obtener datos del usuario
$usuario = $resultado->fetch_assoc();

// Verificar contraseña
if (!password_verify($contraseña, $usuario['contraseña'])) {
    header("Location: ../index.php?error=invalid");
    exit;
}

// Iniciar sesión
session_start();
$_SESSION["nombre"] = $nombre;
$_SESSION["rol"] = $usuario['rol'];

// 🔹 Redirección según rol
if ($usuario['rol'] === 'admin') {
    header("Location: ../../admin/index.php?tabla=educandos&ordenar_por=seccion&direccion=ASC");
} else {
    header("Location: ../inicio.php");
}
exit;
?>