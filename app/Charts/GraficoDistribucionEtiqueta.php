<?php

namespace App\Charts;

use App\Models\Etiqueta;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Database\Eloquent\Builder;

class GraficoDistribucionEtiqueta
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Builder $clientesQuery): \ArielMejiaDev\LarapexCharts\DonutChart
    {
        $etiquetas = Etiqueta::orderBy('id')->get()->keyBy('id');
        $colores = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8'];

        // Una sola query con GROUP BY
        $countsPorEtiqueta = $clientesQuery->clone()
            ->toBase()
            ->select(\Illuminate\Support\Facades\DB::raw('etiqueta_id, count(*) as total'))
            ->groupBy('etiqueta_id')
            ->get()
            ->pluck('total', 'etiqueta_id');

        $nombres = [];
        $counts = [];

        foreach ($etiquetas as $id => $etiqueta) {
            $count = $countsPorEtiqueta->get($id, 0);
            if ($count > 0) {
                $nombres[] = $etiqueta->nombre;
                $counts[] = $count;
            }
        }

        $sinEtiqueta = $countsPorEtiqueta->get('', 0) + $countsPorEtiqueta->get(null, 0);
        if ($sinEtiqueta > 0) {
            $nombres[] = 'Sin etiqueta';
            $counts[] = $sinEtiqueta;
        }

        $totalClientes = array_sum($counts);

        $chartLabels = [];
        foreach ($nombres as $index => $nombre) {
            $porcentaje = $totalClientes > 0
                ? round(($counts[$index] / $totalClientes) * 100, 2)
                : 0;
            $chartLabels[] = "$nombre ({$counts[$index]} - $porcentaje%)";
        }

        return $this->chart->donutChart()
            ->setTitle('Distribución por Etiqueta')
            ->setSubtitle('Total de clientes: ' . $totalClientes)
            ->addData($counts)
            ->setLabels($chartLabels)
            ->setColors(array_slice($colores, 0, count($counts)))
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
                                    'formatter' => 'function() { return ' . $totalClientes . ' }',
                                ],
                            ],
                        ],
                    ],
                ],
                'legend' => [
                    'position' => 'bottom',
                ],
            ]);
    }
}
