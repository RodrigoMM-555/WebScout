<!-- Calendario dinámico trimestre -->

<?php
include("conexion_bd.php");
date_default_timezone_set("Europe/Madrid");
?>

<article class="calendario-trimestre">
    <?php
        // Fecha actual
        $fechaActual = new DateTime();
        // Mes actual
        $mes = (int)$fechaActual->format("m");
        // Calculamos la ronda actual
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
        // Calculamos trimestre (1 a 4)
        $trimestre = ceil($mes / 3);
        // Mes de inicio del trimestre
        $mesInicio = ($trimestre - 1) * 3 + 1;
        // Fecha inicio trimestre
        $inicio = new DateTime($fechaActual->format("Y") . "-" . $mesInicio . "-01 00:00:00");
        // Fecha fin real = inicio + 3 meses
        $fin = clone $inicio;
        $fin->modify("+3 months");

        $fechaInicio = $inicio->format("Y-m-d H:i:s");
        $fechaFin = $fin->format("Y-m-d H:i:s");

        // Consulta correcta para DATETIME
        $sql = "SELECT * FROM avisos 
                WHERE fecha_hora >= '$fechaInicio' 
                AND fecha_hora < '$fechaFin'
                ORDER BY fecha_hora";

        $resultado = $conexion->query($sql);

        echo"
        <section class='ronda'>
        <h2>RONDA ".$rondaI."/".$rondaF."</h2>
        <h1 style='color: darkorange;'>· Trimestre ".$trimestreI." ·</h1>
        <ul>
            <h3> Grupo Scout Seeonee</h3>
            <p>Campanar, Valencia</p>
        </ul>
        </section>
            <section>";

        if ($resultado && $resultado->num_rows > 0) {

            $eventosPorDia = [];

            while($fila = $resultado->fetch_assoc()) {
                $fechaAviso = new DateTime($fila['fecha_hora']);
                $diaClave = $fechaAviso->format("Y-m-d"); // clave única por día

                // Creamos el formatter para mostrar la fecha en texto
                $formatter = new IntlDateFormatter(
                    'es_ES',
                    dateType: IntlDateFormatter::FULL,
                    timeType: IntlDateFormatter::NONE,
                    timezone: 'Europe/Madrid',
                    calendar: IntlDateFormatter::GREGORIAN,
                    pattern: "EEEE d 'de' MMMM"
                );

                $fechaTexto = ucfirst($formatter->format($fechaAviso));

                // Guardamos cada evento dentro del día correspondiente
                $eventosPorDia[$diaClave]['fechaTexto'] = $fechaTexto;
                $eventosPorDia[$diaClave]['eventos'][] = $fila['titulo'];
            }

            foreach($eventosPorDia as $dia => $info) {
                echo "<div class='evento'>";
                echo "<div class='fecha'>" . $info['fechaTexto'] . "</div>";

                foreach($info['eventos'] as $titulo) {
                    echo "<div class='titulo-evento'>" . htmlspecialchars($titulo) . "</div>";
                }

                echo "</div>";
            }

        } else {
            echo "<p>No hay avisos en este trimestre.</p>";
        }
    ?>

    </section>
</article>