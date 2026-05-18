<?php
/**
 * formListaEspera.php — Formulario público de preinscripción
 * ----------------------------------------------------------
 * Muestra el formulario de lista de espera y permite enviarlo
 * a contrl/procesaListaEspera.php.
 *
 * Recibe: Nada (GET)
 * Devuelve: HTML con formulario y mensajes de estado
 */
// --- INICIO BLOQUE DE INCLUSIÓN Y CONFIGURACIÓN ---
// Incluye configuración, conexión y recursos necesarios
// --- FIN BLOQUE DE INCLUSIÓN Y CONFIGURACIÓN ---
// --- INICIO BLOQUE DE MENSAJES FLASH ---
// Muestra mensajes de éxito o error según el estado de la solicitud
// --- FIN BLOQUE DE MENSAJES FLASH ---
// --- INICIO BLOQUE DE FORMULARIO PRINCIPAL ---
// Renderiza el formulario de lista de espera
// --- FIN BLOQUE DE FORMULARIO PRINCIPAL ---

require_once __DIR__ . '/../tools/config.php';
include '../inc/conexion_bd.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebScout — Lista de espera</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/front/css/webscout.css">
</head>
<body class="lista-espera-page">
<main class="lista-espera-wrap">

<?php 
if (isset($_GET['estado']) && $_GET['estado'] === 'ok'):
    echo '<p class="flash flash-exito" data-i18n="solicitud_ok">La solicitud ha sido procesada correctamente.</p>';
elseif (isset($_GET['estado']) && $_GET['estado'] === 'error_preparacion'):
    echo '<p class="flash flash-error" data-i18n="solicitud_error">Hubo un error al preparar la solicitud. Por favor, inténtalo de nuevo.</p>';
elseif (isset($_GET['estado']) && $_GET['estado'] === 'error_insercion'):
    echo '<p class="flash flash-error" data-i18n="solicitud_error">Hubo un error al insertar la solicitud. Por favor, inténtalo de nuevo.</p>';
elseif (isset($_GET['estado']) && $_GET['estado'] === 'error_campos'):
    echo '<p class="flash flash-error" data-i18n="solicitud_error_campos">Por favor, completa todos los campos obligatorios.</p>';
endif;
     // Muestra mensajes flash según el valor de 'estado' en la URL:
     // - ok: éxito
     // - error_preparacion: error al preparar
     // - error_insercion: error al insertar
     // - error_campos: faltan campos obligatorios
?>


