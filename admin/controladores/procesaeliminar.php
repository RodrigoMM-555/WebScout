<?php
include "../inc/conexion_bd.php";

$sql = "DELETE FROM ".$_GET['tabla']." WHERE id=".$_GET['id'];
$conexion->query($sql);

header("Location: ../?tabla=".$_GET['tabla']."&ordenar_por=".urlencode($_GET['ordenar_por'])."&direccion=".urlencode($_GET['direccion']));
exit;

?>
