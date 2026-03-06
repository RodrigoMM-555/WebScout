<?php
/**
 * procesar_login.php — Procesa el formulario de login
 * =====================================================
 * Verifica correo/contraseña contra la BD.
 * Crea sesión con id, nombre y rol; redirige según el tipo de usuario.
 */
include '../../inc/conexion_bd.php';

// Recogemos la información de login
$email = trim($_POST['email'] ?? '');
$contraseña = $_POST['password'];

// Obtener usuario por correo (insensible a mayúsculas/minúsculas)
$sql = "SELECT id, nombre, contraseña, rol FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

// Si el usuario no existe
if ($resultado->num_rows === 0) {
    header("Location: ../../index.php?error=invalid");
    exit;
}

// Obtener datos del usuario
$usuario = $resultado->fetch_assoc();

// Verificar contraseña
if (!password_verify($contraseña, $usuario['contraseña'])) {
    header("Location: ../../index.php?error=invalid");
    exit;
}

// Iniciar sesión
session_start();
$_SESSION["id_usuario"] = (int)$usuario['id'];
$_SESSION["nombre"] = $usuario['nombre'];
$_SESSION["email"] = $email;
$_SESSION["rol"] = $usuario['rol'];

// 🔹 Redirección según rol
if ($usuario['rol'] === 'admin') {
    header("Location: ../../admin/index.php?tabla=educandos&ordenar_por=seccion&direccion=ASC");
} else {
    header("Location: ../inicio.php");
}
exit;
?>