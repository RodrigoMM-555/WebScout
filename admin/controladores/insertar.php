<form action="?operacion=procesainsertar&tabla=<?= $_GET['tabla'] ?>" method="POST">
<?php
        // CREAMOS UN FORMULARIO DINÁMICO
        $resultado = $conexion->query("
            SELECT * FROM ".$_GET['tabla']." LIMIT 1;
        ");	
        // SOLO QUIERO UN ELEMENTO !!!!!!!!!!!!!!!!
        while ($fila = $resultado->fetch_assoc()) {
            foreach($fila as $clave=>$valor){
            echo "
                <div class='control_formulario'>
                <label>".$clave."</label>
                <input 
                    type='text' 
                    name='".$clave."'
                    placeholder='".$clave."'>
                </div>
                ";
            }
        }
    ?>
    <div class="control_formulario">
        <input type="submit" value="Insertar">
    </div>
</form>

<link rel="stylesheet" href="css/estilo.css">






