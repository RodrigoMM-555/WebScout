<form action="?operacion=procesainsertar&tabla=<?= $_GET['tabla'] ?>" method="POST">
<?php
    // Sacamos el nombre de la tabla
    $tabla = $_GET['tabla'];

    // Pedimos la estructura de la tabla
    $resultado = $conexion->query("DESCRIBE `$tabla`;");
    // Recorremos las columnas
    while ($fila = $resultado->fetch_assoc()) {
        $clave = $fila['Field']; // nombre de la columna

        // Saltar columna auto_increment para no pedirla en el formulario
        if ($fila['Extra'] === 'auto_increment') {
            continue;
        }

        echo "
            <div class='control_formulario'>
                <label>$clave</label>
                <input 
                    type='text'
                    name='$clave'
                    placeholder='$clave'>
            </div>
        ";
    }
?>
    <div class="control_formulario">
        <input type="submit" value="Insertar">
    </div>
</form>

<link rel="stylesheet" href="css/estilo.css">