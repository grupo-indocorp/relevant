<?php

namespace App\Http\Controllers;

use App\Services\RUC20Service;
use App\Services\RUC10Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusquedaController extends Controller
{
    protected $ruc20Service;
    protected $ruc10Service;

    public function __construct(RUC20Service $ruc20Service, RUC10Service $ruc10Service)
    {
        $this->ruc20Service = $ruc20Service;
        $this->ruc10Service = $ruc10Service;
    }

    /**
     * Devuelve true si el arreglo es asociativo
     */
    private function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Aplana un registro (array asociativo) para CSV.
     * - Campos escalares quedan como `key`.
     * - Arrays asociativos quedan como `parent_child`.
     * - Arrays indexados (representantes, consultas, telefonos, etc.) se expanden hasta $maxArrayItems
     *   como `parent_1_field`, `parent_2_field`, ... y valores anidados se convierten en columnas.
     */
    private function flattenRecord(array $record, array &$headers, int $maxArrayItems = 5): array
    {
        $flat = [];

        foreach ($record as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssoc($value)) {
                    // Asociativo: parent_child
                    foreach ($value as $subKey => $subVal) {
                        if (is_array($subVal)) {
                            // Profundizar: convertir a JSON para evitar columnas infinitas
                            $col = "{$key}_{$subKey}";
                            $flat[$col] = json_encode($subVal, JSON_UNESCAPED_UNICODE);
                            $headers[$col] = true;
                        } else {
                            $col = "{$key}_{$subKey}";
                            $flat[$col] = $subVal;
                            $headers[$col] = true;
                        }
                    }
                } else {
                    // Indexado: expandir elementos como parent_1_field, parent_2_field ...
                    $i = 0;
                    foreach ($value as $elem) {
                        if ($i >= $maxArrayItems) break;
                        $idx = $i + 1;
                        if (is_array($elem)) {
                            if ($this->isAssoc($elem)) {
                                foreach ($elem as $subKey => $subVal) {
                                    $col = "{$key}_{$idx}_{$subKey}";
                                    if (is_array($subVal)) {
                                        $flat[$col] = json_encode($subVal, JSON_UNESCAPED_UNICODE);
                                    } else {
                                        $flat[$col] = $subVal;
                                    }
                                    $headers[$col] = true;
                                }
                            } else {
                                // Elemento indexado dentro de indexado -> JSON
                                $col = "{$key}_{$idx}";
                                $flat[$col] = json_encode($elem, JSON_UNESCAPED_UNICODE);
                                $headers[$col] = true;
                            }
                        } else {
                            $col = "{$key}_{$idx}";
                            $flat[$col] = $elem;
                            $headers[$col] = true;
                        }
                        $i++;
                    }
                }
            } else {
                $flat[$key] = $value;
                $headers[$key] = true;
            }
        }

        return $flat;
    }

    /**
     * Página principal con el módulo de búsqueda
     * Muestra las 3 opciones de búsqueda
     */
    public function index()
    {
        try {
            return view('busqueda.index');
        } catch (\Exception $e) {
            Log::error("Error cargando página principal: " . $e->getMessage());
            return view('busqueda.index', ['error' => 'Error al cargar la página']);
        }
    }

    /**
     * Página de búsqueda masiva con opciones para RUC 10 y RUC 20
     */
    public function buscarMasivo()
    {
        try {
            return view('busqueda.search_massive');
        } catch (\Exception $e) {
            Log::error("Error cargando página de búsqueda masiva: " . $e->getMessage());
            return view('busqueda.search_massive', ['error' => 'Error al cargar la página']);
        }
    }

    /**
     * Búsqueda individual de RUC 20
     * GET: Muestra el formulario
     * POST: Procesa la búsqueda
     */
    public function buscarRUC20(Request $request)
    {
        if ($request->isMethod('GET')) {
            try {
                return view('busqueda.search_ruc20');
            } catch (\Exception $e) {
                Log::error("Error cargando página de búsqueda RUC 20: " . $e->getMessage());
                return view('busqueda.search_ruc20', ['error' => 'Error al cargar la página']);
            }
        }

        // POST request
        try {
            $ruc = $request->input('ruc', '');
            $ruc = trim($ruc);

            if (empty($ruc)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor ingrese un RUC'
                ], 400);
            }

            // Usar service para búsqueda
            $result = $this->ruc20Service->searchIndividual($ruc);

            // Retornar resultado (con código 404 si no hay datos pero validación es OK)
            $statusCode = $result['success'] ? 200 : ($result['data'] === null ? 404 : 400);
            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error("Error en búsqueda RUC 20: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda'
            ], 500);
        }
    }

    /**
     * Búsqueda masiva de RUC 20 con paginación
     */
    public function buscarRUC20Masivo(Request $request)
    {
        try {
            // Obtener parámetros
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 25);

            // Obtener filtros
            $filters = [];
            if ($request->has('ruc')) $filters['ruc'] = $request->input('ruc');
            if ($request->has('razon_social')) $filters['razon_social'] = $request->input('razon_social');
            if ($request->has('estado')) $filters['estado'] = $request->input('estado');
            if ($request->has('condicion')) $filters['condicion'] = $request->input('condicion');
            if ($request->has('departamento')) $filters['departamento'] = $request->input('departamento');
            if ($request->has('provincia')) $filters['provincia'] = $request->input('provincia');
            if ($request->has('distrito')) $filters['distrito'] = $request->input('distrito');
            if ($request->has('actividad_economica')) $filters['actividad_economica'] = $request->input('actividad_economica');
            if ($request->has('min_trabajadores')) $filters['min_trabajadores'] = $request->input('min_trabajadores');
            if ($request->has('min_anexos')) $filters['min_anexos'] = $request->input('min_anexos');

            // Usar service para búsqueda masiva
            $result = $this->ruc20Service->searchMassive($page, $perPage, !empty($filters) ? $filters : null);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error("Error en búsqueda masiva RUC 20: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda masiva'
            ], 500);
        }
    }

    /**
     * Exporta datos RUC20 a CSV usando streaming por lotes
     * Maneja volúmenes de datos con optimización de memoria
     * Soporta parámetro 'limit' para limitar cantidad de registros
     */
    public function exportRUC20CSV(Request $request)
    {
        try {
            $data = $request->json()->all();
            $filters = $data['filters'] ?? [];
            $batchSize = $data['batch_size'] ?? 10000; // Configurable desde frontend
            $limit = $data['limit'] ?? null; // Límite opcional de registros

            Log::info("Iniciando exportación RUC20 - Filtros: " . json_encode($filters) . 
                     ", Batch size: {$batchSize}, Limit: {$limit}");

            $timestamp = now()->format('Ymd_His');
            $filename = "ruc20_export_{$timestamp}.csv";

            $self = $this;
            return new StreamedResponse(function () use ($filters, $batchSize, $limit, $self) {
                $offset = 0;
                $batchNum = 1;
                $totalYielded = 0;

                // Abrir output stream
                $output = fopen('php://output', 'w');

                // Encabezados dinámicos (se determinan a partir del primer registro)
                $headersMap = [];
                $headers = [];
                $headerWritten = false;

                while (true) {
                    // Si tenemos límite, ajustar el lote
                    $currentBatchSize = $batchSize;
                    if ($limit !== null) {
                        $remaining = $limit - $totalYielded;
                        if ($remaining <= 0) {
                            Log::info("Límite de {$limit} registros alcanzado");
                            break;
                        }
                        $currentBatchSize = min($batchSize, $remaining);
                    }

                    Log::info("Obteniendo lote {$batchNum} (offset: {$offset}, size: {$currentBatchSize})");

                    // Obtener lote de datos
                    $batch = $this->ruc20Service->getBatchForExport($filters, $currentBatchSize, $offset);

                    if (empty($batch)) {
                        Log::info("No hay más datos. Total procesado: {$totalYielded} registros");
                        break;
                    }

                        Log::info("Lote {$batchNum}: " . count($batch) . " registros");

                        // Si aún no escribimos encabezados, generarlos usando el primer registro del batch
                        if (!$headerWritten) {
                            $first = $batch[0];
                            $flatFirst = $self->flattenRecord(is_array($first) ? $first : (array)$first, $headersMap);
                            $headers = array_keys($headersMap);
                            // Escribir encabezados
                            fputcsv($output, $headers);
                            $headerWritten = true;
                        }

                        // Escribir datos al CSV usando los encabezados establecidos
                        foreach ($batch as $row) {
                            $flat = $self->flattenRecord(is_array($row) ? $row : (array)$row, $headersMap);
                            $line = [];
                            foreach ($headers as $h) {
                                $val = $flat[$h] ?? '';
                                // Normalizar valores compuestos
                                if (is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                                $line[] = $val;
                            }
                            fputcsv($output, $line);
                        }

                    $totalYielded += count($batch);
                    $offset += $batchSize;
                    $batchNum++;

                    // Pausa para reducir carga en la BD
                    if ($batchNum % 10 == 0) {
                        usleep(500000); // 0.5 segundos
                    }
                }

                fclose($output);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\""
            ]);

        } catch (\Exception $e) {
            Log::error("Error exportando RUC20: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Búsqueda individual de DNI (RUC 10)
     * GET: Muestra el formulario
     * POST: Procesa la búsqueda
     */
    public function buscarDNI(Request $request)
    {
        if ($request->isMethod('GET')) {
            try {
                return view('busqueda.search_dni');
            } catch (\Exception $e) {
                Log::error("Error cargando página de búsqueda DNI: " . $e->getMessage());
                return view('busqueda.search_dni', ['error' => 'Error al cargar la página']);
            }
        }

        // POST request
        try {
            $dni = $request->input('dni', '');
            $dni = trim($dni);

            if (empty($dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor ingrese un DNI'
                ], 400);
            }

            // Usar service para búsqueda
            $result = $this->ruc10Service->searchIndividual($dni);

            // Retornar resultado
            $statusCode = $result['success'] ? 200 : ($result['data'] === null ? 404 : 400);
            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            Log::error("Error en búsqueda DNI: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda'
            ], 500);
        }
    }

    /**
     * Búsqueda masiva de RUC 10 con paginación
     */
    public function buscarRUC10Masivo(Request $request)
    {
        try {
            // Obtener parámetros
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 25);

            // Obtener filtros
            $filters = [];
            if ($request->has('dni')) $filters['dni'] = $request->input('dni');
            if ($request->has('razon_social')) $filters['razon_social'] = $request->input('razon_social');
            if ($request->has('estado')) $filters['estado'] = $request->input('estado');
            if ($request->has('condicion')) $filters['condicion'] = $request->input('condicion');
            if ($request->has('departamento')) $filters['departamento'] = $request->input('departamento');
            if ($request->has('provincia')) $filters['provincia'] = $request->input('provincia');
            if ($request->has('distrito')) $filters['distrito'] = $request->input('distrito');
            if ($request->has('actividad_economica')) $filters['actividad_economica'] = $request->input('actividad_economica');

            // Usar service para búsqueda masiva
            $result = $this->ruc10Service->searchMassive($page, $perPage, !empty($filters) ? $filters : null);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error("Error en búsqueda masiva RUC 10: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda masiva'
            ], 500);
        }
    }

    /**
     * Exporta datos RUC10 a CSV usando streaming por lotes
     * Maneja volúmenes de millones de registros sin problemas de memoria
     * Soporta parámetro 'limit' para limitar cantidad de registros
     */
    public function exportRUC10CSV(Request $request)
    {
        try {
            $data = $request->json()->all();
            $filters = $data['filters'] ?? [];
            $batchSize = $data['batch_size'] ?? 10000; // Configurable desde frontend
            $limit = $data['limit'] ?? null; // Límite opcional de registros

            Log::info("Iniciando exportación streaming RUC10 - Filtros: " . json_encode($filters) . 
                     ", Batch size: {$batchSize}, Limit: {$limit}");

            $timestamp = now()->format('Ymd_His');
            $filename = "ruc10_export_{$timestamp}.csv";

            $self = $this;
            return new StreamedResponse(function () use ($filters, $batchSize, $limit, $self) {
                $offset = 0;
                $batchNum = 1;
                $totalYielded = 0;

                // Abrir output stream
                $output = fopen('php://output', 'w');

                // Encabezados dinámicos
                $headersMap = [];
                $headers = [];
                $headerWritten = false;

                while (true) {
                    // Si tenemos límite, ajustar el lote
                    $currentBatchSize = $batchSize;
                    if ($limit !== null) {
                        $remaining = $limit - $totalYielded;
                        if ($remaining <= 0) {
                            Log::info("Límite de {$limit} registros alcanzado");
                            break;
                        }
                        $currentBatchSize = min($batchSize, $remaining);
                    }

                    Log::info("Obteniendo lote {$batchNum} (offset: {$offset}, size: {$currentBatchSize})");

                    // Obtener lote de datos
                    $batch = $this->ruc10Service->getBatchForExport($filters, $currentBatchSize, $offset);

                    if (empty($batch)) {
                        Log::info("No hay más datos. Total procesado: {$totalYielded} registros");
                        break;
                    }


                    Log::info("Lote {$batchNum}: " . count($batch) . " registros");

                    if (!$headerWritten) {
                        $first = $batch[0];
                        $flatFirst = $self->flattenRecord(is_array($first) ? $first : (array)$first, $headersMap);
                        $headers = array_keys($headersMap);
                        fputcsv($output, $headers);
                        $headerWritten = true;
                    }

                    foreach ($batch as $row) {
                        $flat = $self->flattenRecord(is_array($row) ? $row : (array)$row, $headersMap);
                        $line = [];
                        foreach ($headers as $h) {
                            $val = $flat[$h] ?? '';
                            if (is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                            $line[] = $val;
                        }
                        fputcsv($output, $line);
                    }

                    $totalYielded += count($batch);
                    $offset += $batchSize;
                    $batchNum++;

                    // Pausa para reducir carga en la BD
                    if ($batchNum % 10 == 0) {
                        usleep(500000); // 0.5 segundos
                    }
                }

                fclose($output);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\""
            ]);

        } catch (\Exception $e) {
            Log::error("Error exportando RUC10: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene sugerencias de RUC20 para autocompletado
     */
    public function getRUC20Suggestions(Request $request)
    {
        try {
            $query = $request->input('query', '');
            $limit = $request->input('limit', 10);
            
            $result = $this->ruc20Service->getSuggestions($query, $limit);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Error obteniendo sugerencias RUC20: " . $e->getMessage());
            return response()->json(['success' => false, 'data' => []]);
        }
    }

    /**
     * Obtiene sugerencias de RUC10 para autocompletado
     */
    public function getRUC10Suggestions(Request $request)
    {
        try {
            $query = $request->input('query', '');
            $limit = $request->input('limit', 10);
            
            $result = $this->ruc10Service->getSuggestions($query, $limit);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Error obteniendo sugerencias RUC10: " . $e->getMessage());
            return response()->json(['success' => false, 'data' => []]);
        }
    }

    /**
     * Obtiene estadísticas de RUC20
     */
    public function getRUC20Stats()
    {
        try {
            $result = $this->ruc20Service->getStatistics();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas RUC20: " . $e->getMessage());
            return response()->json(['success' => false, 'data' => null]);
        }
    }

    /**
     * Obtiene estadísticas de RUC10
     */
    public function getRUC10Stats()
    {
        try {
            $result = $this->ruc10Service->getStatistics();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas RUC10: " . $e->getMessage());
            return response()->json(['success' => false, 'data' => null]);
        }
    }

    /**
     * Obtiene opciones de filtro para RUC 20
     */
    public function getRUC20FilterOptions($column)
    {
        try {
            $result = $this->ruc20Service->getFilterOptions($column);
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error("Error obteniendo filtros RUC 20: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener filtros', 'options' => []], 500);
        }
    }

    /**
     * Obtiene opciones de filtro para RUC 10
     */
    public function getRUC10FilterOptions($column)
    {
        try {
            $result = $this->ruc10Service->getFilterOptions($column);
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error("Error obteniendo filtros RUC 10: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener filtros', 'options' => []], 500);
        }
    }

    /**
     * Obtiene opciones de filtro para una columna específica
     */
    public function getFilterOptions($search_type, $column)
    {
        try {
            if ($search_type == 'ruc20') {
                $result = $this->ruc20Service->getFilterOptions($column);
            } elseif ($search_type == 'ruc10') {
                $result = $this->ruc10Service->getFilterOptions($column);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de búsqueda no válido',
                    'options' => []
                ], 400);
            }

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error("Error en endpoint filter-options: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'options' => []
            ], 500);
        }
    }

    /**
     * Obtiene actividades económicas para filtros de búsqueda masiva
     */
    public function getActividadEconomicaOptions(Request $request, $search_type)
    {
        try {
            $query = (string) ($request->input('query') ?? '');
            $limit = intval($request->input('limit', 50));
            $limit = $limit > 0 ? min($limit, 10000) : 50;

            if ($search_type === 'ruc20') {
                $result = $this->ruc20Service->getActividadEconomicaOptions($query, $limit);
            } elseif ($search_type === 'ruc10') {
                $result = $this->ruc10Service->getActividadEconomicaOptions($query, $limit);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de búsqueda no válido',
                    'data' => []
                ], 400);
            }

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error("Error obteniendo actividades económicas: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividades económicas',
                'data' => []
            ], 500);
        }
    }

    /**
     * Obtiene provincias filtradas por departamento
     */
    public function getProvinciasByDepartamento($search_type, $departamento)
    {
        try {
            if ($search_type == 'ruc20') {
                $provincias = \App\Repositories\RUC20Repository::getProvinciasByDepartamento($departamento);
            } elseif ($search_type == 'ruc10') {
                $provincias = \App\Repositories\RUC10Repository::getProvinciasByDepartamento($departamento);
            } else {
                return response()->json(['success' => false, 'options' => []], 400);
            }

            return response()->json([
                'success' => true,
                'options' => $provincias
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo provincias: " . $e->getMessage());
            return response()->json(['success' => false, 'options' => []], 500);
        }
    }

    /**
     * Obtiene distritos filtrados por departamento y provincia
     */
    public function getDistritosByProvincia($search_type, $departamento, $provincia)
    {
        try {
            if ($search_type == 'ruc20') {
                $distritos = \App\Repositories\RUC20Repository::getDistritosByProvincia($departamento, $provincia);
            } elseif ($search_type == 'ruc10') {
                $distritos = \App\Repositories\RUC10Repository::getDistritosByProvincia($departamento, $provincia);
            } else {
                return response()->json(['success' => false, 'options' => []], 400);
            }

            return response()->json([
                'success' => true,
                'options' => $distritos
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo distritos: " . $e->getMessage());
            return response()->json(['success' => false, 'options' => []], 500);
        }
    }
}
