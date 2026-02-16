<table>
    <?php
        $resultado = $conexion->query("
        SELECT * FROM ".$_GET['tabla']." LIMIT 1;
        ");
        while ($fila = $resultado->fetch_assoc()) {
        echo "<tr>";
        foreach($fila as $clave=>$valor){
            echo "<th>".$clave."</th>";
        }
        echo "<th>Editar</th>";
        echo "<th>Eliminar</th>";
        echo "</tr>";
        }
    ?>
    <?php
        $resultado = $conexion->query("
        SELECT * FROM ".$_GET['tabla'].";
        ");
        while ($fila = $resultado->fetch_assoc()) {
        echo "<tr>";
        foreach($fila as $clave=>$valor){

        if ($clave == "id_usuario") {
            $id = (int)$valor;
            $resNombre = $conexion->query("SELECT nombre, apellidos FROM usuarios WHERE id = $id;");
            $user = $resNombre->fetch_assoc();
            echo "<td>".$user['nombre']." ".$user['apellidos']."</td>";
        }
        
            else{
                echo "<td>".$valor."</td>";
            }
        }
        echo '<td><a href="?operacion=actualizar&tabla='.$_GET['tabla'].'&id='.$fila['id'].'">📝</a></td>';  
        echo '<td><a class="eliminar" href="controladores/procesaeliminar.php?tabla='.$_GET['tabla'].'&id='.$fila['id'].'">❌</a></td>';  
        echo "</tr>";
        }
    ?>
</table>
<p>|</p>

<style>
    p{
        color: #f6f7f3;
    }
</style>

<a href="?operacion=insertar&tabla=<?= $_GET['tabla'] ?>" class="boton_insertar">+</a>

