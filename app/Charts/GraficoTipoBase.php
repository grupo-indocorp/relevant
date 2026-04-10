<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Database\Eloquent\Builder;

class GraficoTipoBase
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Builder $clientesQuery): \ArielMejiaDev\LarapexCharts\DonutChart
    {
        $counts = $clientesQuery->clone()
            ->toBase()
            ->select(\Illuminate\Support\Facades\DB::raw('tipobase, count(*) as total'))
            ->groupBy('tipobase')
            ->get()
            ->pluck('total', 'tipobase');

        $equipoVentas = (int) $counts->get('Equipo de Ventas', 0);
        $baseEmpresa  = (int) $counts->get('Base de la Empresa', 0);
        $total = $equipoVentas + $baseEmpresa;
        $pctVentas = $total > 0 ? round(($equipoVentas / $total) * 100, 2) : 0;
        $pctEmpresa = $total > 0 ? round(($baseEmpresa / $total) * 100, 2) : 0;

        return $this->chart->donutChart()
            ->setTitle('Distribución Tipo Base')
            ->setSubtitle('Origen de los clientes')
            ->addData([$equipoVentas, $baseEmpresa])
            ->setLabels(["Equipo de Ventas ($equipoVentas - $pctVentas%)", "Base de la Empresa ($baseEmpresa - $pctEmpresa%)"])
            ->setColors(['#775DD0', '#00E396'])
            ->setOptions([
                'legend' => ['position' => 'bottom'],
                'dataLabels' => ['enabled' => true],
                'tooltip' => [
                    'y' => ['formatter' => 'function(val) { return val + " clientes" }'],
                ],
                'plotOptions' => [
                    'pie' => [
                        'donut' => ['size' => '65%'],
                    ],
                ],
            ]);
    }
}
