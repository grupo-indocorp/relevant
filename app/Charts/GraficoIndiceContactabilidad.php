<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Database\Eloquent\Builder;

class GraficoIndiceContactabilidad
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Builder $clientesQuery): \ArielMejiaDev\LarapexCharts\RadialChart
    {
        $total = $clientesQuery->clone()->count();
        $contactados = $clientesQuery->clone()->where('contactabilidad', false)->count();
        $indice = $total > 0 ? round(($contactados / $total) * 100, 2) : 0;

        return $this->chart->radialChart()
            ->setTitle('Índice de Contactabilidad')
            ->setSubtitle("$contactados de $total clientes contactados")
            ->addData([$indice])
            ->setLabels(['Contactados'])
            ->setColors(['#008FFB'])
            ->setOptions([
                'plotOptions' => [
                    'radialBar' => [
                        'startAngle' => -90,
                        'endAngle' => 90,
                        'hollow' => [
                            'size' => '60%',
                        ],
                        'dataLabels' => [
                            'name' => [
                                'show' => true,
                                'fontSize' => '16px',
                                'offsetY' => 20,
                            ],
                            'value' => [
                                'show' => true,
                                'fontSize' => '24px',
                                'formatter' => 'function(val) { return val + "%" }',
                            ],
                        ],
                    ],
                ],
            ]);
    }
}
