<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GraficoDeAnillo
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Builder $clientesQuery): \ArielMejiaDev\LarapexCharts\DonutChart
    {
        // Paso 1: contar clientes por etapa_id usando clientesQuery (ya filtrado por equipo/ejecutivo/fecha)
        $countsPorEtapaId = $clientesQuery->clone()
            ->toBase()
            ->select(DB::raw('etapa_id, count(*) as total'))
            ->groupBy('etapa_id')
            ->get()
            ->pluck('total', 'etapa_id');

        // Paso 2: para cada etapa_id con clientes, obtener nombre y color
        // Agrupamos por nombre para fusionar duplicados (mismo nombre, distinto id)
        // El color se toma de la etapa con estado=1
        $etapasInfo = DB::table('etapas')
            ->whereIn('id', $countsPorEtapaId->keys())
            ->select('id', 'nombre', 'color', 'estado')
            ->orderBy('id')
            ->get();

        // Agrupar por nombre: sumar totales y elegir color de estado=1
        $agrupado = [];
        foreach ($etapasInfo as $etapa) {
            $nombre = $etapa->nombre;
            $total  = (int) $countsPorEtapaId->get($etapa->id, 0);
            if (!isset($agrupado[$nombre])) {
                $agrupado[$nombre] = ['total' => 0, 'color' => '#cccccc', 'min_id' => $etapa->id];
            }
            $agrupado[$nombre]['total'] += $total;
            // Preferir color de la etapa con estado=1
            if ($etapa->estado == 1) {
                $agrupado[$nombre]['color'] = $etapa->color;
            }
            $agrupado[$nombre]['min_id'] = min($agrupado[$nombre]['min_id'], $etapa->id);
        }

        // Ordenar por min_id para mantener orden consistente
        uasort($agrupado, fn($a, $b) => $a['min_id'] <=> $b['min_id']);

        $etapasNombres = [];
        $etapasCounts = [];
        $etapasColores = [];

        foreach ($agrupado as $nombre => $data) {
            $etapasNombres[] = $nombre;
            $etapasCounts[]  = $data['total'];
            $etapasColores[] = $data['color'];
        }

        $totalClientes = array_sum($etapasCounts);

        // Crear etiquetas con porcentajes
        $chartLabels = [];
        foreach ($etapasNombres as $index => $nombre) {
            $porcentaje = $totalClientes > 0
                ? round(($etapasCounts[$index] / $totalClientes) * 100, 2)
                : 0;

            $chartLabels[] = "$nombre ({$etapasCounts[$index]} - $porcentaje%)";
        }

        return $this->chart->donutChart()
            ->setTitle('Distribución de Clientes por Etapas')
            ->setSubtitle('Total de clientes: '.$totalClientes)
            ->addData($etapasCounts)
            ->setLabels($chartLabels)
            ->setColors($etapasColores)
            ->setOptions([
                'dataLabels' => [
                    'enabled' => true,
                    'formatter' => 'function(val) { return Math.round(val) + "%" }',
                ],
                'plotOptions' => [
                    'pie' => [
                        'donut' => [
                            'labels' => [
                                'show' => true,
                                'total' => [
                                    'show' => true,
                                    'label' => 'Total',
                                    'formatter' => 'function() { return '.$totalClientes.' }',
                                ],
                            ],
                        ],
                    ],
                ],
                'tooltip' => [
                    'y' => [
                        'formatter' => 'function(val, opts) { 
                            return val + " clientes (" + Math.round(opts.percent) + "%)"
                        }',
                    ],
                ],
                'legend' => [
                    'position' => 'bottom',
                    'formatter' => 'function(seriesName, opts) { 
                        return seriesName + ": " + opts.w.globals.series[opts.seriesIndex] 
                    }',
                ],
            ]);
    }
}
