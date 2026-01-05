<?php

namespace App\Exports;

use App\Helpers\Helpers;
use App\Models\Exportcliente;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndotechFunnelExport
{
    protected $filtro;

    protected $user;

    public function __construct($filtro, $user)
    {
        $this->filtro = $filtro;
        $this->user = $user;
    }

    public function query()
    {
        $where = Helpers::filtroExportCliente(json_decode($this->filtro), $this->user);

        // Subconsulta con filtros
        $subquery = Exportcliente::query()
            ->selectRaw('MAX(id) as id') // Último registro por RUC
            ->where($where) // Aplicar filtros (sede, equipo, ejecutivo, fechas, etc.)
            ->groupBy('ruc');

        // Consulta principal con filtros y subconsulta
        return Exportcliente::query()
            ->whereIn('id', $subquery) // Filtra por los IDs de la subconsulta
            ->where($where) // Aplicar filtros nuevamente (opcional, dependiendo de la lógica de Helpers)
            ->orderBy('ruc'); // Ordenar por RUC (opcional)
    }

    public function headings(): array
    {
        return [
            'Equipo',
            'Ejecutivo',
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

    public function map($cliente): array
    {
        return [
            $cliente->ejecutivo_equipo,
            $cliente->ejecutivo,
            $cliente->ruc,
            $cliente->razon_social,
            $cliente->ciudad,
            $cliente->contacto,
            $cliente->contacto_celular,
            $cliente->contacto_email,
            $cliente->estado_wick,
            $cliente->estado_dito,
            $cliente->lineas_claro,
            $cliente->lineas_entel,
            $cliente->lineas_bitel,
            $cliente->etapa,
            $cliente->fecha_creacion,
            $cliente->fecha_ultimo_contacto,
            $cliente->producto_categoria_1,
            $cliente->producto_categoria_1_total,
            $cliente->producto_categoria_2,
            $cliente->producto_categoria_2_total,
            $cliente->producto_categoria_3,
            $cliente->producto_categoria_3_total,
            $cliente->comentario_5,
            $cliente->comentario_4,
            $cliente->comentario_3,
            $cliente->comentario_2,
            $cliente->comentario_1,
            $cliente->cliente_tipo,
            $cliente->agencia,
        ];
    }

    public function exportToCsv(): StreamedResponse
    {
        $headers = $this->headings();

        $callback = function () use ($headers) {
            // Crear el archivo con soporte para UTF-8 y BOM
            $file = fopen('php://output', 'w');

            // Agregar el BOM (Byte Order Mark) para UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Escribir las cabeceras
            fputcsv($file, $headers);

            // Escribir los datos
            $this->query()->chunk(1000, function ($clientes) use ($file) {
                foreach ($clientes as $cliente) {
                    $cliente->ejecutivo_equipo = $cliente->user?->equipos->last()?->nombre;

                    $row = $this->map($cliente);
                    // Convertir cada campo a UTF-8 si es necesario
                    $row = array_map(function ($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $row);
                    fputcsv($file, $row);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8', // Especificar UTF-8
            'Content-Disposition' => 'attachment; filename="IndotechFunnelExport.csv"',
        ]);
    }
}
