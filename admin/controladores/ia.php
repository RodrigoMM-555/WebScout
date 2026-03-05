<?php
/**
 * ia.php — Consulta asistida por IA para administración
 *
 * Flujo general:
 * 1) Verifica sesión y rol admin.
 * 2) Carga conexión a BD (si no viene inyectada desde admin/index.php).
 * 3) Construye un resumen del esquema de BD.
 * 4) Envía prompt a Ollama para obtener una consulta SQL.
 * 5) Valida que la consulta sea SOLO de lectura (SELECT/SHOW).
 * 6) Ejecuta la consulta y muestra resultados en tabla HTML.
 */

// 1) Asegurar sesión activa para poder validar permisos de usuario.
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// 2) Cargar configuración global si todavía no existe (BASE_URL, DB_NAME, etc.).
if (!defined('BASE_URL')) {
  require_once __DIR__ . '/../../tools/config.php';
}

// 3) Seguridad de acceso: solo usuarios con rol admin pueden usar esta vista.
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: " . BASE_URL . "/index.php");
  exit;
}

// 4) Cargar conexión cuando el archivo se ejecuta de forma aislada.
// Si se incluye desde admin/index.php, normalmente $conexion ya existe.
if (!isset($conexion) || !($conexion instanceof mysqli)) {
  require_once __DIR__ . '/../inc/conexion_bd.php';
}

/**
 * Limpia la respuesta textual de la IA para dejar SQL puro.
 *
 * - Elimina espacios iniciales/finales.
 * - Elimina fences de markdown si el modelo los devuelve por error.
 */
function limpiarRespuestaSQLIA(string $texto): string {
  $texto = trim($texto);
  $texto = preg_replace('/^```sql\s*/i', '', $texto);
  $texto = preg_replace('/^```\s*/i', '', $texto);
  $texto = preg_replace('/```$/', '', $texto);
  return trim($texto);
}

/**
 * Valida que la consulta SQL sea segura para este apartado.
 *
 * Reglas aplicadas:
 * - Debe ser una única sentencia.
 * - Solo permite SELECT o SHOW.
 * - Bloquea comentarios SQL y palabras clave de escritura/DDL.
 */
function esConsultaPermitida(string $sql): bool {
  $sql = trim($sql);
  // No permitir consultas vacías.
  if ($sql === '') {
    return false;
  }

  // Bloquear comentarios para reducir intentos de evasión.
  if (preg_match('/(--|#|\/\*)/', $sql)) {
    return false;
  }

  // Permitimos un ';' final opcional, pero no múltiples sentencias.
  $sinPuntoComaFinal = rtrim($sql);
  if (substr($sinPuntoComaFinal, -1) === ';') {
    $sinPuntoComaFinal = substr($sinPuntoComaFinal, 0, -1);
  }

  // Si queda otro ';' dentro, entonces es multi-sentencia => bloquear.
  if (strpos($sinPuntoComaFinal, ';') !== false) {
    return false;
  }

  // Solo se aceptan consultas que empiecen por SELECT o SHOW.
  if (!preg_match('/^\s*(SELECT|SHOW)\b/i', $sinPuntoComaFinal)) {
    return false;
  }

  // Lista explícita de verbos peligrosos/no permitidos en este módulo.
  $bloqueadas = '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|RENAME|REPLACE|GRANT|REVOKE|CALL|EXEC|MERGE|SET|USE|DESCRIBE|DESC)\b/i';
  if (preg_match($bloqueadas, $sinPuntoComaFinal)) {
    return false;
  }

  return true;
}

/**
 * Construye un resumen de esquema para mejorar el contexto del modelo.
 *
 * Devuelve texto tipo:
 * - Tabla: educandos
 * - Columnas: id (int), nombre (varchar), ...
 */
