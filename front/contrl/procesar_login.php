<!-- Procesar login -->
<?php
include '../inc/conexion_bd.php';

// Recogemos la ifnormacion de login
$nombre = trim($_POST['usuario']);
$contraseña = $_POST['password'];

// Obtener la contraseña del usuario
$sql = "SELECT contraseña FROM usuarios WHERE nombre = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$resultado = $stmt->get_result();

// Si el usuario no existe
if ($resultado->num_rows === 0) {
    header("Location: ../index.php?error=invalid");
    exit;
}

$stored_password = $resultado->fetch_assoc()['contraseña'];

// Verificar la contraseña
if (!password_verify($contraseña, $stored_password)) {
    header("Location: ../index.php?error=invalid");
    exit;
}

// Iniciar sesión
session_start();
$_SESSION["nombre"] = $nombre;

header("Location: ../inicio.php");
exit;
