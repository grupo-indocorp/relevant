<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GraficoClientesNuevos
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Builder $clientesQuery, ?Carbon $fechaSeleccionada = null, ?int $equipoId = null, ?int $ejecutivoId = null): \ArielMejiaDev\LarapexCharts\LineChart
    {
        $fechaFin = $fechaSeleccionada ? $fechaSeleccionada->copy()->endOfMonth()->toDateString() : Carbon::now()->toDateString();
        $fechaInicio = $fechaSeleccionada
            ? $fechaSeleccionada->copy()->subMonths(11)->startOfMonth()->toDateString()
            : Carbon::now()->subMonths(11)->startOfMonth()->toDateString();

        // SQL 100% raw para garantizar resultados exactos
        $sql = "SELECT COUNT(id) AS total, DATE_FORMAT(created_at, '%Y-%m') AS mes
                FROM clientes
                WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?";
        $params = [$fechaInicio, $fechaFin];

        if ($equipoId) {
            $sql .= " AND equipo_id = ?";
            $params[] = $equipoId;
        }

        if ($ejecutivoId) {
            $sql .= " AND user_id = ?";
            $params[] = $ejecutivoId;
        }

        $sql .= " GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY mes ASC";

        $results = DB::select($sql, $params);
        $countsPorMes = collect($results)->pluck('total', 'mes');

        $meses = [];
        $counts = [];

        $cursor = Carbon::parse($fechaInicio)->startOfMonth();
        $fin = Carbon::parse($fechaFin)->endOfMonth();
        while ($cursor->lte($fin)) {
            $key = $cursor->format('Y-m');
            $meses[] = $cursor->translatedFormat('M Y');
            $counts[] = (int) $countsPorMes->get($key, 0);
            $cursor->addMonth();
        }

        return $this->chart->lineChart()
            ->setTitle('Clientes Nuevos por Mes')
            ->setSubtitle('Registros en los últimos 12 meses')
            ->addData('Nuevos clientes', $counts)
            ->setXAxis($meses)
            ->setColors(['#008FFB'])
            ->setOptions([
                'stroke' => [
                    'curve' => 'smooth',
                    'width' => 3,
                ],
                'markers' => [
                    'size' => 4,
                ],
                'dataLabels' => [
                    'enabled' => false,
                ],
                'tooltip' => [
                    'y' => [
                        'formatter' => 'function(val) { return val + " clientes" }',
                    ],
                ],
            ]);
    }
}
