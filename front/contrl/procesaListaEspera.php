<?php
include '../../inc/conexion_bd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	exit('Método no permitido.');
}

$nombre_nino = trim($_POST['nombre_nino'] ?? '');
$apellido_ninio = trim($_POST['apellido_ninio'] ?? '');
$fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$nombre_contacto = trim($_POST['nombre_contacto'] ?? '');
$telefono_contacto = trim($_POST['telefono_contacto'] ?? '');
$correo_contacto = trim($_POST['correo_contacto'] ?? '');
$hermano_en_grupo = isset($_POST['hermano_en_grupo']) ? 1 : 0;
$relacion_con_miembro = isset($_POST['relacion_con_miembro']) ? 1 : 0;
$familia_antiguo_scouter = isset($_POST['familia_antiguo_scouter']) ? 1 : 0;
$estuvo_en_grupo = isset($_POST['estuvo_en_grupo']) ? 1 : 0;
$explicacion_relacion = trim($_POST['explicacion_relacion'] ?? '');
$comentarios = trim($_POST['comentarios'] ?? '');


if (
	$nombre_nino === '' ||
	$apellido_ninio === '' ||
	$fecha_nacimiento === '' ||
	$nombre_contacto === '' ||
	$telefono_contacto === '' ||
	$correo_contacto === ''
) {
	http_response_code(400);
	exit('Faltan campos obligatorios.');
}

$fecha = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
if (!$fecha || $fecha->format('Y-m-d') !== $fecha_nacimiento) {
	http_response_code(400);
	exit('Fecha de nacimiento no válida.');
}

if (!filter_var($correo_contacto, FILTER_VALIDATE_EMAIL)) {
	http_response_code(400);
	exit('Correo electrónico no válido.');
}

$cortarTexto = static function ($texto, $longitud) {
	if (function_exists('mb_substr')) {
		return mb_substr($texto, 0, $longitud);
	}

	return substr($texto, 0, $longitud);
};

$nombre_nino = $cortarTexto($nombre_nino, 150);
$apellido_ninio = $cortarTexto($apellido_ninio, 150);
$nombre_contacto = $cortarTexto($nombre_contacto, 150);
$telefono_contacto = $cortarTexto($telefono_contacto, 20);
$correo_contacto = $cortarTexto($correo_contacto, 150);
$explicacion_relacion = $cortarTexto($explicacion_relacion, 65535);
$comentarios = $cortarTexto($comentarios, 65535);

$sql = 'INSERT INTO lista_espera (nombre_nino, apellidos_nino, fecha_nacimiento, nombre_contacto, telefono_contacto, correo_contacto, hermano_en_grupo, relacion_con_miembro, familia_antiguo_scouter, estuvo_en_grupo, explicacion_relacion, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
$stmt = $conexion->prepare($sql);

if (!$stmt) {
	http_response_code(500);
	exit('No se pudo preparar la solicitud.');
}

$stmt->bind_param(
	'ssssssiiiiss',
	$nombre_nino,
	$apellido_ninio,
	$fecha_nacimiento,
	$nombre_contacto,
	$telefono_contacto,
	$correo_contacto,
	$hermano_en_grupo,
	$relacion_con_miembro,
	$familia_antiguo_scouter,
	$estuvo_en_grupo,
	$explicacion_relacion,
	$comentarios
);

if (!$stmt->execute()) {
	http_response_code(500);
	exit('No se pudo guardar la solicitud: ' . $stmt->error);
}

$stmt->close();
header('Location: ../formListaEspera.php?estado=ok');
exit;
?>