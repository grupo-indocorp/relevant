<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GraficoActividadContacto
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

        // SQL 100% raw — gestiones únicas (1 cliente + 1 día = 1 gestión)
        $sql = "SELECT COUNT(DISTINCT c.cliente_id, DATE(c.created_at)) AS total,
                       DATE_FORMAT(c.created_at, '%Y-%m') AS mes
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

        $sql .= " GROUP BY DATE_FORMAT(c.created_at, '%Y-%m') ORDER BY mes ASC";

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
            ->setTitle('Actividad de Contacto por Mes')
            ->setSubtitle('Gestiones realizadas en los últimos 12 meses')
            ->addData('Gestiones', $counts)
            ->setXAxis($meses)
            ->setColors(['#00E396'])
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
                        'formatter' => 'function(val) { return val + " gestiones" }',
                    ],
                ],
            ]);
    }
}
