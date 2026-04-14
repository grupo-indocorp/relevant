<?php

namespace App\Charts;

use App\Models\Contactabilidad;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Support\Facades\DB;

class GraficoCanalesContacto
{
    protected $chart;
    public array $tableData = [];

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(?string $fechaInicio = null, ?string $fechaFin = null, ?int $equipoId = null, ?int $ejecutivoId = null): \ArielMejiaDev\LarapexCharts\BarChart
    {
        $canales = Contactabilidad::where('nombre', '!=', 'No Contactado')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        // Gestiones únicas: 1 gestión = 1 cliente + 1 día por canal
        $sql = "SELECT c.contactabilidad_id, COUNT(DISTINCT c.cliente_id, DATE(c.created_at)) AS total
                FROM comentarios c
                INNER JOIN clientes cl ON cl.id = c.cliente_id
                WHERE c.contactabilidad_id IS NOT NULL AND c.contactabilidad_id != 1";
        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND DATE(c.created_at) BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        if ($equipoId) {
            $sql .= " AND c.user_id IN (SELECT user_id FROM equipo_user WHERE equipo_id = ?)";
            $params[] = $equipoId;
        }

        if ($ejecutivoId) {
            $sql .= " AND c.user_id = ?";
            $params[] = $ejecutivoId;
        }

        $sql .= " GROUP BY c.contactabilidad_id";
        $countsPorCanal = collect(DB::select($sql, $params))->pluck('total', 'contactabilidad_id');

        $nombres = [];
        $counts = [];
        $colores = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];

        foreach ($canales as $id => $canal) {
            $nombres[] = $canal->nombre;
            $counts[] = (int) $countsPorCanal->get($id, 0);
        }

        $this->tableData = array_combine($nombres, $counts);

        return $this->chart->barChart()
            ->setTitle('Distribución por Canal de Contacto')
            ->setSubtitle('Gestiones únicas por tipo de canal')
            ->addData('Gestiones', $counts)
            ->setXAxis($nombres)
            ->setColors($colores)
            ->setOptions([
                'plotOptions' => [
                    'bar' => [
                        'distributed' => true,
                        'borderRadius' => 4,
                        'columnWidth' => '55%',
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
