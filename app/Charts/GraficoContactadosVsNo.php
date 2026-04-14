<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Database\Eloquent\Builder;

class GraficoContactadosVsNo
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Builder $clientesQuery): \ArielMejiaDev\LarapexCharts\DonutChart
    {
        $contactados = $clientesQuery->clone()->where('contactabilidad', false)->count();
        $noContactados = $clientesQuery->clone()->where('contactabilidad', true)->count();
        $total = $contactados + $noContactados;

        $labels = [];
        $data = [];

        if ($contactados > 0) {
            $pct = $total > 0 ? round(($contactados / $total) * 100, 2) : 0;
            $labels[] = "Contactados ($contactados - $pct%)";
            $data[] = $contactados;
        }

        if ($noContactados > 0) {
            $pct = $total > 0 ? round(($noContactados / $total) * 100, 2) : 0;
            $labels[] = "No Contactados ($noContactados - $pct%)";
            $data[] = $noContactados;
        }

        return $this->chart->donutChart()
            ->setTitle('Contactados vs No Contactados')
            ->setSubtitle('Total: ' . $total . ' clientes')
            ->addData($data)
            ->setLabels($labels)
            ->setColors(['#00E396', '#FF4560'])
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
                                    'formatter' => 'function() { return ' . $total . ' }',
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
