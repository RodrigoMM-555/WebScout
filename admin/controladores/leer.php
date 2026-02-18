<?php
// Recogemos los parámetros de ordenación si vienen por GET
$ordenarPor = $_GET['ordenar_por'] ?? 'id';
$direccion = $_GET['direccion'] ?? 'ASC'; // ASC o DESC
$tabla = $_GET['tabla'] ?? 'educandos';

// Obtener las columnas dinámicamente desde la base de datos
$opcionesOrden = [];
$columnas_result = $conexion->query("SHOW COLUMNS FROM `$tabla`");
while($col = $columnas_result->fetch_assoc()){
    $nombre = $col['Field'];
    // Creamos un label bonito automáticamente
    $label = ucfirst(str_replace('_',' ',$nombre));
    if ($nombre == "id_usuario") {
        $label = "Madre/Padre";
    } elseif ($nombre == "anio") {
        $label = "Año";
    }
    $opcionesOrden[$nombre] = $label;
}
?>

<div class="tabla-controles" style="display:flex; align-items:center; margin-bottom:10px; gap:10px; flex-wrap:wrap;">
    <!-- Botones de ordenación dinámicos -->
    <div style="display:flex; gap:5px; flex-wrap:wrap;">
        <?php foreach($opcionesOrden as $columna => $label): 
            $nuevaDireccion = ($ordenarPor == $columna && $direccion == 'ASC') ? 'DESC' : 'ASC';
        ?>
            <a href="?tabla=<?= htmlspecialchars($tabla) ?>&ordenar_por=<?= $columna ?>&direccion=<?= $nuevaDireccion ?>" 
               style="padding:5px 10px; background:#555; color:#fff; text-decoration:none; border-radius:4px; <?= $ordenarPor==$columna?'background:#007bff;':'' ?>">
                <?= $label ?> <?= ($ordenarPor==$columna)?($direccion=='ASC'?'↑':'↓'):'' ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Botón de insertar -->
    <a href="?operacion=insertar&tabla=<?= htmlspecialchars($tabla) ?>" class="boton_insertar" 
       style="margin-left:auto; padding:5px 10px; background:green; color:#fff; text-decoration:none; border-radius:4px;">+</a>
</div>


<!-- Tabla -->
<table>
    <?php
        $resultado = $conexion->query("SELECT * FROM $tabla LIMIT 1;");
        while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            foreach($fila as $clave=>$valor){
                if ($clave == "id_usuario") {
                    echo "<th>Madre/Padre</th>";
                    continue;
                } elseif ($clave == "anio") {
                    echo "<th>Año</th>";
                    continue;
                } else {
                $clave = ucfirst(str_replace('_',' ',$clave));
                echo "<th>".$clave."</th>";
                }
            }
            echo "<th>Editar</th><th>Eliminar</th>";
            echo "</tr>";
        }

        // Ahora la consulta completa con orden
        $resultado = $conexion->query("SELECT * FROM `$tabla` ORDER BY `$ordenarPor` $direccion;");

        // Colores por sección
        $coloresSeccion = [
            "colonia" => "seccion-colonia",
            "manada"  => "seccion-manada",
            "tropa"   => "seccion-tropa",
            "posta"   => "seccion-posta",
            "rutas"   => "seccion-rutas"
        ];

        while ($fila = $resultado->fetch_assoc()) {
            $claseFila = "";
            if (isset($fila['seccion'])) {
                $seccion = strtolower($fila['seccion']);
                if (isset($coloresSeccion[$seccion])) $claseFila = $coloresSeccion[$seccion];
            }

            echo "<tr class='$claseFila'>";
            foreach($fila as $clave => $valor){
                if ($clave == "id_usuario") {
                    $id = (int)$valor;
                    $resNombre = $conexion->query("SELECT nombre, apellidos FROM usuarios WHERE id = $id;");
                    $user = $resNombre->fetch_assoc();
                    echo "<td>".$user['nombre']." ".$user['apellidos']."</td>";
                } else {
                    echo "<td>".$valor."</td>";
                }
            }

            echo '<td><a href="?operacion=actualizar&tabla='.$tabla.'&id='.$fila['id'].'">📝</a></td>';  
            echo '<td><a class="eliminar" href="controladores/procesaeliminar.php?tabla='.$tabla.'&id='.$fila['id'].'">❌</a></td>';  
            echo "</tr>";
        }
    ?>
</table>

<script>
document.querySelectorAll('.eliminar').forEach(function(enlace) {
    enlace.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm("¿Estás seguro de que quieres eliminar este elemento?")) {
            window.location.href = this.href;
        }
    });
});
</script>