function obtenerResumenEsquema(mysqli $conexion, string $db): string {
  $salida = [];
  $resTablas = $conexion->query("SHOW TABLES");
  if (!$resTablas) {
    return '';
  }

  while ($fila = $resTablas->fetch_array(MYSQLI_NUM)) {
    $tabla = $fila[0];
    $tablaEsc = $conexion->real_escape_string($tabla);
    $salida[] = "Tabla: {$tabla}";

    $resCols = $conexion->query("SHOW COLUMNS FROM `{$tablaEsc}`");
    if ($resCols) {
      $columnas = [];
      while ($col = $resCols->fetch_assoc()) {
        $columnas[] = $col['Field'] . ' (' . $col['Type'] . ')';
      }
      $salida[] = 'Columnas: ' . implode(', ', $columnas);
    }
  }

  return implode("\n", $salida);
}

function normalizarPeticionIA(string $peticion): string {
  $texto = mb_strtolower(trim($peticion), 'UTF-8');

  $reemplazos = [
    'pendiaentes' => 'pendientes',
    'pendientees' => 'pendientes',
    'resonder' => 'responder',
    'respondar' => 'responder',
    'asitencia' => 'asistencia',
    'niños' => 'educandos',
    'niños' => 'educandos',
    'hijos' => 'educandos'
  ];

  $texto = strtr($texto, $reemplazos);
  $texto = preg_replace('/\s+/', ' ', $texto);
  return trim($texto);
}

function generarSqlDeterministaBasica(string $peticionNormalizada): ?string {
  $pidePendientes = preg_match('/pendiente|sin responder|no respond/', $peticionNormalizada) === 1;
  $pideAsistencia = preg_match('/asistencia|responder|respuesta/', $peticionNormalizada) === 1;
  $pideCampamento = preg_match('/campamento/', $peticionNormalizada) === 1;

  if ($pidePendientes && $pideAsistencia && $pideCampamento) {
    return "SELECT e.nombre, e.apellidos, a.titulo, s.asistencia "
      . "FROM asistencias s "
      . "JOIN educandos e ON e.id = s.id_educando "
      . "JOIN avisos a ON a.id = s.id_aviso "
      . "WHERE s.asistencia = 'pendiente' AND a.tipo = 'campamento' "
      . "ORDER BY e.nombre, e.apellidos";
  }

  return null;
}

// Configuración del endpoint y modelo local de Ollama.
$OLLAMA_URL = "http://localhost:11434/api/generate";
$MODEL = "qwen2.5-coder:7b";

// Variables de estado de la vista/controlador.
$peticion = trim($_POST['peticion'] ?? '');
$sqlGenerado = '';
$error = '';
$columnas = [];
$filas = [];
$mostrarNombreCompleto = false;
$columnaNombreOriginal = null;
$columnaApellidoOriginal = null;

