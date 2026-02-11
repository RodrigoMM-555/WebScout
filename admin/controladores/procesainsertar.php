<?php
    $sql = "INSERT INTO ".$_GET['tabla']." VALUES (";	// Inicio el formateo del SQL

    foreach($_POST as $clave=>$valor){							// Recorro los campos del form

        if($clave == "id"){										// Si eres un id
            $sql.= "NULL,";										// Inserta NULL
        }
        else{

            // Si el campo es contraseña, la hasheamos
            if($clave == "contraseña"){
                $valor = password_hash($valor, PASSWORD_DEFAULT);
            }

            $sql.= "'".$valor."',";								// Inserta el valor
        }
    }

    $sql = substr($sql, 0, -1); // Le quito la ultima coma
    $sql .= ");";

    echo $sql;													// Lo saco por pantalla
    $resultado = $conexion->query($sql);						// Proceso el SQL
    header("Location: ?tabla=".$_GET['tabla']);
?>
