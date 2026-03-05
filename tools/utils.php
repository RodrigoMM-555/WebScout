<?php
/**
 * utils.php — Funciones compartidas de WebScout
 * ================================================
 * Se incluye una sola vez con require_once desde cualquier
 * archivo que necesite estas utilidades (limpiarTexto, CSRF,
 * comprobaciones de sesión, mensajes flash…).
 */

// ─────────────────────────────────────────────────────────────
// LIMPIEZA DE TEXTO
// ─────────────────────────────────────────────────────────────

/**
 * Normaliza un texto para usarlo como nombre de carpeta/archivo.
 * Reemplaza espacios por guiones bajos y elimina caracteres especiales.
 */
function limpiarTexto(string $texto): string {
    $texto = str_replace(' ', '_', $texto);
    $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    if ($tmp !== false) {
        $texto = $tmp;
    }
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $texto);
}

// ─────────────────────────────────────────────────────────────
// PROTECCIÓN CSRF
// ─────────────────────────────────────────────────────────────

/**
 * Genera (o reutiliza) un token CSRF guardado en $_SESSION.
 * Devuelve el token como string.
 */
function generarTokenCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Devuelve un <input hidden> con el token CSRF listo para incrustar en formularios.
 */
function campoCSRF(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generarTokenCSRF()) . '">';
}

/**
 * Valida el token CSRF recibido por POST.
 * Si no coincide, detiene la ejecución con 403.
 */
function validarCSRF(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF inválido. Recarga la página e intenta de nuevo.');
    }
}

// ─────────────────────────────────────────────────────────────
// COMPROBACIONES DE SESIÓN / AUTORIZACIÓN
// ─────────────────────────────────────────────────────────────

/**
 * Asegura que el usuario ha iniciado sesión.
 * Si no, redirige al login del front.
 */
function requerirSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['id_usuario'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Asegura que el usuario es administrador.
 * Si no, redirige al login del front.
 */
function requerirAdmin(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// MENSAJES FLASH (feedback al usuario tras acciones)
// ─────────────────────────────────────────────────────────────

/**
 * Guarda un mensaje flash en sesión para mostrarlo en la siguiente página.
 * @param string $tipo  'exito' | 'error' | 'info'
 * @param string $texto  Mensaje a mostrar
 */
function setFlash(string $tipo, string $texto): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

/**
 * Recupera y elimina el mensaje flash de sesión.
 * Devuelve null si no hay ninguno.
 */
function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Renderiza el mensaje flash como HTML si existe.
 * Se llama normalmente después del header en cada página.
 */
function mostrarFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $tipo  = htmlspecialchars($flash['tipo']);
        $texto = htmlspecialchars($flash['texto']);
        echo "<div class='flash flash-{$tipo}'>{$texto}</div>";
    }
}

// ─────────────────────────────────────────────────────────────
// VALIDACIÓN DE TABLA (anti-SQLi para CRUD dinámico)
// ─────────────────────────────────────────────────────────────

/**
 * Valida que el nombre de tabla esté en la whitelist.
 * Devuelve el nombre limpio o detiene la ejecución.
 */
function validarTabla(string $tabla): string {
    $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
    if (!in_array($tabla, TABLAS_PERMITIDAS, true)) {
        http_response_code(400);
        die('Tabla no permitida.');
    }
    return $tabla;
}