// Solo procesa si llega formulario POST con una petición no vacía.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $peticion !== '') {
  // 1) Extraer esquema actual de la base de datos.
  $esquema = obtenerResumenEsquema($conexion, $db ?? DB_NAME);
  $peticionNormalizada = normalizarPeticionIA($peticion);
  $sqlGenerado = generarSqlDeterministaBasica($peticionNormalizada);

  // Si hay coincidencia con un caso frecuente, evitamos depender del modelo.
  if ($sqlGenerado === null) {

    // 2) Prompt guiado para forzar SQL de lectura y mejorar interpretación.
    $prompt = "Eres un generador de SQL para MySQL del proyecto WebScout.\n"
      . "Esquema disponible:\n{$esquema}\n\n"
      . "Petición original: {$peticion}\n"
      . "Petición normalizada: {$peticionNormalizada}\n\n"
      . "Reglas obligatorias:\n"
      . "1) Devuelve UNA sola sentencia SQL que empiece por SELECT o SHOW.\n"
      . "2) No uses markdown, explicación ni bloques de código.\n"
      . "3) No incluyas comentarios SQL ni palabras clave de escritura/DDL.\n"
      . "4) No generes consultas que modifiquen datos (INSERT, UPDATE, DELETE).\n"
      . "5) Niños o niñas es lo mismo que educandos.\n";
      

    // Payload JSON para la API de Ollama.
    $data = [
      "model" => $MODEL,
      "prompt" => $prompt,
      "stream" => false
    ];

    // 3) Llamada HTTP a Ollama.
    $ch = curl_init($OLLAMA_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);

    $response = curl_exec($ch);
    // Errores de comunicación con la IA (servicio caído, timeout, etc.).
    if ($response === false) {
      $error = 'No se pudo conectar con Ollama: ' . curl_error($ch);
    }
    curl_close($ch);

    if (!$error) {
      // 4) Parsear y limpiar salida del modelo.
      $result = json_decode($response, true);
      $sqlGenerado = limpiarRespuestaSQLIA((string)($result['response'] ?? ''));
    }
  }

  // 5) Capa de seguridad antes de tocar la base de datos.
  if (!esConsultaPermitida($sqlGenerado)) {
    $error = 'La consulta generada no es válida. Solo se permiten SELECT o SHOW en una única sentencia.';
  } else {
    // 6) Ejecutar consulta de lectura y capturar resultados tabulares.
    $resultadoConsulta = $conexion->query($sqlGenerado);
    if (!$resultadoConsulta) {
      $error = 'Error al ejecutar la consulta: ' . $conexion->error;
    } elseif ($resultadoConsulta instanceof mysqli_result) {
      // Obtener nombres de columnas para encabezado de la tabla.
      while ($campo = $resultadoConsulta->fetch_field()) {
        $columnas[] = $campo->name;
      }

      // Obtener todas las filas para dibujar el cuerpo de la tabla.
      while ($fila = $resultadoConsulta->fetch_assoc()) {
        $filas[] = $fila;
      }

      // Unificar nombre + apellido(s) en una sola columna cuando ambos existan.
      // Soporta variantes habituales: apellido/apellidos (singular y plural).
      foreach ($columnas as $columna) {
        $normalizada = strtolower($columna);
        if ($columnaNombreOriginal === null && $normalizada === 'nombre') {
          $columnaNombreOriginal = $columna;
        }
        if ($columnaApellidoOriginal === null && in_array($normalizada, ['apellido', 'apellidos'], true)) {
          $columnaApellidoOriginal = $columna;
        }
      }

      if ($columnaNombreOriginal !== null && $columnaApellidoOriginal !== null) {
        $mostrarNombreCompleto = true;
        $columnas = array_values(array_filter(
          $columnas,
          fn($col) => $col !== $columnaNombreOriginal && $col !== $columnaApellidoOriginal
        ));
        array_unshift($columnas, 'nombre');
      }
    }
  }
}
?>

<!-- Vista principal del módulo IA (dentro del panel admin). -->
<h1>IA Admin</h1>

<!-- Formulario de lenguaje natural: el usuario escribe qué quiere consultar. -->
<form action="?operacion=ia_admin" method="POST">
  <div class="control_formulario" style="grid-column: 1 / -1;">
    <label for="peticion">Qué quieres consultar</label>
    <!-- Se mantiene el valor anterior para facilitar iteraciones de consulta. -->
    <input id="peticion" type="text" name="peticion" value="<?= htmlspecialchars($peticion) ?>" required>
  </div>
  <div class="control_formulario" style="grid-column: 1 / -1;">
    <button type="submit">Generar y ejecutar consulta</button>
  </div>
</form>

<!-- Mensaje de error controlado (con escape HTML anti-XSS). -->
<?php if ($error): ?>
  <p style="color:#b00020;"><strong>Error:</strong> <?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<!-- Si hay columnas, se renderiza la tabla de resultados. -->
<?php if (!empty($columnas)): ?>
  <table>
    <thead>
      <tr>
        <?php foreach ($columnas as $col): ?>
          <th><?= htmlspecialchars($col) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <!-- Caso borde: consulta válida pero sin filas resultantes. -->
      <?php if (empty($filas)): ?>
        <tr><td colspan="<?= count($columnas) ?>">Sin resultados.</td></tr>
      <?php else: ?>
        <!-- Pintado de filas y celdas escapando todo contenido dinámico. -->
        <?php foreach ($filas as $fila): ?>
          <tr>
            <?php foreach ($columnas as $col): ?>
              <?php if ($mostrarNombreCompleto && $col === 'nombre'): ?>
                <td><?= htmlspecialchars(trim((string)($fila[$columnaNombreOriginal] ?? '') . ' ' . (string)($fila[$columnaApellidoOriginal] ?? ''))) ?></td>
              <?php else: ?>
                <td><?= htmlspecialchars((string)($fila[$col] ?? '')) ?></td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
<?php endif; ?>