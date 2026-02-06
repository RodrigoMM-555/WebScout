<?php

include "../inc/conexion_bd.php";

$sql = "DELETE FROM ".$_GET['tabla']." WHERE id=".$_GET['id'];
$conexion->query($sql);

header("Location: ../?tabla=".$_GET['tabla']);
exit;

?>
