<?php
/**
 * config.php — Configuración centralizada de WebScout
 * ====================================================
 * Todas las constantes y credenciales se definen aquí.
 * Incluir este archivo UNA sola vez al inicio de cada punto de entrada.
 *
 * IMPORTANTE: en producción, mover este archivo fuera del document root
 * o usar variables de entorno (.env) para las credenciales.
 */

// ── Credenciales de base de datos ──────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'Uwebscout');
define('DB_PASS', 'Uwebscout5$');
define('DB_NAME', 'WebScout');

// ── Rutas base ─────────────────────────────────────────────
// Ajustar si el proyecto se despliega en otro path
define('BASE_URL',  '/WebScout');          // URL relativa desde la raíz del servidor
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . BASE_URL); // Ruta absoluta en disco

// ── Constantes de permisos (bitmask) ───────────────────────
define('PERM_COCHE',    1);   // Autorización vehículo privado
define('PERM_WHATSAPP',  2);   // Grupo de WhatsApp
define('PERM_SOLO',      4);   // Irse solo
define('PERM_FOTOS',     8);   // Publicación de imágenes/fotos

// ── Tablas permitidas (whitelist para evitar SQL injection) ─
define('TABLAS_PERMITIDAS', ['usuarios', 'educandos', 'avisos', 'asistencias', 'lista_espera']);
