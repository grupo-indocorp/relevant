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

    private function createTempExportDirectory(): string
    {
        $base = sys_get_temp_dir();
        $dir = $base . DIRECTORY_SEPARATOR . 'ruc_export_' . uniqid('', true);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function cleanupTempExportDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        @rmdir($dir);
    }

    private function writeUtf8Bom($handle): void
    {
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
    }

    private function normalizeCsvField(mixed $value, bool $isPhone = false, bool $isDni = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            $flattened = array_map(
                fn($item) => is_scalar($item) ? (string)$item : json_encode($item, JSON_UNESCAPED_UNICODE),
                array_filter($value, fn($item) => $item !== null && $item !== '')
            );
            $result = implode(',', $flattened);
            if ($isPhone && !empty($result)) {
                return "'" . $result . "'";
            }
            return $result;
        }

        $strValue = (string)$value;
        if ($isDni && !empty($strValue)) {
            return "'" . $strValue . "'";
        }
        if ($isPhone && !empty($strValue)) {
            return "'" . $strValue . "'";
        }
        return $strValue;
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
            // Aumentar límite de tiempo para exportaciones masivas
            set_time_limit(0);
            ini_set('max_execution_time', '0');

            $filters = $request->input('filters', []);
            $batchSize = $request->input('batch_size', 2000);
            $limit = $request->input('limit', null);

            if (is_string($filters)) {
                $filters = json_decode($filters, true) ?: [];
            }
            if (!is_array($filters)) {
                $filters = [];
            }
            if (!empty($filters['actividad_economica']) && is_string($filters['actividad_economica'])) {
                $filters['actividad_economica'] = array_filter(array_map('trim', explode(',', $filters['actividad_economica'])));
            }

            Log::info("Iniciando exportación RUC20 ZIP - Filtros: " . json_encode($filters) .
                ", Batch size: {$batchSize}, Limit: {$limit}");

            $timestamp = now()->format('Ymd_His');
            $zipName = "ruc20_{$timestamp}.zip";
            $tempDir = $this->createTempExportDirectory();
            $empresasPath = $tempDir . DIRECTORY_SEPARATOR . 'empresas.csv';
            $representantesPath = $tempDir . DIRECTORY_SEPARATOR . 'representantes_legales.csv';
            $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipName;

            $empresaHeaders = [
                'ruc', 'razon_social', 'estado', 'condicion', 'tipo', 'trabajadores', 'actividad_economica',
                'motivo', 'subsegmento_agosto', 'ganado_por', 'gerente', 'sml',
                'departamento', 'provincia', 'distrito', 'ubigeo', 'direccion',
                'telefono_movistar', 'telefono_claro', 'telefono_entel', 'telefono_competencia',
                'sunat_ultima_actualizacion', 'sunat_nombre', 'sunat_actividad_economica',
                'sunat_estado', 'sunat_condicion', 'sunat_trabajadores', 'sunat_anexos'
            ];
            $representanteHeaders = ['ruc', 'nombre', 'cargo', 'tipo_doc', 'nro_doc', 'telefono_movistar', 'telefono_claro', 'telefono_entel', 'telefono_otros'];

            $empresaHandle = fopen($empresasPath, 'w');
            $this->writeUtf8Bom($empresaHandle);
            fputcsv($empresaHandle, $empresaHeaders);

            $repHandle = fopen($representantesPath, 'w');
            $this->writeUtf8Bom($repHandle);
            fputcsv($repHandle, $representanteHeaders);

            $lastEmpresaId = 0;
            $batchNum = 1;
            $totalWritten = 0;
            $writtenReps = [];

            while (true) {
                $currentBatchSize = $batchSize;
                if ($limit !== null) {
                    $remaining = $limit - $totalWritten;
                    if ($remaining <= 0) {
                        Log::info("Límite de {$limit} registros alcanzado");
                        break;
                    }
                    $currentBatchSize = min($batchSize, $remaining);
                }

                Log::info("RUC20 exportando lote {$batchNum} (last_id: {$lastEmpresaId}, size: {$currentBatchSize})");
                $empresas = $this->ruc20Service->getEmpresasBatchForExport($filters, $currentBatchSize, $lastEmpresaId);
                if (empty($empresas)) {
                    Log::info("No hay más empresas para exportar. Total escrito: {$totalWritten}");
                    break;
                }

                Log::debug("RUC20 Lote {$batchNum}: Obtenidas " . count($empresas) . " empresas");
                Log::debug("RUC20 Lote {$batchNum} - Primer registro: " . json_encode(reset($empresas), JSON_UNESCAPED_UNICODE));

                $rucList = array_values(array_unique(array_column($empresas, 'RUC')));
                Log::debug("RUC20 Lote {$batchNum}: " . count($rucList) . " RUCs únicos");
                $representantes = $this->ruc20Service->getRepresentantesForRucList($rucList);
                Log::debug("RUC20 Lote {$batchNum}: Obtenidos " . count($representantes) . " representantes");

                foreach ($empresas as $idx => $empresa) {
                    $row = [
                        $this->normalizeCsvField($empresa['RUC'] ?? ''),
                        $this->normalizeCsvField($empresa['Razón_Social'] ?? ''),
                        $this->normalizeCsvField($empresa['Estado'] ?? ''),
                        $this->normalizeCsvField($empresa['Condicion'] ?? ''),
                        $this->normalizeCsvField($empresa['Tipo'] ?? ''),
                        $this->normalizeCsvField($empresa['NroTrab'] ?? ''),
                        $this->normalizeCsvField($empresa['Actividad_Economica_Principal'] ?? ''),
                        $this->normalizeCsvField($empresa['motivo'] ?? ''),
                        $this->normalizeCsvField($empresa['subsegmento_agosto'] ?? ''),
                        $this->normalizeCsvField($empresa['ganado_por'] ?? ''),
                        $this->normalizeCsvField($empresa['gerente'] ?? ''),
                        $this->normalizeCsvField($empresa['s_m_l'] ?? ''),
                        $this->normalizeCsvField($empresa['Departamento'] ?? ''),
                        $this->normalizeCsvField($empresa['Provincia'] ?? ''),
                        $this->normalizeCsvField($empresa['Distrito'] ?? ''),
                        $this->normalizeCsvField($empresa['UBIGEO'] ?? ''),
                        $this->normalizeCsvField($empresa['direccion'] ?? ''),
                        $this->normalizeCsvField($empresa['movistar_lines'] ?? '', true),
                        $this->normalizeCsvField($empresa['claro_lines'] ?? '', true),
                        $this->normalizeCsvField($empresa['entel_lines'] ?? '', true),
                        $this->normalizeCsvField($empresa['competence_lines'] ?? '', true),
                        $this->normalizeCsvField($empresa['consulta_fecha_consulta'] ?? ''),
                        $this->normalizeCsvField($empresa['consulta_nombre_razon_social'] ?? ''),
                        $this->normalizeCsvField($empresa['consulta_actividades_economicas'] ?? ''),
                        $this->normalizeCsvField($empresa['consulta_estado_contribuyente'] ?? ''),
                        $this->normalizeCsvField($empresa['consulta_condicion_contribuyente'] ?? ''),
                        $this->normalizeCsvField($empresa['consulta_cant_trabajadores'] ?? ''),
                        $this->normalizeCsvField($empresa['consulta_cant_anexos'] ?? ''),
                    ];
                    if ($idx === 0 && $batchNum === 1) {
                        Log::debug("RUC20 Primer registro escribiendo:", $row);
                    }
                    fputcsv($empresaHandle, $row);
                }

                foreach ($representantes as $rep) {
                    $key = implode('|', [($rep['ruc'] ?? ''), ($rep['nro_doc'] ?? ''), ($rep['cargo'] ?? '')]);
                    if (isset($writtenReps[$key])) {
                        continue;
                    }
                    $writtenReps[$key] = true;
                    fputcsv($repHandle, [
                        $this->normalizeCsvField($rep['ruc'] ?? ''),
                        $this->normalizeCsvField($rep['nombre'] ?? ''),
                        $this->normalizeCsvField($rep['cargo'] ?? ''),
                        $this->normalizeCsvField($rep['tipo_doc'] ?? ''),
                        $this->normalizeCsvField($rep['nro_doc'] ?? '', false, true),
                        $this->normalizeCsvField($rep['operador_movistar'] ?? '', true),
                        $this->normalizeCsvField($rep['operador_claro'] ?? '', true),
                        $this->normalizeCsvField($rep['operador_entel'] ?? '', true),
                        $this->normalizeCsvField($rep['operador_otros'] ?? '', true),
                    ]);
                }

                $totalWritten += count($empresas);
                $lastEmpresaId = max(array_column($empresas, 'id'));
                $batchNum++;

                if ($batchNum % 10 === 0) {
                    usleep(300000);
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }

            fclose($empresaHandle);
            fclose($repHandle);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('No se pudo crear el archivo ZIP de exportación');
            }
            $zip->addFile($empresasPath, 'empresas.csv');
            $zip->addFile($representantesPath, 'representantes_legales.csv');
            $zip->close();

            return response()->streamDownload(function () use ($zipPath, $tempDir) {
                readfile($zipPath);
                $this->cleanupTempExportDirectory($tempDir);
            }, $zipName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
            ]);

        } catch (\Exception $e) {
            Log::error("Error exportando RUC20 ZIP: " . $e->getMessage(), ['exception' => $e]);
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
            $filters = $request->input('filters', []);
            $batchSize = $request->input('batch_size', 2000);
            $limit = $request->input('limit', null);

            if (is_string($filters)) {
                $filters = json_decode($filters, true) ?: [];
            }
            if (!is_array($filters)) {
                $filters = [];
            }
            if (!empty($filters['actividad_economica']) && is_string($filters['actividad_economica'])) {
                $filters['actividad_economica'] = array_filter(array_map('trim', explode(',', $filters['actividad_economica'])));
            }

            Log::info("Iniciando exportación RUC10 ZIP - Filtros: " . json_encode($filters) .
                ", Batch size: {$batchSize}, Limit: {$limit}");

            $timestamp = now()->format('Ymd_His');
            $zipName = "ruc10_{$timestamp}.zip";
            $tempDir = $this->createTempExportDirectory();
            $personasPath = $tempDir . DIRECTORY_SEPARATOR . 'personas.csv';
            $vinculacionesPath = $tempDir . DIRECTORY_SEPARATOR . 'vinculaciones.csv';
            $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipName;

            $personaHeaders = [
                'dni', 'ruc_asociado', 'razon_social', 'estado', 'condicion', 'direccion', 'ubigeo',
                'departamento', 'provincia', 'distrito', 'actividad_economica', 'telefono_movistar',
                'telefono_claro', 'telefono_entel', 'telefono_otros', 'apellido_paterno', 'apellido_materno',
                'nombres', 'nombre_completo', 'fecha_nacimiento', 'fecha_emision', 'fecha_caducidad',
                'sexo', 'estado_civil', 'madre', 'padre', 'ubigeo_direccion', 'direccion_reniec'
            ];
            $vinculacionHeaders = ['dni', 'empresa_ruc', 'empresa', 'estado_empresa', 'cargo', 'condicion_empresa', 'actividad_economica_empresa', 'trabajadores', 'anexos', 'sunat_ultima_actualizacion'];

            $personasHandle = fopen($personasPath, 'w');
            $this->writeUtf8Bom($personasHandle);
            fputcsv($personasHandle, $personaHeaders);

            $vinculacionesHandle = fopen($vinculacionesPath, 'w');
            $this->writeUtf8Bom($vinculacionesHandle);
            fputcsv($vinculacionesHandle, $vinculacionHeaders);

            $lastPersonaId = 0;
            $batchNum = 1;
            $totalWritten = 0;

            while (true) {
                $currentBatchSize = $batchSize;
                if ($limit !== null) {
                    $remaining = $limit - $totalWritten;
                    if ($remaining <= 0) {
                        Log::info("Límite de {$limit} registros alcanzado");
                        break;
                    }
                    $currentBatchSize = min($batchSize, $remaining);
                }

                Log::info("RUC10 exportando lote {$batchNum} (last_id: {$lastPersonaId}, size: {$currentBatchSize})");
                $personas = $this->ruc10Service->getPersonasBatchForExport($filters, $currentBatchSize, $lastPersonaId);
                if (empty($personas)) {
                    Log::info("No hay más personas para exportar. Total escrito: {$totalWritten}");
                    break;
                }

                Log::debug("RUC10 Lote {$batchNum}: Obtenidas " . count($personas) . " personas");
                Log::debug("RUC10 Lote {$batchNum} - Primer registro: " . json_encode(reset($personas), JSON_UNESCAPED_UNICODE));

                $dniList = array_values(array_unique(array_column($personas, 'dni')));
                Log::debug("RUC10 Lote {$batchNum}: " . count($dniList) . " DNIs únicos");
                $vinculaciones = $this->ruc10Service->getVinculacionesForDniList($dniList);
                Log::debug("RUC10 Lote {$batchNum}: Obtenidas " . count($vinculaciones) . " vinculaciones");

                foreach ($personas as $idx => $persona) {
                    $apellidoPaterno = $persona['ap_pat'] ?? $persona['apellido_paterno'] ?? '';
                    $apellidoMaterno = $persona['ap_mat'] ?? $persona['apellido_materno'] ?? '';
                    $nombres = $persona['nombres'] ?? '';
                    $nombreCompleto = trim(implode(' ', array_filter([$apellidoPaterno, $apellidoMaterno, $nombres])));

                    if ($idx === 0 && $batchNum === 1) {
                        Log::debug("RUC10 Primer registro persona: " . json_encode($persona, JSON_UNESCAPED_UNICODE));
                    }

                    $row = [
                        $this->normalizeCsvField($persona['dni'] ?? '', false, true),
                        $this->normalizeCsvField($persona['RUC'] ?? ''),
                        $this->normalizeCsvField($persona['Razón_Social'] ?? ''),
                        $this->normalizeCsvField($persona['Estado'] ?? ''),
                        $this->normalizeCsvField($persona['Condicion'] ?? ''),
                        $this->normalizeCsvField($persona['direccion'] ?? ''),
                        $this->normalizeCsvField($persona['UBIGEO'] ?? ''),
                        $this->normalizeCsvField($persona['Departamento'] ?? ''),
                        $this->normalizeCsvField($persona['Provincia'] ?? ''),
                        $this->normalizeCsvField($persona['Distrito'] ?? ''),
                        $this->normalizeCsvField($persona['Actividad_Economica_Principal'] ?? ''),
                        $this->normalizeCsvField($persona['lista_movistar'] ?? '', true),
                        $this->normalizeCsvField($persona['lista_claro'] ?? '', true),
                        $this->normalizeCsvField($persona['lista_entel'] ?? '', true),
                        $this->normalizeCsvField($persona['lista_otros'] ?? '', true),
                        $this->normalizeCsvField($apellidoPaterno),
                        $this->normalizeCsvField($apellidoMaterno),
                        $this->normalizeCsvField($nombres),
                        $this->normalizeCsvField($nombreCompleto),
                        $this->normalizeCsvField($persona['fecha_nac'] ?? ''),
                        $this->normalizeCsvField($persona['fch_emision'] ?? ''),
                        $this->normalizeCsvField($persona['fch_caducidad'] ?? ''),
                        $this->normalizeCsvField($persona['sexo'] ?? ''),
                        $this->normalizeCsvField($persona['est_civil'] ?? ''),
                        $this->normalizeCsvField($persona['madre'] ?? ''),
                        $this->normalizeCsvField($persona['padre'] ?? ''),
                        $this->normalizeCsvField($persona['ubigeo_dir'] ?? $persona['ubigeo_direccion'] ?? ''),
                        $this->normalizeCsvField($persona['direccion_reniec'] ?? $persona['direccion'] ?? ''),
                    ];
                    if ($idx === 0 && $batchNum === 1) {
                        Log::debug("RUC10 Primer registro escribiendo:", $row);
                    }
                    fputcsv($personasHandle, $row);
                }

                foreach ($vinculaciones as $idx => $vinculacion) {
                    if ($idx === 0 && $batchNum === 1) {
                        Log::debug("RUC10 Primer registro vinculacion: " . json_encode($vinculacion, JSON_UNESCAPED_UNICODE));
                    }
                    $row = [
                        $this->normalizeCsvField($vinculacion['dni'] ?? '', false, true),
                        $this->normalizeCsvField($vinculacion['empresa_ruc'] ?? ''),
                        $this->normalizeCsvField($vinculacion['empresa'] ?? ''),
                        $this->normalizeCsvField($vinculacion['estado_empresa'] ?? ''),
                        $this->normalizeCsvField($vinculacion['cargo'] ?? ''),
                        $this->normalizeCsvField($vinculacion['condicion_empresa'] ?? ''),
                        $this->normalizeCsvField($vinculacion['actividad_empresa'] ?? ''),
                        $this->normalizeCsvField($vinculacion['trabajadores'] ?? ''),
                        $this->normalizeCsvField($vinculacion['anexos'] ?? ''),
                        $this->normalizeCsvField($vinculacion['ultima_actualizacion'] ?? ''),
                    ];
                    if ($idx === 0 && $batchNum === 1) {
                        Log::debug("RUC10 Primer registro vinculacion escribiendo:", $row);
                    }
                    fputcsv($vinculacionesHandle, $row);
                }

                $totalWritten += count($personas);
                $lastPersonaId = end($personas)['dni'] ?? null;
                $batchNum++;

                if ($batchNum % 10 === 0) {
                    usleep(300000);
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }

            fclose($personasHandle);
            fclose($vinculacionesHandle);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('No se pudo crear el archivo ZIP de exportación');
            }
            $zip->addFile($personasPath, 'personas.csv');
            $zip->addFile($vinculacionesPath, 'vinculaciones.csv');
            $zip->close();

            return response()->streamDownload(function () use ($zipPath, $tempDir) {
                readfile($zipPath);
                $this->cleanupTempExportDirectory($tempDir);
            }, $zipName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
            ]);

        } catch (\Exception $e) {
            Log::error("Error exportando RUC10 ZIP: " . $e->getMessage(), ['exception' => $e]);
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
