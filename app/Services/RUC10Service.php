<?php

namespace App\Services;

use App\Repositories\RUC10Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class RUC10Service
{
    /**
     * Realiza una búsqueda individual de RUC 10 por DNI
     * Ejecuta validación y retorna resultado con estructura definida
     * 
     * @param string $dni Número de DNI
     * @return array Diccionario con resultado o error
     */
    public static function searchIndividual(string $dni): array
    {
        try {
            // Validar entrada
            self::validateDNI($dni);

            $result = RUC10Repository::searchByDni($dni);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => "No se encontraron resultados para el DNI: {$dni}",
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Búsqueda realizada exitosamente',
                'data' => $result
            ];

        } catch (ValidationException $ve) {
            Log::warning("Error de validación en búsqueda RUC 10: " . $ve->getMessage());
            return [
                'success' => false,
                'message' => $ve->getMessage(),
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error("Error en búsqueda individual RUC 10: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al realizar la búsqueda',
                'data' => null
            ];
        }
    }

    /**
     * Realiza búsqueda masiva de RUC 10 con paginación
     * 
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @param array|null $filters Filtros de búsqueda
     * @return array Resultado con datos paginados
     */
    public static function searchMassive(int $page = 1, int $perPage = 25, ?array $filters = null): array
    {
        try {
            $result = RUC10Repository::searchAllMassive($page, $perPage, $filters);

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
            Log::error("Error en búsqueda masiva RUC 10: " . $e->getMessage());
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
            [$batch, $totalCount] = RUC10Repository::searchAllForExportBatch($filters, $limit, $offset);
            return $batch;

        } catch (\Exception $e) {
            Log::error("Error obteniendo lote para exportación RUC 10: " . $e->getMessage());
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
            $result = RUC10Repository::searchAllMassive(1, 1, $filters);
            return $result['total'] ?? 0;

        } catch (\Exception $e) {
            Log::error("Error obteniendo conteo total RUC 10: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Valida formato de DNI
     * 
     * @param string $dni Número de DNI a validar
     * @throws ValidationException Si el formato es inválido
     */
    private static function validateDNI(string $dni): void
    {
        $validator = Validator::make(['dni' => $dni], [
            'dni' => 'required|string|size:8|regex:/^[0-9]{8}$/'
        ], [
            'dni.required' => 'El DNI es obligatorio',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
            'dni.regex' => 'El DNI debe contener solo números'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Obtiene estadísticas de la base de datos RUC 10
     * 
     * @return array Estadísticas generales
     */
    public static function getStatistics(): array
    {
        try {
            return RUC10Repository::getStatistics();
        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas RUC 10: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener estadísticas', 'data' => null];
        }
    }

    /**
     * Busca sugerencias de DNI basadas en texto parcial
     * 
     * @param string $query Texto de búsqueda
     * @param int $limit Límite de resultados
     * @return array Sugerencias
     */
    public static function getSuggestions(string $query, int $limit = 10): array
    {
        try {
            $suggestions = RUC10Repository::getSuggestions($query, $limit);

            return [
                'success' => true,
                'data' => $suggestions
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo sugerencias RUC 10: " . $e->getMessage());
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
            $options = RUC10Repository::getActividadEconomicaOptions($query, $limit);

            return [
                'success' => true,
                'data' => $options
            ];
        } catch (\Exception $e) {
            Log::error("Error obteniendo actividades económicas RUC 10: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener actividades económicas',
                'data' => []
            ];
        }
    }

    /**
     * Busca por número de RUC (para personas naturales)
     * 
     * @param string $ruc Número de RUC
     * @return array Resultado de búsqueda
     */
    public static function searchByRUC(string $ruc): array
    {
        try {
            // Validar que sea un RUC 10
            if (!str_starts_with($ruc, '10')) {
                return [
                    'success' => false,
                    'message' => 'El RUC debe comenzar con 10 para personas naturales',
                    'data' => null
                ];
            }

            $result = RUC10::byRuc($ruc)->first();

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
                'data' => $result->toArray()
            ];

        } catch (\Exception $e) {
            Log::error("Error en búsqueda por RUC 10: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al realizar la búsqueda',
                'data' => null
            ];
        }
    }

    /**
     * Busca por nombres completos
     * 
     * @param string $nombres Nombres completos a buscar
     * @return array Resultados encontrados
     */
    public static function searchByNames(string $nombres): array
    {
        try {
            $results = RUC10::byNombres($nombres)
                           ->limit(50)
                           ->get();

            return [
                'success' => true,
                'message' => 'Búsqueda por nombres realizada exitosamente',
                'data' => $results->toArray()
            ];

        } catch (\Exception $e) {
            Log::error("Error en búsqueda por nombres: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al realizar la búsqueda por nombres',
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
                'Estado', 'Condicion', 'Departamento', 
                'Provincia', 'Distrito', 'UBIGEO'
            ];

            if (!in_array($column, $allowedColumns)) {
                return [
                    'success' => false,
                    'message' => 'Columna no válida',
                    'options' => []
                ];
            }

            $options = RUC10Repository::getFilterValues($column);

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
