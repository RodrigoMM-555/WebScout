<!-- Calendario dinámico trimestre -->
<?php
/**
 * calendario.php — Genera el calendario trimestral
 * Muestra los eventos/avisos del trimestre actual agrupados por día,
 * coloreados por tipo de evento (sábado, campamento, etc.).
 * Usa IntlDateFormatter para fechas en español.
 */
// conexion_bd.php ya se incluye desde la página padre
// Solo lo incluimos si no existe $conexion aún
if (!isset($conexion)) {
    include_once __DIR__ . '/conexion_bd.php';
}
date_default_timezone_set("Europe/Madrid");
?>


<article class="calendario-trimestre">
<?php
    // Fecha actual
    $fechaActual = new DateTime();
    $mes = (int)$fechaActual->format("m");
    if ($mes < 8) {
        $rondaI = (int)$fechaActual->format("Y")-1;
    } else {
        $rondaI = (int)$fechaActual->format("Y");
    }
    if ($mes <= 12 && $mes >= 10) {
        $trimestreI = 1;
    } elseif ($mes >= 1 && $mes < 4) {
        $trimestreI = 2;
    } else {
        $trimestreI = 3;
    }
    $rondaF = $rondaI + 1;
    $trimestre = ceil($mes / 3);
    $mesInicio = ($trimestre - 1) * 3 + 1;
    $inicio = new DateTime($fechaActual->format("Y") . "-" . $mesInicio . "-01 00:00:00");
    $fin = clone $inicio;
    $fin->modify("+3 months");
    $fechaInicio = $inicio->format("Y-m-d H:i:s");
    $fechaFin = $fin->format("Y-m-d H:i:s");

    echo "<section class='ronda'>
        <h2>RONDA $rondaI/$rondaF</h2>
        <h1 style='color: darkorange;'>· Trimestre $trimestreI ·</h1>
        <ul>
            <h3> Grupo Scout Seeonee</h3>
            <p>Campanar, Valencia</p>
        </ul>
    </section>";

    // Obtener avisos del trimestre
    $sql = "SELECT * FROM avisos 
            WHERE fecha_hora_inicio >= ? 
            AND fecha_hora_inicio < ?
            ORDER BY fecha_hora_inicio";
    $stmtCal = $conexion->prepare($sql);
    $stmtCal->bind_param("ss", $fechaInicio, $fechaFin);
    $stmtCal->execute();
    $resultado = $stmtCal->get_result();

    $avisosPorDia = [];
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $fechaAvisoI = new DateTime($fila['fecha_hora_inicio']);
            $fechaAvisoF = !empty($fila['fecha_hora_fin']) ? new DateTime($fila['fecha_hora_fin']) : $fechaAvisoI;
            $interval = $fechaAvisoI->diff($fechaAvisoF);
            $diasEvento = $interval->days;
            for ($i = 0; $i <= $diasEvento; $i++) {
                $diaEvento = clone $fechaAvisoI;
                $diaEvento->modify("+{$i} days");
                $diaClave = $diaEvento->format("Y-m-d");
                $avisosPorDia[$diaClave][] = $fila;
            }
        }
    }

    // Generar calendario visual de los tres meses
    echo "<div class='calendario-meses-grid'>";
    for ($m = 0; $m < 3; $m++) {
        $mesActual = (int)$inicio->format("m") + $m;
        $anioActual = (int)$inicio->format("Y");
        // Ajustar año si el mes pasa de diciembre
        if ($mesActual > 12) {
            $mesActual -= 12;
            $anioActual++;
        }
        setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');
        $nombreMes = ucfirst(strftime("%B", mktime(0,0,0,$mesActual,1,$anioActual)));
        $mesesI18n = [
            1 => '<span data-i18n="enero">Enero</span>',
            2 => '<span data-i18n="febrero">Febrero</span>',
            3 => '<span data-i18n="marzo">Marzo</span>',
            4 => '<span data-i18n="abril">Abril</span>',
            5 => '<span data-i18n="mayo">Mayo</span>',
            6 => '<span data-i18n="junio">Junio</span>',
            7 => '<span data-i18n="julio">Julio</span>',
            8 => '<span data-i18n="agosto">Agosto</span>',
            9 => '<span data-i18n="septiembre">Septiembre</span>',
            10 => '<span data-i18n="octubre">Octubre</span>',
            11 => '<span data-i18n="noviembre">Noviembre</span>',
            12 => '<span data-i18n="diciembre">Diciembre</span>'
        ];
        $primerDiaMes = new DateTime("$anioActual-$mesActual-01");
        $ultimoDiaMes = clone $primerDiaMes;
        $ultimoDiaMes->modify("last day of this month");
        $diasMes = (int)$ultimoDiaMes->format("d");
        $diaSemanaInicio = (int)$primerDiaMes->format("N"); // 1=Lunes

        echo "<section class='calendario-mes'>";
        echo "<h2>" . $mesesI18n[$mesActual] . " $anioActual</h2>";
        echo "<div class='calendario-grid'>";
        // Cabecera días
        $diasSemana = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
        $diasSemanaI18n = [
            'L' => '<span data-i18n="lunes_corto">L</span>',
            'M' => '<span data-i18n="martes_corto">M</span>',
            'X' => '<span data-i18n="miercoles_corto">X</span>',
            'J' => '<span data-i18n="jueves_corto">J</span>',
            'V' => '<span data-i18n="viernes_corto">V</span>',
            'S' => '<span data-i18n="sabado_corto">S</span>',
            'D' => '<span data-i18n="domingo_corto">D</span>'
        ];
        foreach ($diasSemana as $d) {
            echo "<div class='calendario-dia-header'>" . $diasSemanaI18n[$d] . "</div>";
        }
        // Espacios vacíos antes del primer día
        for ($i = 1; $i < $diaSemanaInicio; $i++) {
            echo "<div class='calendario-dia-vacio'></div>";
        }
        // Días del mes
        for ($dia = 1; $dia <= $diasMes; $dia++) {
            $fechaDia = sprintf("%04d-%02d-%02d", $anioActual, $mesActual, $dia);
            echo "<div class='calendario-dia' data-fecha='$fechaDia'>";
            echo "<div class='calendario-dia-num'>$dia</div>";
            if (isset($avisosPorDia[$fechaDia])) {
                foreach ($avisosPorDia[$fechaDia] as $aviso) {
                    $tipo = htmlspecialchars($aviso['tipo']);
                    $titulo = htmlspecialchars($aviso['titulo']);
                    echo "<div class='titulo-evento tipo-$tipo'>" . $titulo . "</div>";
                }
            }
            echo "</div>";
        }
        echo "</div>";
        echo "</section>";
    }
    echo "</div>";

?>
</article>
