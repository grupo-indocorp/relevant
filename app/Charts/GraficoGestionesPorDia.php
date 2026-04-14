<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GraficoGestionesPorDia
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Carbon $mesReferencia, ?int $equipoId = null, ?int $ejecutivoId = null, ?string $fechaCorte = null): \ArielMejiaDev\LarapexCharts\LineChart
    {
        $fechaInicio = $mesReferencia->copy()->startOfMonth()->toDateString();
        $fechaFinMes = $mesReferencia->copy()->endOfMonth()->toDateString();
        $fechaFin = $fechaCorte ? min($fechaFinMes, $fechaCorte) : $fechaFinMes;

        $sql = "SELECT COUNT(DISTINCT c.cliente_id, DATE(c.created_at)) AS total,
                       DATE(c.created_at) AS dia
                FROM comentarios c
                INNER JOIN clientes cl ON cl.id = c.cliente_id
                WHERE DATE(c.created_at) >= ? AND DATE(c.created_at) <= ?";
        $params = [$fechaInicio, $fechaFin];

        if ($equipoId) {
            $sql .= " AND c.user_id IN (SELECT user_id FROM equipo_user WHERE equipo_id = ?)";
            $params[] = $equipoId;
        }

        if ($ejecutivoId) {
            $sql .= " AND c.user_id = ?";
            $params[] = $ejecutivoId;
        }

        $sql .= " GROUP BY DATE(c.created_at) ORDER BY dia ASC";

        $results = DB::select($sql, $params);
        $countsPorDia = collect($results)->pluck('total', 'dia');

        $dias = [];
        $counts = [];

        $cursor = $mesReferencia->copy()->startOfMonth();
        $fin = Carbon::parse($fechaFin);
        while ($cursor->lte($fin)) {
            $key = $cursor->toDateString();
            $dias[] = $cursor->format('d');
            $counts[] = (int) $countsPorDia->get($key, 0);
            $cursor->addDay();
        }

        return $this->chart->lineChart()
            ->setTitle('Total Gestiones por Día')
            ->setSubtitle($mesReferencia->translatedFormat('F Y'))
            ->addData('Gestiones', $counts)
            ->setXAxis($dias)
            ->setColors(['#775DD0'])
            ->setOptions([
                'stroke' => [
                    'curve' => 'smooth',
                    'width' => 3,
                ],
                'markers' => [
                    'size' => 4,
                ],
                'dataLabels' => [
                    'enabled' => true,
                    'style' => [
                        'fontSize' => '10px',
                    ],
                ],
                'tooltip' => [
                    'y' => [
                        'formatter' => 'function(val) { return val + " gestiones" }',
                    ],
                ],
            ]);
    }
}
