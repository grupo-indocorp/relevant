<?php

namespace App\Exports;

use App\Helpers\Helpers;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IndotechFunnelExport
{
    protected string $filtro;

    protected $user;

    protected int $chunkSize;

    protected string $filename;

    protected bool $addBom;

    protected bool $skipRoleRestrictions = false;

    public function __construct(string $filtro, $user, int $chunkSize = 1000, string $filename = 'IndotechFunnelExport.csv', bool $addBom = true, bool $skipRoleRestrictions = false)
    {
        $this->filtro = $filtro;
        $this->user = $user;
        $this->chunkSize = $chunkSize;
        $this->filename = $filename;
        $this->addBom = $addBom;
        $this->skipRoleRestrictions = $skipRoleRestrictions;
    }


    /**
     * Obtiene los datos directamente de clientes construyendo exportclientes al vuelo
     * No depende de la tabla exportclientes (que puede estar vacía)
     */
    public function getExportData($limit, $offset)
    {
        $sql = "
            SELECT 
                c.id as cliente_id,
                COALESCE(eq.nombre, '') as ejecutivo_equipo,
                COALESCE(u.name, '') as ejecutivo,
                COALESCE(c.user_id, 0) as ejecutivo_id,
                COALESCE(u.email, '') as ejecutivo_email,
                c.ruc,
                c.razon_social,
                c.ciudad,
                COALESCE((SELECT nombre FROM contactos WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), '') as contacto,
                COALESCE((SELECT celular FROM contactos WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), '') as contacto_celular,
                COALESCE((SELECT correo FROM contactos WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), '') as contacto_email,
                COALESCE((SELECT estadowick_id FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), '') as estado_wick,
                COALESCE((SELECT estadodito_id FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), '') as estado_dito,
                COALESCE((SELECT linea_claro FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), 0) as lineas_claro,
                COALESCE((SELECT linea_entel FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), 0) as lineas_entel,
                COALESCE((SELECT linea_bitel FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), 0) as lineas_bitel,
                COALESCE((SELECT linea_movistar FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1), 0) as lineas_movistar,
                COALESCE(et.nombre, '') as etapa,
                DATE(c.created_at) as fecha_creacion,
                DATE(c.fecha_gestion) as fecha_ultimo_contacto,
                0 as producto_categoria_1,
                0 as producto_categoria_1_total,
                0 as producto_categoria_2,
                0 as producto_categoria_2_total,
                0 as producto_categoria_3,
                0 as producto_categoria_3_total,
                COALESCE((SELECT comentario FROM comentarios WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1 OFFSET 0), '') as comentario_5,
                COALESCE((SELECT comentario FROM comentarios WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1 OFFSET 1), '') as comentario_4,
                COALESCE((SELECT comentario FROM comentarios WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1 OFFSET 2), '') as comentario_3,
                COALESCE((SELECT comentario FROM comentarios WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1 OFFSET 3), '') as comentario_2,
                COALESCE((SELECT comentario FROM comentarios WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1 OFFSET 4), '') as comentario_1,
                COALESCE((SELECT nombre FROM clientetipos WHERE id = (SELECT clientetipo_id FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1) LIMIT 1), '') as cliente_tipo,
                COALESCE((SELECT nombre FROM agencias WHERE id = (SELECT agencia_id FROM movistars WHERE cliente_id = c.id ORDER BY id DESC LIMIT 1) LIMIT 1), '') as agencia
            FROM clientes c
            LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN equipos eq ON eq.id = c.equipo_id
            LEFT JOIN etapas et ON et.id = c.etapa_id
            WHERE c.ruc IS NOT NULL AND c.ruc != ''
            ORDER BY c.id
            LIMIT ? OFFSET ?
        ";
        
        try {
            $results = DB::select($sql, [$limit, $offset]);
            Log::debug('Query executed successfully', ['limit' => $limit, 'offset' => $offset, 'result_count' => count($results)]);
            return $results;
        } catch (\Exception $e) {
            Log::error('Query failed', ['error' => $e->getMessage(), 'sql' => substr($sql, 0, 200)]);
            throw $e;
        }
    }

    public function headings(): array
    {
        return [
            'Cliente ID',
            'Equipo',
            'Ejecutivo',
            'Ejecutivo ID',
            'Ejecutivo Email',
            'Ruc',
            'Razón Social',
            'Ciudad',
            'Nombre Contacto',
            'Celular Contacto',
            'Correo Electrónico Contacto',
            'Estado Wick',
            'Evaluación Dito',
            'Líneas Claro',
            'Líneas Entel',
            'Líneas Bitel',
            'Líneas Movistar',
            'Etapa de Negociación',
            'Fecha Primer Contacto',
            'Fecha Último Contacto',
            'Movil Cantidad',
            'Movil Cargo Fijo.',
            'Fija Cantidad',
            'Fija Cargo Fijo',
            'Avanzada Cantidad',
            'Avanzada Cargo Fijo',
            'Último Comentario',
            '4to Comentario',
            '3er Comentario',
            '2do Comentario',
            '1er Comentario',
            'Tipo de Cliente',
            'Agencia',
        ];
    }


    protected function normalizeValue(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);
            return $json === false ? '' : $json;
        }

        $str = (string) $value;

        // Asegurar UTF-8
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str) ?: 'UTF-8');
        }

        return $str;
    }

    public function mapRow(array $row): array
    {
        return [
            $this->normalizeValue($row['cliente_id'] ?? ''),
            $this->normalizeValue($row['ejecutivo_equipo'] ?? ''),
            $this->normalizeValue($row['ejecutivo'] ?? ''),
            $this->normalizeValue($row['ejecutivo_id'] ?? ''),
            $this->normalizeValue($row['ejecutivo_email'] ?? ''),
            $this->normalizeValue($row['ruc'] ?? ''),
            $this->normalizeValue($row['razon_social'] ?? ''),
            $this->normalizeValue($row['ciudad'] ?? ''),
            $this->normalizeValue($row['contacto'] ?? ''),
            $this->normalizeValue($row['contacto_celular'] ?? ''),
            $this->normalizeValue($row['contacto_email'] ?? ''),
            $this->normalizeValue($row['estado_wick'] ?? ''),
            $this->normalizeValue($row['estado_dito'] ?? ''),
            $this->normalizeValue($row['lineas_claro'] ?? ''),
            $this->normalizeValue($row['lineas_entel'] ?? ''),
            $this->normalizeValue($row['lineas_bitel'] ?? ''),
            $this->normalizeValue($row['lineas_movistar'] ?? ''),
            $this->normalizeValue($row['etapa'] ?? ''),
            $this->normalizeValue($row['fecha_creacion'] ?? ''),
            $this->normalizeValue($row['fecha_ultimo_contacto'] ?? ''),
            $this->normalizeValue($row['producto_categoria_1'] ?? ''),
            $this->normalizeValue($row['producto_categoria_1_total'] ?? ''),
            $this->normalizeValue($row['producto_categoria_2'] ?? ''),
            $this->normalizeValue($row['producto_categoria_2_total'] ?? ''),
            $this->normalizeValue($row['producto_categoria_3'] ?? ''),
            $this->normalizeValue($row['producto_categoria_3_total'] ?? ''),
            $this->normalizeValue($row['comentario_5'] ?? ''),
            $this->normalizeValue($row['comentario_4'] ?? ''),
            $this->normalizeValue($row['comentario_3'] ?? ''),
            $this->normalizeValue($row['comentario_2'] ?? ''),
            $this->normalizeValue($row['comentario_1'] ?? ''),
            $this->normalizeValue($row['cliente_tipo'] ?? ''),
            $this->normalizeValue($row['agencia'] ?? ''),
        ];
    }

    public function exportToCsv(): StreamedResponse
    {
        $headers = $this->headings();
        $normalizedHeaders = array_map([$this, 'normalizeValue'], $headers);
        $filename = $this->filename;
        $chunkSize = $this->chunkSize;
        $addBom = $this->addBom;
        $exporterInstance = $this;

        $callback = function () use ($normalizedHeaders, $filename, $chunkSize, $addBom, $exporterInstance) {
            $file = fopen('php://output', 'w');

            try {
                if ($addBom) {
                    fwrite($file, "\xEF\xBB\xBF");
                }

                fputcsv($file, $normalizedHeaders, ',', '"', '\\');

                $processed = 0;
                $limit = $chunkSize;
                $offset = 0;
                
                Log::info('Starting Indotech export', ['filename' => $filename, 'chunk_size' => $limit]);
                
                while (true) {
                    try {
                        $rows = $exporterInstance->getExportData($limit, $offset);
                        
                        Log::info('Retrieved rows from DB', ['count' => count($rows), 'offset' => $offset, 'limit' => $limit]);
                        
                        if (empty($rows)) {
                            Log::info('No more rows to process');
                            break;
                        }
                        
                        foreach ($rows as $row) {
                            $rowArray = (array) $row;
                            $csvRow = $exporterInstance->mapRow($rowArray);
                            fputcsv($file, $csvRow, ',', '"', '\\');
                            $processed++;
                        }
                        
                        Log::info('Indotech export progress', ['processed' => $processed, 'offset' => $offset]);
                        
                        if (function_exists('set_time_limit')) {
                            set_time_limit(300);
                        }
                        
                        $offset += $limit;
                        
                    } catch (\Throwable $e) {
                        Log::error('Error in export loop at offset ' . $offset, ['error' => $e->getMessage()]);
                        throw $e;
                    }
                }
                
                Log::info('Indotech export completed', ['total_records' => $processed, 'filename' => $filename]);
                
            } catch (\Throwable $e) {
                Log::error('Error during streaming Indotech export: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                fputcsv($file, ['ERROR', 'Error during export: ' . substr($e->getMessage(), 0, 100)]);
            } finally {
                if (is_resource($file)) {
                    fclose($file);
                }
            }
        };

        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Content-Transfer-Encoding' => 'binary',
        ];

        return response()->stream($callback, 200, $responseHeaders);
    }
}