<!-- Formulario principal de solicitud -->
<form class="lista-espera-form" action="contrl/procesaListaEspera.php" method="POST">
    <h1 data-i18n="form_lista_espera">Formulario de lista de espera</h1>
    <!-- Cada campo del formulario es obligatorio, marcado con required -->

    <div class="lista-espera-grid">
        <div>
            <label for="nombre_nino" data-i18n="nombre_nino_label">Nombre del niño:</label>
            <input type="text" id="nombre_nino" name="nombre_nino" required>
            <small class='aviso-obligatorio'>*Obligatorio</small>
        </div>
                <!-- Campo para el nombre del niño, obligatorio -->

        <div>
            <label for="apellido_ninio" data-i18n="apellidos_nino_label">Apellidos del niño:</label>
            <input type="text" id="apellido_ninio" name="apellido_ninio" required>
            <small class='aviso-obligatorio'>*Obligatorio</small>
        </div>
                <!-- Campo para los apellidos del niño, obligatorio -->

        <div class="campo-completo">
            <label for="fecha_nacimiento">Fecha de nacimiento:</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
            <small class='aviso-obligatorio'>*Obligatorio</small>
        </div>
                <!-- Campo para la fecha de nacimiento, obligatorio -->

        <div>
            <label for="nombre_contacto">Nombre del contacto:</label>
            <input type="text" id="nombre_contacto" name="nombre_contacto" required>
            <small class='aviso-obligatorio'>*Obligatorio</small>
        </div>
                <!-- Campo para el nombre del contacto, obligatorio -->

        <div>
            <label for="telefono_contacto">Teléfono del contacto:</label>
            <input type="text" id="telefono_contacto" name="telefono_contacto" required>
            <small class='aviso-obligatorio'>*Obligatorio</small>
        </div>
                <!-- Campo para el teléfono del contacto, obligatorio -->

        <div class="campo-completo">
            <label for="correo_contacto">Correo del contacto:</label>
            <input type="email" id="correo_contacto" name="correo_contacto" required>
            <small class='aviso-obligatorio'>*Obligatorio</small>
        </div>
                <!-- Campo para el correo electrónico del contacto, obligatorio -->

        <div class="campo-completo">
            <label for="direccion_contacto">Dirección del contacto:</label>
            <input type="text" id="direccion_contacto" name="direccion_contacto" required>
            <small class='aviso-obligatorio'>*Obligatorio</small>
        </div>
                <!-- Campo para la dirección del contacto, obligatorio -->

        <div class="checks-wrap">
            <div class="check-item">
                <input type="checkbox" id="hermano_en_grupo" name="hermano_en_grupo">
                <label for="hermano_en_grupo">¿Es hermano o hermana de algun miembro del grupo, scouter o educando?</label>
            </div>
                    <!-- Checkbox: ¿Es hermano de algún miembro? -->

            <div class="check-item">
                <input type="checkbox" id="relacion_con_miembro" name="relacion_con_miembro">
                <label for="relacion_con_miembro">¿Tiene otro tipo de relacion familiar con algun miembro del grupo?</label>
            </div>
                    <!-- Checkbox: ¿Tiene otra relación familiar? -->

            <div class="check-item">
                <input type="checkbox" id="familia_antiguo_scouter" name="familia_antiguo_scouter">
                <label for="familia_antiguo_scouter">¿Es familiar de un antiguo scouter?</label>
            </div>
                    <!-- Checkbox: ¿Familiar de antiguo scouter? -->

            <div class="check-item">
                <input type="checkbox" id="estuvo_en_grupo" name="estuvo_en_grupo">
                <label for="estuvo_en_grupo">¿Ha estado apuntado en el grupo antes?</label>
            </div>
                    <!-- Checkbox: ¿Ha estado antes en el grupo? -->
        </div>

        <div class="campo-completo">
            <label style="display: none;" for="explicacion_relacion">Explicación de la relación:</label>
            <textarea style="display: none;" id="explicacion_relacion" name="explicacion_relacion"></textarea>
        </div>
                <!-- Campo condicional: explicación de la relación, solo visible si se marca algún check -->

        <div class="campo-completo">
            <label for="comentarios">Comentarios:</label>
            <textarea id="comentarios" name="comentarios"></textarea>
        </div>
                <!-- Campo opcional para comentarios adicionales -->
    </div>

    <script>
        // Referencias a checks y bloque de explicación condicional.
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const explicacionLabel = document.querySelector('label[for="explicacion_relacion"]');
        const explicacionTextarea = document.getElementById('explicacion_relacion');

        // Regla UX:
        // - Si se marca cualquier condición especial, se pide explicación.
        // - Si no hay ninguna marcada, se oculta para simplificar el formulario.
        function actualizarVisibilidadExplicacion() {
            const algunoMarcado = Array.from(checkboxes).some(checkbox => checkbox.checked);
            explicacionLabel.style.display = algunoMarcado ? 'block' : 'none';
            explicacionTextarea.style.display = algunoMarcado ? 'block' : 'none';
        }

        // Enlazar la lógica a todos los checks de prioridad.
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', actualizarVisibilidadExplicacion);
        });

        // Ejecutar una vez al cargar para dejar estado inicial correcto.
        actualizarVisibilidadExplicacion();
    </script>
        // Script: muestra/oculta el campo de explicación según los checks marcados
        // Si algún checkbox está marcado, se muestra el textarea de explicación
        // Si no, se oculta para simplificar el formulario

    <div class="acciones-formulario">
        <button type="button" class="btn-secundario" onclick="window.history.back()">Atrás</button>
        <button type="submit">Enviar solicitud</button>
    </div>
        <!-- Botón para volver atrás y botón para enviar el formulario -->
</form>
</main>

</body>
</html>