<?php

namespace App\Charts;

use App\Models\Etapa;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Database\Eloquent\Builder;

class GraficoFunnelEtapas
{
    protected $chart;
    public array $tableData = [];

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Builder $clientesQuery): \ArielMejiaDev\LarapexCharts\BarChart
    {
        $etapasPermitidas = [
            'PENDIENTE 0%',
            'INTERESADO 25%',
            'PROSPECTO 50%',
            'OPORTUNIDAD 75%',
            'GANADO 100%',
            'PERDIDO',
            'NO GESTIONABLE',
            'STANDBY',
            'PENDIENTE POTENCIAL 0%',
            'CIERRE 90%',
            'NO INTERESADO 0%',
        ];
        $etapas = Etapa::whereIn('nombre', $etapasPermitidas)->where('estado', 1)->orderBy('id')->get();

        // Una sola query con GROUP BY en vez de N queries
        $countsPorEtapa = $clientesQuery->clone()
            ->toBase()
            ->select(\Illuminate\Support\Facades\DB::raw('etapa_id, count(*) as total'))
            ->groupBy('etapa_id')
            ->get()
            ->pluck('total', 'etapa_id');

        $nombres = [];
        $counts = [];
        $colores = [];

        foreach ($etapas as $etapa) {
            $nombres[] = $etapa->nombre;
            $counts[] = (int) $countsPorEtapa->get($etapa->id, 0);
            $colores[] = $etapa->color;
        }

        $this->tableData = array_combine($nombres, $counts);

        return $this->chart->barChart()
            ->setTitle('Funnel de Conversión por Etapas')
            ->setSubtitle('Clientes en cada etapa del pipeline')
            ->addData('Clientes', $counts)
            ->setXAxis($nombres)
            ->setColors($colores)
            ->setOptions([
                'plotOptions' => [
                    'bar' => [
                        'distributed' => true,
                        'borderRadius' => 4,
                        'columnWidth' => '60%',
                    ],
                ],
                'dataLabels' => [
                    'enabled' => true,
                ],
                'legend' => [
                    'show' => false,
                ],
                'tooltip' => [
                    'y' => [
                        'formatter' => 'function(val) { return val + " clientes" }',
                    ],
                ],
            ]);
    }
}
