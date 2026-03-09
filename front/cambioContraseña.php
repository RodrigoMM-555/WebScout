<?php
/**
 * cambioContraseña.php — Cambio de contraseña de usuario autenticado
 * ===================================================================
 * Muestra formulario y procesa el cambio de contraseña del usuario en sesión.
 * Aplica validaciones de seguridad (CSRF, verificación de contraseña actual
 * y reglas de complejidad de la nueva contraseña).
 */

include("../inc/conexion_bd.php");

requerirSesion();

$id_usuario = (int)($_SESSION["id_usuario"] ?? 0);
$mensaje = '';
$tipoMensaje = 'info';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCSRF();

    $contraseña_actual = (string)($_POST["contraseña_actual"] ?? '');
    $contraseña_nueva = (string)($_POST["contraseña_nueva"] ?? '');
    $confirmar = (string)($_POST["confirmar_contraseña"] ?? '');

    $stmt = $conexion->prepare("SELECT contraseña FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        $mensaje = 'No se encontró el usuario en sesión.';
        $tipoMensaje = 'error';
    } elseif (!password_verify($contraseña_actual, (string)$usuario['contraseña'])) {
        $mensaje = 'La contraseña actual es incorrecta.';
        $tipoMensaje = 'error';
    } elseif ($contraseña_nueva !== $confirmar) {
        $mensaje = 'Las nuevas contraseñas no coinciden.';
        $tipoMensaje = 'error';
    } else {
        $tieneLetra = (bool)preg_match('/[A-Za-z]/', $contraseña_nueva);
        $tieneMayuscula = (bool)preg_match('/[A-Z]/', $contraseña_nueva);
        $tieneNumero = (bool)preg_match('/[0-9]/', $contraseña_nueva);
        $tieneEspecial = (bool)preg_match('/[^A-Za-z0-9]/', $contraseña_nueva);

        if (!$tieneLetra || !$tieneMayuscula || !($tieneNumero || $tieneEspecial)) {
            $mensaje = 'La nueva contraseña debe incluir letras, al menos una mayúscula y al menos un número o carácter especial.';
            $tipoMensaje = 'error';
        } else {
            $hash = password_hash($contraseña_nueva, PASSWORD_DEFAULT);

            $stmt = $conexion->prepare("UPDATE usuarios SET contraseña = ?, cambio_contraseña = 0 WHERE id = ?");
            $stmt->bind_param("si", $hash, $id_usuario);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                // Tras cambiar contraseña, forzamos nuevo login con credenciales actualizadas.
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();

                header('Location: ../index.php');
                exit;
            } else {
                $mensaje = 'No se pudo actualizar la contraseña. Inténtalo de nuevo.';
                $tipoMensaje = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
    <link rel="stylesheet" href="css/webscout.css">
</head>
<body class="login cambio-password-page">
    <main class="cambio-password-card">
        <h2>Cambiar contraseña</h2>

        <?php if ($mensaje !== ''): ?>
            <p class="cambio-password-msg <?= $tipoMensaje === 'exito' ? 'is-success' : 'is-error' ?>">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="?" method="POST" id="form-cambio-pass" class="cambio-password-form">
            <?= campoCSRF() ?>

            <div class="campo-pass">
                <label for="contraseña_actual">Contraseña actual</label>
                <input type="password" id="contraseña_actual" name="contraseña_actual" required>
            </div>

            <div class="campo-pass">
                <label for="contraseña_nueva">Nueva contraseña</label>
                <input
                    type="password"
                    id="contraseña_nueva"
                    name="contraseña_nueva"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <div class="campo-pass">
                <label for="confirmar_contraseña">Confirmar nueva contraseña</label>
                <input type="password" id="confirmar_contraseña" name="confirmar_contraseña" required autocomplete="new-password">
            </div>

            <div class="cambio-password-hint">
                <p>Tu nueva contraseña debe cumplir:</p>
                <ul>
                    <li>Al menos 8 caracteres</li>
                    <li>Al menos una letra</li>
                    <li>Al menos una mayúscula</li>
                    <li>Al menos un número o carácter especial</li>
                </ul>
            </div>

            <input type="submit" value="Cambiar contraseña">
        </form>
    </main>

    <script>
    (function() {
        const form = document.getElementById('form-cambio-pass');
        const nueva = document.getElementById('contraseña_nueva');

        if (!form || !nueva) return;

        function validarComplejidadCliente(valor) {
            const tieneLetra = /[A-Za-z]/.test(valor);
            const tieneMayuscula = /[A-Z]/.test(valor);
            const tieneNumero = /[0-9]/.test(valor);
            const tieneEspecial = /[^A-Za-z0-9]/.test(valor);
            return tieneLetra && tieneMayuscula && (tieneNumero || tieneEspecial);
        }

        form.addEventListener('submit', function(e) {
            if (!validarComplejidadCliente(nueva.value)) {
                e.preventDefault();
                alert('La nueva contraseña debe incluir letras, al menos una mayúscula y al menos un número o carácter especial.');
            }
        });
    })();
    </script>
</body>
</html>