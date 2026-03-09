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

// ─────────────────────────────────────────────────────────────
// LÓGICA SCOUT: RONDA Y SECCIONES POR AÑO DE NACIMIENTO
// ─────────────────────────────────────────────────────────────

/**
 * Devuelve el curso scout actual.
 * - De enero a agosto: año actual (ej. marzo 2026 => curso 2026)
 * - Desde septiembre: año siguiente (ej. octubre 2026 => curso 2027)
 */
function obtenerCursoScoutActual(?DateTimeInterface $fecha = null): int {
    $fechaBase = $fecha ?? new DateTimeImmutable('now');
    $anio = (int)$fechaBase->format('Y');
    $mes = (int)$fechaBase->format('n');
    return ($mes >= 9) ? $anio + 1 : $anio;
}

/**
 * Reglas de sección según diferencia "cursoScout - anioNacimiento".
 */
function obtenerReglasSeccionScout(): array {
    return [
        ['min' => 6,  'max' => 7,  'seccion' => 'colonia'],
        ['min' => 8,  'max' => 10, 'seccion' => 'manada'],
        ['min' => 11, 'max' => 13, 'seccion' => 'tropa'],
        ['min' => 14, 'max' => 16, 'seccion' => 'posta'],
        ['min' => 17, 'max' => 19, 'seccion' => 'rutas'],
    ];
}

/**
 * Calcula la sección scout de un educando según su año de nacimiento.
 * Devuelve null si queda fuera de los rangos definidos.
 */
function calcularSeccionScoutPorAnio(int $anioNacimiento, ?int $cursoScout = null): ?string {
    $curso = $cursoScout ?? obtenerCursoScoutActual();
    $diferencia = $curso - $anioNacimiento;

    foreach (obtenerReglasSeccionScout() as $regla) {
        if ($diferencia >= $regla['min'] && $diferencia <= $regla['max']) {
            return $regla['seccion'];
        }
    }

    return null;
}

/**
 * Devuelve el rango de años de nacimiento mostrado en formularios scout.
 */
function obtenerRangoAniosScout(?int $cursoScout = null): array {
    $curso = $cursoScout ?? obtenerCursoScoutActual();
    return [
        'max' => $curso - 6,
        'min' => $curso - 19,
    ];
}

/**
 * Sincroniza la columna educandos.seccion con el año de nacimiento actual.
 * Devuelve el número de registros modificados.
 */
function sincronizarSeccionesEducandos(mysqli $conexion, ?int $cursoScout = null): int {
    $curso = $cursoScout ?? obtenerCursoScoutActual();
    $modificados = 0;

    $sql = "SELECT id, anio, seccion FROM educandos";
    $res = $conexion->query($sql);
    if (!$res) {
        return 0;
    }

    $stmtUpdate = $conexion->prepare("UPDATE educandos SET seccion = ? WHERE id = ?");
    if (!$stmtUpdate) {
        return 0;
    }

    while ($fila = $res->fetch_assoc()) {
        $id = (int)$fila['id'];
        $anio = (int)$fila['anio'];
        $seccionActual = strtolower(trim((string)($fila['seccion'] ?? '')));
        $seccionEsperada = calcularSeccionScoutPorAnio($anio, $curso);

        if ($seccionEsperada === null || $seccionEsperada === $seccionActual) {
            continue;
        }

        $stmtUpdate->bind_param('si', $seccionEsperada, $id);
        if ($stmtUpdate->execute()) {
            $modificados++;
        }
    }

    $stmtUpdate->close();
    return $modificados;
}
