<?php

namespace App\Exports;

use App\Helpers\Helpers;
use App\Models\Exportcliente;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;

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


    public function query(): Builder
    {
        $where = Helpers::filtroExportCliente(json_decode($this->filtro), $this->user, $this->skipRoleRestrictions);

        return Exportcliente::query()
            ->join('clientes', 'clientes.id', '=', 'exportclientes.cliente_id')
            ->leftJoin('users', 'users.id', '=', 'exportclientes.ejecutivo_id')
            ->whereIn('exportclientes.id', function ($q) use ($where) {
                $q->selectRaw('MAX(exportclientes.id)')
                  ->from('exportclientes')
                  ->join('clientes', 'clientes.id', '=', 'exportclientes.cliente_id')
                  ->when(!empty($where), function ($q) use ($where) { return $q->where($where); })
                  ->groupBy('clientes.ruc');
            })
            ->when(!empty($where), function ($q) use ($where) { return $q->where($where); })
            ->select('exportclientes.*', 'clientes.ruc', 'users.email as ejecutivo_email')
            ->orderBy('exportclientes.id');
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

    /**
     * Normaliza y convierte un valor a string seguro para CSV
     */
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

    public function map(Exportcliente $cliente): array
    {
        return [
            $this->normalizeValue($cliente->cliente_id ?? ''),
            $this->normalizeValue($cliente->ejecutivo_equipo ?? ''),
            $this->normalizeValue($cliente->ejecutivo ?? ''),
            $this->normalizeValue($cliente->ejecutivo_id ?? ''),
            $this->normalizeValue($cliente->ejecutivo_email ?? ''),
            $this->normalizeValue($cliente->ruc ?? ''),
            $this->normalizeValue($cliente->razon_social ?? ''),
            $this->normalizeValue($cliente->ciudad ?? ''),
            $this->normalizeValue($cliente->contacto ?? ''),
            $this->normalizeValue($cliente->contacto_celular ?? ''),
            $this->normalizeValue($cliente->contacto_email ?? ''),
            $this->normalizeValue($cliente->estado_wick ?? ''),
            $this->normalizeValue($cliente->estado_dito ?? ''),
            $this->normalizeValue($cliente->lineas_claro ?? ''),
            $this->normalizeValue($cliente->lineas_entel ?? ''),
            $this->normalizeValue($cliente->lineas_bitel ?? ''),
            $this->normalizeValue($cliente->etapa ?? ''),
            $this->normalizeValue($cliente->fecha_creacion ?? ''),
            $this->normalizeValue($cliente->fecha_ultimo_contacto ?? ''),
            $this->normalizeValue($cliente->producto_categoria_1 ?? ''),
            $this->normalizeValue($cliente->producto_categoria_1_total ?? ''),
            $this->normalizeValue($cliente->producto_categoria_2 ?? ''),
            $this->normalizeValue($cliente->producto_categoria_2_total ?? ''),
            $this->normalizeValue($cliente->producto_categoria_3 ?? ''),
            $this->normalizeValue($cliente->producto_categoria_3_total ?? ''),
            $this->normalizeValue($cliente->comentario_5 ?? ''),
            $this->normalizeValue($cliente->comentario_4 ?? ''),
            $this->normalizeValue($cliente->comentario_3 ?? ''),
            $this->normalizeValue($cliente->comentario_2 ?? ''),
            $this->normalizeValue($cliente->comentario_1 ?? ''),
            $this->normalizeValue($cliente->cliente_tipo ?? ''),
            $this->normalizeValue($cliente->agencia ?? ''),
        ];
    }

    public function exportToCsv(): StreamedResponse
    {
        $headers = $this->headings();
        $filename = $this->filename;

        // Log count for debug purposes
        try {
            $count = $this->query()->count();
            Log::info('Indotech export count: ' . $count, ['filename' => $filename, 'chunkSize' => $this->chunkSize]);
        } catch (\Throwable $e) {
            Log::error('Error al calcular count en Indotech export: ' . $e->getMessage());
            $count = 0;
        }

        $callback = function () use ($headers, $count, $filename) {
            $file = fopen('php://output', 'w');

            if ($this->addBom) {
                // Agregar BOM para Excel/UTF-8
                fwrite($file, "\xEF\xBB\xBF");
            }

            // Escribir las cabeceras
            fputcsv($file, array_map([$this, 'normalizeValue'], $headers));

            // Escribir los datos
            $this->query()->chunk(1000, function ($clientes) use ($file) {
                foreach ($clientes as $cliente) {
                    // $cliente->ejecutivo_equipo = $cliente->user?->equipos->last()?->nombre;

                    $row = $this->map($cliente);
                    // Convertir cada campo a UTF-8 si es necesario
                    $row = array_map(function ($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $row);
                    fputcsv($file, $row);
                }
            });
            // Si no hay filas en exportclientes, intentar exportar directo desde clientes (fallback)
            if ($count === 0) {
                Log::warning('Indotech export: no rows in exportclientes, using fallback export from clientes', ['filename' => $filename]);
                try {
                    $this->exportFromClientes($file);
                    fclose($file);
                    return;
                } catch (\Throwable $e) {
                    Log::error('Fallback exportFromClientes failed: ' . $e->getMessage());
                    fputcsv($file, ['ERROR', 'Fallback export failed, check logs']);
                    fclose($file);
                    return;
                }
            }

            // Escribir los datos por chunks para ahorrar memoria
            try {
                $this->query()->chunk($this->chunkSize, function ($clientes) use ($file) {
                    foreach ($clientes as $cliente) {
                        $row = $this->map($cliente);
                        fputcsv($file, $row);
                    }
                });
            } catch (\Throwable $e) {
                Log::error('Error during streaming Indotech export: ' . $e->getMessage());
                // Indicar un error en el CSV para facilitar diagnóstico
                fputcsv($file, ['ERROR', 'Error during export, check logs']);
            }

            fclose($file);
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

    /**
     * Fallback: exportar directamente desde la tabla clientes cuando no hay filas en exportclientes
     * Es una exportación ligera que intenta reconstruir campos relevantes.
     */
    protected function exportFromClientes($file)
    {
        // Aplicar los mismos filtros que en exportclientes
        $where = \App\Helpers\Helpers::filtroExportCliente(json_decode($this->filtro), $this->user, $this->skipRoleRestrictions);

        // Usar relaciones para minimizar N+1
        \App\Models\Cliente::with(['user', 'equipo', 'sede', 'etapa', 'contactos', 'movistars', 'ventas.productos.categoria'])
            ->when(!empty($where), function ($q) use ($where) { return $q->where($where); })
            ->orderBy('id')
            ->chunk($this->chunkSize, function ($clientes) use ($file) {
                foreach ($clientes as $cliente) {
                    // Construir una fila aproximada similar a la original
                    $ventas = $cliente->ventas->last();
                    $venta_id = $ventas->id ?? '';

                    // Calcular sumas por categoría (movil=2, fija=3, avanzada=4)
                    $m_cant = $m_carf = $f_cant = $f_carf = $a_cant = $a_carf = 0;
                    if ($ventas) {
                        foreach ($ventas->productos as $item) {
                            if ($item->categoria_id === 2) {
                                $m_cant += $item->pivot->cantidad;
                                $m_carf += $item->pivot->total;
                            } elseif ($item->categoria_id === 3) {
                                $f_cant += $item->pivot->cantidad;
                                $f_carf += $item->pivot->total;
                            } elseif ($item->categoria_id === 4) {
                                $a_cant += $item->pivot->cantidad;
                                $a_carf += $item->pivot->total;
                            }
                        }
                    }

                    $comentarios = $cliente->comentarios()->latest()->take(5)->get();
                    $comentariosArray = $comentarios->toArray();
                    $textoPredeterminado = '';
                    while (count($comentariosArray) < 5) {
                        $comentariosArray[] = ['comentario' => $textoPredeterminado];
                    }

                    $row = [
                        $this->normalizeValue($cliente->id),
                        $this->normalizeValue($cliente->equipo->nombre ?? ''),
                        $this->normalizeValue($cliente->user->name ?? ''),
                        $this->normalizeValue($cliente->user->id ?? ''),
                        $this->normalizeValue($cliente->user->email ?? ''),
                        $this->normalizeValue($cliente->ruc ?? ''),
                        $this->normalizeValue($cliente->razon_social ?? ''),
                        $this->normalizeValue($cliente->ciudad ?? ''),
                        $this->normalizeValue($cliente->contactos->last()->nombre ?? ''),
                        $this->normalizeValue($cliente->contactos->last()->celular ?? ''),
                        $this->normalizeValue($cliente->contactos->last()->correo ?? ''),
                        $this->normalizeValue($cliente->movistars->last()->estadowick->nombre ?? ''),
                        $this->normalizeValue($cliente->movistars->last()->estadodito->nombre ?? ''),
                        $this->normalizeValue($cliente->movistars->last()->linea_claro ?? '0'),
                        $this->normalizeValue($cliente->movistars->last()->linea_entel ?? '0'),
                        $this->normalizeValue($cliente->movistars->last()->linea_bitel ?? '0'),
                        $this->normalizeValue($cliente->etapa->nombre ?? ''),
                        $this->normalizeValue($cliente->created_at->format('Y-m-d') ?? ''),
                        $this->normalizeValue($cliente->fecha_gestion ?? ''),
                        $this->normalizeValue($m_cant),
                        $this->normalizeValue($m_carf),
                        $this->normalizeValue($f_cant),
                        $this->normalizeValue($f_carf),
                        $this->normalizeValue($a_cant),
                        $this->normalizeValue($a_carf),
                        $this->normalizeValue($comentariosArray[0]['comentario'] ?? ''),
                        $this->normalizeValue($comentariosArray[1]['comentario'] ?? ''),
                        $this->normalizeValue($comentariosArray[2]['comentario'] ?? ''),
                        $this->normalizeValue($comentariosArray[3]['comentario'] ?? ''),
                        $this->normalizeValue($comentariosArray[4]['comentario'] ?? ''),
                        $this->normalizeValue($cliente->movistars->last()->clientetipo->nombre ?? ''),
                        $this->normalizeValue($cliente->movistars->last()->agencia->nombre ?? ''),
                    ];

                    fputcsv($file, $row);
                }
            });
    }
}
