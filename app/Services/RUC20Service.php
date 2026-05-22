<?php

namespace App\Services;

use App\Repositories\RUC20Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class RUC20Service
{
    /**
     * Realiza una búsqueda individual de RUC 20
     * Ejecuta validación y retorna resultado con estructura definida
     * 
     * @param string $ruc Número de RUC
     * @return array Diccionario con resultado o error
     */
    public static function searchIndividual(string $ruc): array
    {
        try {
            // Validar entrada
            self::validateRUC20($ruc);

            $result = RUC20Repository::searchByRuc($ruc);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => "No se encontraron resultados para el RUC: {$ruc}",
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Búsqueda realizada exitosamente',
                'data' => $result
            ];

        } catch (ValidationException $ve) {
            Log::warning("Error de validación en búsqueda RUC 20: " . $ve->getMessage());
            return [
                'success' => false,
                'message' => $ve->getMessage(),
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error("Error en búsqueda individual RUC 20: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al realizar la búsqueda',
                'data' => null
            ];
        }
    }

    /**
     * Realiza búsqueda masiva de RUC 20 con paginación
     * 
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @param array|null $filters Filtros de búsqueda
     * @return array Resultado con datos paginados
     */
    public static function searchMassive(int $page = 1, int $perPage = 25, ?array $filters = null): array
    {
        try {
            $result = RUC20Repository::searchAllMassive($page, $perPage, $filters);

            return [
                'success' => true,
                'message' => 'Búsqueda masiva realizada exitosamente',
                'data' => $result['data'],
                'pagination' => [
                    'page' => $result['page'],
                    'per_page' => $result['per_page'],
                    'total' => $result['total'],
                    'total_pages' => $result['pages'],
                    'has_next' => $result['page'] < $result['pages'],
                    'has_prev' => $result['page'] > 1
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Error en búsqueda masiva RUC 20: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al realizar la búsqueda masiva',
                'data' => null,
                'pagination' => null
            ];
        }
    }

    /**
     * Obtiene un lote de datos para exportación
     * 
     * @param array $filters Filtros de búsqueda
     * @param int $limit Límite de registros
     * @param int $offset Desplazamiento
     * @return array Lote de datos
     */
    public static function getBatchForExport(array $filters = [], int $limit = 10000, int $offset = 0): array
    {
        try {
            [$batch, $totalCount] = RUC20Repository::searchAllForExportBatch($filters, $limit, $offset);
            return $batch;

        } catch (\Exception $e) {
            Log::error("Error obteniendo lote para exportación RUC 20: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene conteo total de registros con filtros aplicados
     * 
     * @param array $filters Filtros de búsqueda
     * @return int Total de registros
     */
    public static function getTotalCount(array $filters = []): int
    {
        try {
            $result = RUC20Repository::searchAllMassive(1, 1, $filters);
            return $result['total'] ?? 0;

        } catch (\Exception $e) {
            Log::error("Error obteniendo conteo total RUC 20: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Valida formato de RUC 20
     * 
     * @param string $ruc Número de RUC a validar
     * @throws ValidationException Si el formato es inválido
     */
    private static function validateRUC20(string $ruc): void
    {
        $validator = Validator::make(['ruc' => $ruc], [
            'ruc' => 'required|string|size:11|regex:/^[0-9]{11}$/'
        ], [
            'ruc.required' => 'El RUC es obligatorio',
            'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
            'ruc.regex' => 'El RUC debe contener solo números'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validar que comience con 20 (RUC tipo 20)
        if (!str_starts_with($ruc, '20')) {
            throw new ValidationException(
                Validator::make([], [])
                    ->errors()
                    ->add('ruc', 'El RUC debe comenzar con 20 para personas jurídicas')
            );
        }
    }

    /**
     * Obtiene estadísticas de la base de datos RUC 20
     * 
     * @return array Estadísticas generales
     */
    public static function getStatistics(): array
    {
        try {
            return RUC20Repository::getStatistics();
        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas RUC 20: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'data' => null
            ];
        }
    }

    /**
     * Busca sugerencias de RUC basadas en texto parcial
     * 
     * @param string $query Texto de búsqueda
     * @param int $limit Límite de resultados
     * @return array Sugerencias
     */
    public static function getSuggestions(string $query, int $limit = 10): array
    {
        try {
            $suggestions = RUC20Repository::getSuggestions($query, $limit);

            return [
                'success' => true,
                'data' => $suggestions
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo sugerencias RUC 20: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener sugerencias',
                'data' => []
            ];
        }
    }

    /**
     * Obtiene actividades económicas para filtros de búsqueda masiva
     */
    public static function getActividadEconomicaOptions(?string $query = '', int $limit = 50): array
    {
        try {
            $query = trim((string) $query);
            $options = RUC20Repository::getActividadEconomicaOptions($query, $limit);

            return [
                'success' => true,
                'data' => $options
            ];
        } catch (\Exception $e) {
            Log::error("Error obteniendo actividades económicas RUC 20: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener actividades económicas',
                'data' => []
            ];
        }
    }

    /**
     * Obtiene opciones de filtro para una columna
     * 
     * @param string $column Nombre de la columna
     * @return array Diccionario con opciones o error
     */
    public static function getFilterOptions(string $column): array
    {
        try {
            $allowedColumns = [
                'Estado', 'Condicion', 'Tipo', 'Departamento', 
                'Provincia', 'Distrito', 'motivo', 'subsegmento_agosto'
            ];

            if (!in_array($column, $allowedColumns)) {
                return [
                    'success' => false,
                    'message' => 'Columna no válida',
                    'options' => []
                ];
            }

            $options = RUC20Repository::getFilterValues($column);

            return [
                'success' => true,
                'message' => 'Opciones obtenidas exitosamente',
                'options' => $options
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo opciones de filtro: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener opciones',
                'options' => []
            ];
        }
    }
}
