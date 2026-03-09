(function () {
    // -------------------------------------------------------------------------
    // donuts.js
    // -------------------------------------------------------------------------
    // Este archivo lee los datos preparados en PHP (window.webScoutDonutData)
    // y dibuja un donut por cada definición incluida en payload.charts.
    //
    // Flujo general:
    // 1) Comprobar que Chart.js está cargado.
    // 2) Comprobar que existe el payload inyectado desde PHP.
    // 3) Recorrer cada gráfico configurado en payload.charts.
    // 4) Buscar su <canvas> por id.
    // 5) Crear instancia new Chart(...) tipo doughnut.
    // -------------------------------------------------------------------------

    // Seguridad básica: si el CDN de Chart.js falla, salimos sin romper la página.
    if (typeof window.Chart === 'undefined') {
        return;
    }

    // Datos generados en PHP y serializados con json_encode.
    // Estructura esperada:
    // {
    //   colores: { colonia: '#...', manada: '#...', ... },
    //   charts: [
    //     {
    //       id: 'donut-no',
    //       titulo: 'No asisten',
    //       labels: ['Colonia', 'Manada', ...],
    //       keys: ['colonia', 'manada', ...],
    //       values: [3, 5, ...],
    //       total: 12
    //     }
    //   ]
    // }
    var payload = window.webScoutDonutData;

    // Si el payload no existe o no trae gráficos, no hacemos nada.
    // Esto evita errores JS en escenarios de vista parcial o carga incompleta.
    if (!payload || !Array.isArray(payload.charts)) {
        return;
    }

    // Recorremos cada definición para pintar un donut independiente.
    payload.charts.forEach(function (chartDef) {
        // El id viene desde PHP y debe coincidir con <canvas id='...'>.
        var canvas = document.getElementById(chartDef.id);

        // Si no existe el canvas, saltamos ese gráfico y seguimos con los demás.
        if (!canvas) {
            return;
        }

        // Mapeamos cada clave de sección al color configurado en PHP.
        // Si aparece una sección no prevista, usamos un color fallback suave.
        var colors = (chartDef.keys || []).map(function (key) {
            return payload.colores[key] || '#d8c5ec';
        });

        // Creamos el donut con la configuración de Chart.js.
        new Chart(canvas.getContext('2d'), {
            // doughnut = gráfico circular con hueco central.
            type: 'doughnut',
            data: {
                // Etiquetas que salen en leyenda/tooltip.
                labels: chartDef.labels,
                datasets: [{
                    // Valores por sección para este donut concreto.
                    data: chartDef.values,
                    // Color por porción.
                    backgroundColor: colors,
                    // Borde blanco para separar visualmente segmentos.
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                // Adaptación automática al contenedor.
                responsive: true,
                // Usamos alto fijo del contenedor CSS (.donut-wrap).
                maintainAspectRatio: false,
                // Tamaño del hueco central (donde se ve el total con CSS).
                cutout: '62%',
                plugins: {
                    legend: {
                        // Leyenda debajo para mantener consistencia entre tarjetas.
                        position: 'bottom',
                        labels: {
                            // Tamaño del cuadradito de color de la leyenda.
                            boxWidth: 14,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            // Texto personalizado del tooltip.
                            // Ejemplo: "Manada: 5"
                            label: function (context) {
                                var valor = context.raw || 0;
                                return context.label + ': ' + valor;
                            }
                        }
                    }
                }
            }
        });
    });
})();
