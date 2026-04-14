<?php

namespace App\Http\Controllers;

use App\Charts\GraficoActividadContacto;
use App\Charts\GraficoCanalesContacto;
use App\Charts\GraficoClientesNuevos;
use App\Charts\GraficoContactadosVsNo;
use App\Charts\GraficoDeAnillo;
use App\Charts\GraficoDeConversion;
use App\Charts\GraficoDistribucionEtiqueta;
use App\Charts\GraficoFunnelEtapas;
use App\Charts\GraficoGestionesPorDia;
use App\Charts\GraficoRankingEjecutivos;
use App\Charts\GraficoTipoBase;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Etapa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        GraficoDeAnillo $chartBuilder,
        GraficoDeConversion $conversionChartBuilder,
        GraficoFunnelEtapas $funnelChartBuilder,
        GraficoClientesNuevos $clientesNuevosChartBuilder,
        GraficoDistribucionEtiqueta $etiquetaChartBuilder,
        GraficoContactadosVsNo $contactadosChartBuilder,
        GraficoCanalesContacto $canalesContactoChartBuilder,
        GraficoActividadContacto $actividadContactoChartBuilder,
        GraficoGestionesPorDia $gestionesPorDiaChartBuilder,
        GraficoRankingEjecutivos $rankingEjecutivosChartBuilder,
        GraficoTipoBase $tipoBaseChartBuilder
    )
    {
        // Validar parámetros
        $validator = Validator::make($request->all(), [
            'equipo' => 'nullable|integer|exists:equipos,id',
            'ejecutivo' => 'nullable|integer|exists:users,id',
            'fecha' => 'nullable|date_format:m/Y', // Validar el formato de la fecha
        ]);

        if ($validator->fails()) {
            return redirect()->route('dashboard')->withErrors($validator);
        }

        // Obtener parámetros
        $equipoSeleccionado = $request->input('equipo');
        $ejecutivoSeleccionado = $request->input('ejecutivo');

        // Validar coherencia equipo-ejecutivo
        if ($equipoSeleccionado && $ejecutivoSeleccionado) {
            $ejecutivoValido = User::where('id', $ejecutivoSeleccionado)
                ->whereHas('equipos', fn ($q) => $q->where('equipo_id', $equipoSeleccionado))
                ->exists();

            if (! $ejecutivoValido) {
                $ejecutivoSeleccionado = null;
                $request->merge(['ejecutivo' => null]);
            }
        }

        // Obtener y parsear fecha
        $fechaSeleccionada = null;
        if ($request->filled('fecha')) {
            $fechaSeleccionada = Carbon::createFromFormat('m/Y', $request->fecha)->startOfMonth();
        }

        // Obtener datos base
        $equipos = Equipo::all();

        // Obtener ejecutivos según equipo
        $ejecutivos = $equipoSeleccionado
            ? User::whereHas('equipos', fn ($q) => $q->where('equipo_id', $equipoSeleccionado))->get()
            : collect();

        // Query principal (con filtro de fecha) — para gráficos de estado puntual
        $clientesQuery = Cliente::query()
            ->when($equipoSeleccionado, fn ($q) => $q->where('equipo_id', $equipoSeleccionado))
            ->when($ejecutivoSeleccionado, fn ($q) => $q->where('user_id', $ejecutivoSeleccionado))
            ->when($fechaSeleccionada, fn ($q) => $q->whereRaw('DATE(created_at) BETWEEN ? AND ?', [
                $fechaSeleccionada->copy()->startOfMonth()->toDateString(),
                $fechaSeleccionada->copy()->endOfMonth()->toDateString(),
            ]));

        // Query sin filtro de fecha (solo equipo/ejecutivo) — para gráficos temporales de 12 meses
        $clientesQueryTemporal = Cliente::query()
            ->when($equipoSeleccionado, fn ($q) => $q->where('equipo_id', $equipoSeleccionado))
            ->when($ejecutivoSeleccionado, fn ($q) => $q->where('user_id', $ejecutivoSeleccionado));

        // Calcular métricas
        $totalClientes = $clientesQuery->count();
        $etapaCinco = Etapa::findOrFail(5);
        $clientesEnEtapaCinco = $clientesQuery->clone()->where('etapa_id', $etapaCinco->id)->count();
        $convertibilidad = $totalClientes > 0 ? round(($clientesEnEtapaCinco / $totalClientes) * 100, 2) : 0;

        // Gráficos de estado puntual (usan clientesQuery con filtro de fecha)
        $tipoBaseChart = $tipoBaseChartBuilder->build($clientesQuery->clone());
        $chart = $chartBuilder->build($clientesQuery->clone());
        $conversionChart = $conversionChartBuilder->build($clientesQuery->clone());
        $funnelChart = $funnelChartBuilder->build($clientesQuery->clone());
        $etiquetaChart = $etiquetaChartBuilder->build($clientesQuery->clone());
        $contactadosChart = $contactadosChartBuilder->build($clientesQuery->clone());
        $canalesContactoChart = $canalesContactoChartBuilder->build(
            $fechaSeleccionada ? $fechaSeleccionada->copy()->startOfMonth()->toDateString() : null,
            $fechaSeleccionada ? $fechaSeleccionada->copy()->endOfMonth()->toDateString() : null,
            $equipoSeleccionado ? (int) $equipoSeleccionado : null,
            $ejecutivoSeleccionado ? (int) $ejecutivoSeleccionado : null
        );

        // Gráficos temporales (usan clientesQueryTemporal SIN filtro de fecha, el periodo solo define la ventana de 12 meses)
        $clientesNuevosChart = $clientesNuevosChartBuilder->build($clientesQueryTemporal->clone(), $fechaSeleccionada, $equipoSeleccionado ? (int) $equipoSeleccionado : null, $ejecutivoSeleccionado ? (int) $ejecutivoSeleccionado : null);
        $actividadContactoChart = $actividadContactoChartBuilder->build($clientesQueryTemporal->clone(), $fechaSeleccionada, $equipoSeleccionado ? (int) $equipoSeleccionado : null, $ejecutivoSeleccionado ? (int) $ejecutivoSeleccionado : null);

        // ========== SECCIÓN GESTIONES DIARIAS ==========
        $mesGestionesDiarias = $fechaSeleccionada ? $fechaSeleccionada->copy() : Carbon::now();
        $fechaCorte = Carbon::now();
        $gdFechaInicio = $mesGestionesDiarias->copy()->startOfMonth()->toDateString();
        $gdFechaFinMes = $mesGestionesDiarias->copy()->endOfMonth()->toDateString();
        $gdFechaFin = min($gdFechaFinMes, $fechaCorte->toDateString());
        $eqId = $equipoSeleccionado ? (int) $equipoSeleccionado : null;
        $ejId = $ejecutivoSeleccionado ? (int) $ejecutivoSeleccionado : null;

        // KPI: Total Gestiones del mes (gestiones únicas: cliente + día)
        $sqlTotalGestiones = "SELECT COUNT(DISTINCT c.cliente_id, DATE(c.created_at)) AS total
                              FROM comentarios c
                              INNER JOIN clientes cl ON cl.id = c.cliente_id
                              WHERE DATE(c.created_at) >= ? AND DATE(c.created_at) <= ?";
        $paramsGestiones = [$gdFechaInicio, $gdFechaFin];
        if ($eqId) { $sqlTotalGestiones .= " AND c.user_id IN (SELECT user_id FROM equipo_user WHERE equipo_id = ?)"; $paramsGestiones[] = $eqId; }
        if ($ejId) { $sqlTotalGestiones .= " AND c.user_id = ?"; $paramsGestiones[] = $ejId; }
        $totalGestionesMes = DB::select($sqlTotalGestiones, $paramsGestiones)[0]->total;

        // KPI: Clientes Nuevos del mes
        $sqlClientesNuevosMes = "SELECT COUNT(id) AS total FROM clientes WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?";
        $paramsNuevos = [$gdFechaInicio, $gdFechaFin];
        if ($eqId) { $sqlClientesNuevosMes .= " AND equipo_id = ?"; $paramsNuevos[] = $eqId; }
        if ($ejId) { $sqlClientesNuevosMes .= " AND user_id = ?"; $paramsNuevos[] = $ejId; }
        $clientesNuevosMes = DB::select($sqlClientesNuevosMes, $paramsNuevos)[0]->total;

        // Tabla pivot: Gestiones diarias por Ejecutivo (atribuidas a quien hizo el comentario)
        $sqlGestionesPivot = "SELECT c.user_id, u.name AS ejecutivo, DATE(c.created_at) AS dia,
                                     COUNT(DISTINCT c.cliente_id, DATE(c.created_at)) AS total
                              FROM comentarios c
                              INNER JOIN clientes cl ON cl.id = c.cliente_id
                              INNER JOIN users u ON u.id = c.user_id
                              WHERE DATE(c.created_at) >= ? AND DATE(c.created_at) <= ?";
        $paramsPivot = [$gdFechaInicio, $gdFechaFin];
        if ($eqId) { $sqlGestionesPivot .= " AND c.user_id IN (SELECT user_id FROM equipo_user WHERE equipo_id = ?)"; $paramsPivot[] = $eqId; }
        if ($ejId) { $sqlGestionesPivot .= " AND c.user_id = ?"; $paramsPivot[] = $ejId; }
        $sqlGestionesPivot .= " GROUP BY c.user_id, u.name, DATE(c.created_at) ORDER BY u.name ASC, dia ASC";
        $gestionesPivotRaw = DB::select($sqlGestionesPivot, $paramsPivot);

        // Tabla pivot: Clientes nuevos por Ejecutivo
        $sqlNuevosPivot = "SELECT cl.user_id, u.name AS ejecutivo, DATE(cl.created_at) AS dia,
                                  COUNT(cl.id) AS total
                           FROM clientes cl
                           INNER JOIN users u ON u.id = cl.user_id
                           WHERE DATE(cl.created_at) >= ? AND DATE(cl.created_at) <= ?";
        $paramsNuevosPivot = [$gdFechaInicio, $gdFechaFin];
        if ($eqId) { $sqlNuevosPivot .= " AND cl.equipo_id = ?"; $paramsNuevosPivot[] = $eqId; }
        if ($ejId) { $sqlNuevosPivot .= " AND cl.user_id = ?"; $paramsNuevosPivot[] = $ejId; }
        $sqlNuevosPivot .= " GROUP BY cl.user_id, u.name, DATE(cl.created_at) ORDER BY u.name ASC, dia ASC";
        $nuevosPivotRaw = DB::select($sqlNuevosPivot, $paramsNuevosPivot);

        // Procesar pivots: convertir a estructura [ejecutivo => [dia => total]]
        $gestionesPivot = $this->procesarPivot($gestionesPivotRaw);
        $nuevosPivot = $this->procesarPivot($nuevosPivotRaw);

        // Obtener días con datos para las tablas
        $diasConGestiones = collect($gestionesPivotRaw)->pluck('dia')->unique()->sort()->values()->toArray();
        $diasConNuevos = collect($nuevosPivotRaw)->pluck('dia')->unique()->sort()->values()->toArray();

        // Clientes por departamento (para el mapa)
        $sqlMapaDep = "SELECT UPPER(d.nombre) AS departamento, d.codigo,
                              COUNT(cl.id) AS clientes
                       FROM clientes cl
                       INNER JOIN departamentos d ON d.codigo = cl.departamento_codigo
                       WHERE DATE(cl.created_at) >= ? AND DATE(cl.created_at) <= ?";
        $paramsMapaDep = [$gdFechaInicio, $gdFechaFin];
        if ($eqId) { $sqlMapaDep .= " AND cl.equipo_id = ?"; $paramsMapaDep[] = $eqId; }
        if ($ejId) { $sqlMapaDep .= " AND cl.user_id = ?"; $paramsMapaDep[] = $ejId; }
        $sqlMapaDep .= " GROUP BY d.codigo, d.nombre ORDER BY clientes DESC";
        $clientesPorDepartamento = collect(DB::select($sqlMapaDep, $paramsMapaDep))
            ->keyBy('departamento')
            ->map(fn ($r) => (int) $r->clientes)
            ->toArray();

        // Gráficos de gestiones diarias
        $gestionesPorDiaChart = $gestionesPorDiaChartBuilder->build($mesGestionesDiarias->copy(), $eqId, $ejId, $fechaCorte->toDateString());
        $rankingEjecutivosChart = $rankingEjecutivosChartBuilder->build($mesGestionesDiarias->copy(), $eqId, $ejId, $fechaCorte->toDateString());

        return view('sistema.dashboard.index', [
            'chart' => $chart,
            'conversionChart' => $conversionChart,
            'tipoBaseChart' => $tipoBaseChart,
            'equipos' => $equipos,
            'ejecutivos' => $ejecutivos,
            'equipoSeleccionado' => $equipoSeleccionado,
            'ejecutivoSeleccionado' => $ejecutivoSeleccionado,
            'fechaSeleccionada' => $fechaSeleccionada,
            'totalClientes' => $totalClientes,
            'clientesEnEtapaCinco' => $clientesEnEtapaCinco,
            'etapaCinco' => $etapaCinco,
            'convertibilidad' => $convertibilidad,
            'funnelChart' => $funnelChart,
            'clientesNuevosChart' => $clientesNuevosChart,
            'etiquetaChart' => $etiquetaChart,
            'contactadosChart' => $contactadosChart,
            'canalesContactoChart' => $canalesContactoChart,
            'actividadContactoChart' => $actividadContactoChart,
            'totalGestionesMes' => $totalGestionesMes,
            'clientesNuevosMes' => $clientesNuevosMes,
            'gestionesPivot' => $gestionesPivot,
            'nuevosPivot' => $nuevosPivot,
            'diasConGestiones' => $diasConGestiones,
            'diasConNuevos' => $diasConNuevos,
            'mesGestionesDiarias' => $mesGestionesDiarias,
            'fechaCorte' => $fechaCorte,
            'nombreEquipoGD' => $eqId ? $equipos->find($eqId)->nombre ?? null : null,
            'gestionesPorDiaChart' => $gestionesPorDiaChart,
            'rankingEjecutivosChart' => $rankingEjecutivosChart,
            'clientesPorDepartamento' => $clientesPorDepartamento,
        ]);
    }

    private function procesarPivot(array $rows): array
    {
        $pivot = [];
        foreach ($rows as $row) {
            $pivot[$row->ejecutivo][$row->dia] = (int) $row->total;
        }
        return $pivot;
    }
}
