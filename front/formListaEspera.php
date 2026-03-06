<?php
require_once __DIR__ . '/../tools/config.php';
include 'inc/conexion_bd.php';
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

<?php if (isset($_GET['estado']) && $_GET['estado'] === 'ok'): ?>
    <p class="flash flash-exito">La solicitud ha sido procesada correctamente.</p>
<?php endif; ?>

<form class="lista-espera-form" action="contrl/procesaListaEspera.php" method="POST">
    <h1>Formulario de lista de espera</h1>

    <div class="lista-espera-grid">
        <div>
            <label for="nombre_nino">Nombre del niño:</label>
            <input type="text" id="nombre_nino" name="nombre_nino" required>
        </div>

        <div>
            <label for="apellido_ninio">Apellidos del niño:</label>
            <input type="text" id="apellido_ninio" name="apellido_ninio" required>
        </div>

        <div class="campo-completo">
            <label for="fecha_nacimiento">Fecha de nacimiento:</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
        </div>

        <div>
            <label for="nombre_contacto">Nombre del contacto:</label>
            <input type="text" id="nombre_contacto" name="nombre_contacto" required>
        </div>

        <div>
            <label for="telefono_contacto">Teléfono del contacto:</label>
            <input type="text" id="telefono_contacto" name="telefono_contacto" required>
        </div>

        <div class="campo-completo">
            <label for="correo_contacto">Correo del contacto:</label>
            <input type="email" id="correo_contacto" name="correo_contacto" required>
        </div>

        <div class="checks-wrap">
            <div class="check-item">
                <input type="checkbox" id="hermano_en_grupo" name="hermano_en_grupo">
                <label for="hermano_en_grupo">¿Tiene hermano en el grupo?</label>
            </div>

            <div class="check-item">
                <input type="checkbox" id="relacion_con_miembro" name="relacion_con_miembro">
                <label for="relacion_con_miembro">¿Tiene relación con algún miembro del grupo?</label>
            </div>

            <div class="check-item">
                <input type="checkbox" id="familia_antiguo_scouter" name="familia_antiguo_scouter">
                <label for="familia_antiguo_scouter">¿Es parte de la familia de un antiguo scouter?</label>
            </div>

            <div class="check-item">
                <input type="checkbox" id="estuvo_en_grupo" name="estuvo_en_grupo">
                <label for="estuvo_en_grupo">¿Ha estado en el grupo antes?</label>
            </div>
        </div>

        <div class="campo-completo">
            <label style="display: none;" for="explicacion_relacion">Explicación de la relación:</label>
            <textarea style="display: none;" id="explicacion_relacion" name="explicacion_relacion"></textarea>
        </div>

        <div class="campo-completo">
            <label for="comentarios">Comentarios:</label>
            <textarea id="comentarios" name="comentarios"></textarea>
        </div>
    </div>

    <script>
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const explicacionLabel = document.querySelector('label[for="explicacion_relacion"]');
        const explicacionTextarea = document.getElementById('explicacion_relacion');

        function actualizarVisibilidadExplicacion() {
            const algunoMarcado = Array.from(checkboxes).some(checkbox => checkbox.checked);
            explicacionLabel.style.display = algunoMarcado ? 'block' : 'none';
            explicacionTextarea.style.display = algunoMarcado ? 'block' : 'none';
        }
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', actualizarVisibilidadExplicacion);
        });

        actualizarVisibilidadExplicacion();
    </script>

    <div class="acciones-formulario">
        <button type="button" class="btn-secundario" onclick="window.history.back()">Atrás</button>
        <button type="submit">Enviar solicitud</button>
    </div>
</form>
</main>

</body>
</html>