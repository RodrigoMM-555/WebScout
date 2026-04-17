
<?php
// Pantalla grid de familias: padres e hijos con colores de sección
include_once __DIR__ . '/conexion_bd.php';

// Obtener todas las familias (usuarios) y sus hijos (educandos)

// 1. Obtener todos los usuarios (padres)
$sql_usuarios = "SELECT id, nombre, apellidos, nombre2, apellidos2 FROM usuarios ORDER BY id";
$res_usuarios = $conexion->query($sql_usuarios);
$familias = [];
if ($res_usuarios) {
	while ($u = $res_usuarios->fetch_assoc()) {
		$id = (int)$u['id'];
		$familias[$id] = [
			'padres' => [trim($u['nombre'] . ' ' . $u['apellidos'])],
			'hijos' => []
		];
		if (!empty($u['nombre2']) || !empty($u['apellidos2'])) {
			$familias[$id]['padres'][] = trim($u['nombre2'] . ' ' . $u['apellidos2']);
		}
	}
}

// 2. Obtener todos los educandos (hijos) y asignar a su familia
$sql_hijos = "SELECT id, nombre, apellidos, seccion, anio, id_usuario FROM educandos ORDER BY id_usuario, seccion, nombre";
$res_hijos = $conexion->query($sql_hijos);
if ($res_hijos) {
	while ($h = $res_hijos->fetch_assoc()) {
		$id_usuario = (int)$h['id_usuario'];
		if (isset($familias[$id_usuario])) {
			$familias[$id_usuario]['hijos'][] = [
				'nombre' => $h['nombre'],
				'apellidos' => $h['apellidos'],
				'seccion' => strtolower($h['seccion'] ?? ''),
				'anio' => $h['anio']
			];
		}
	}
}

$claseSeccion = [
	'colonia' => 'seccion-colonia',
	'manada' => 'seccion-manada',
	'tropa' => 'seccion-tropa',
	'posta' => 'seccion-posta',
	'rutas' => 'seccion-rutas',
];

// Detectar si se accede directamente o por include
$esDirecto = (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']));
if ($esDirecto) {
	?><!DOCTYPE html>
	<html lang="es">
	<head>
		<meta charset="UTF-8">
		<title>Familias - WebScout</title>
		<link rel="stylesheet" href="/admin/css/estilo.css">
	</head>
	<body>
	<h1 style="text-align:center;margin-top:24px;margin-bottom:10px;">Familias</h1>
<?php }

// Renderizar solo el grid (para include o directo)
?>
<?php
if (empty($familias)) {
	echo '<div style="text-align:center;margin:32px 0 24px 0;font-size:1.2em;color:#b71c1c;font-weight:500;">No se encontraron familias en la base de datos.</div>';
	if (!$res) {
		echo '<div style="color:#b71c1c;text-align:center;font-size:.98em;">Error SQL: ' . htmlspecialchars($conexion->error) . '</div>';
	}
} else {
	echo '<div class="familias-grid">';
	foreach ($familias as $familia) {
		echo '<div class="familia-card">';
		echo '<div class="familia-padres">';
		$numPadres = count($familia['padres']);
		foreach ($familia['padres'] as $i => $padre) {
			echo '<span>' . htmlspecialchars($padre) . '</span>';
			if ($i < $numPadres - 1) {
				echo '<span class="padre-sep" title="Ambos progenitores">/</span>';
			}
		}
		echo '</div>';
		echo '<div class="familia-hijos">';
		if (count($familia['hijos'])) {
			foreach ($familia['hijos'] as $hijo) {
				$clase = isset($claseSeccion[$hijo['seccion']]) ? $claseSeccion[$hijo['seccion']] : '';
				echo '<div class="hijo-caja ' . $clase . '">';
				echo htmlspecialchars($hijo['nombre'] . ' ' . $hijo['apellidos']) . '<br>';
				echo '<span>' . ucfirst($hijo['anio'] ? (int)$hijo['anio'] : '') . '</span>';
				echo '</div>';
			}
		} else {
			echo '<span class="sin-hijos">Sin hijos registrados</span>';
		}
		echo '</div>';
		echo '</div>';
	}
	echo '</div>';
}
if ($esDirecto) { echo '</body></html>'; }
