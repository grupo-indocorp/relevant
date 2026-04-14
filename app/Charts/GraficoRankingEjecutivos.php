<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GraficoRankingEjecutivos
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(Carbon $mesReferencia, ?int $equipoId = null, ?int $ejecutivoId = null, ?string $fechaCorte = null): \ArielMejiaDev\LarapexCharts\HorizontalBar
    {
        $fechaInicio = $mesReferencia->copy()->startOfMonth()->toDateString();
        $fechaFinMes = $mesReferencia->copy()->endOfMonth()->toDateString();
        $fechaFin = $fechaCorte ? min($fechaFinMes, $fechaCorte) : $fechaFinMes;

        $sql = "SELECT u.name AS ejecutivo,
                       COUNT(DISTINCT c.cliente_id, DATE(c.created_at)) AS total
                FROM comentarios c
                INNER JOIN clientes cl ON cl.id = c.cliente_id
                INNER JOIN users u ON u.id = c.user_id
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

        $sql .= " GROUP BY c.user_id, u.name ORDER BY total DESC";

        $results = DB::select($sql, $params);

        $nombres = [];
        $counts = [];

        foreach ($results as $row) {
            $nombres[] = $row->ejecutivo;
            $counts[] = (int) $row->total;
        }

        $colores = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8', '#2B908F', '#F9A3A4', '#90EE7E', '#69D2E7', '#F86624', '#A5978B', '#8D5B4C'];

        return $this->chart->horizontalBarChart()
            ->setTitle('Ranking de Gestiones por Ejecutivo')
            ->setSubtitle($mesReferencia->translatedFormat('F Y'))
            ->addData('Gestiones', $counts)
            ->setXAxis($nombres)
            ->setColors($colores)
            ->setOptions([
                'plotOptions' => [
                    'bar' => [
                        'distributed' => true,
                        'borderRadius' => 4,
                        'barHeight' => '70%',
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
                        'formatter' => 'function(val) { return val + " gestiones" }',
                    ],
                ],
            ]);
    }
}
