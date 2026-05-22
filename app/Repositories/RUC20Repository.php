<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RUC20Repository
{
    public static function searchByRuc(string $ruc): ?array
    {
        try {
            $conn = DB::connection('mysql_flask');
            
            // Test connection
            $testQuery = $conn->selectOne("SELECT 1 as test");
            if (!$testQuery) {
                Log::error("RUC20Repository::searchByRuc: Database connection test failed");
                return null;
            }

            $row = $conn->selectOne("SELECT id as ruc_id, RUC, Estado, Condicion, Razón_Social,
                       direccion, Tipo, Actividad_Economica_Principal,
                       NroTrab, UBIGEO, Departamento, Provincia, Distrito,
                       motivo, subsegmento_agosto, ganado_por, gerente,
                       movistar_lines, claro_lines, entel_lines,
                       competence_lines, s_m_l
                    FROM ruc20_febrero28
                    WHERE RUC = ?
                    LIMIT 1", [$ruc]);

            if (!$row) {
                Log::info("RUC20Repository::searchByRuc: No data found for RUC: " . $ruc);
                return null;
            }

            $result = (array)$row;

            $consultas = $conn->select("SELECT id as consulta_gen_id, ruc, nombre_razon_social, estado_contribuyente,
                       condicion_contribuyente, actividades_economicas,
                       cant_trabajadores, cant_anexos, fecha_consulta,
                       valido, error
                    FROM consultas_sunat
                    WHERE ruc = ?
                    ORDER BY fecha_consulta DESC
                    LIMIT 5", [$ruc]);

            $result['consultas_sunat'] = array_map(fn($r) => (array)$r, $consultas);
            $ultimaConsulta = $result['consultas_sunat'][0] ?? null;
            $consultaId = $ultimaConsulta['consulta_gen_id'] ?? null;

            $representantes = [];
            if ($consultaId) {
                $reps = $conn->select("SELECT id, tipo_documento, numero_documento, nombre, cargo
                        FROM representantes_legales
                        WHERE consulta_id = ?", [$consultaId]);

                foreach ($reps as $rep) {
                    $repArr = (array)$rep;
                    $phone = $conn->selectOne("SELECT documento, lista_movistar, lista_claro,
                            lista_entel, lista_otros
                        FROM super_tabla_telefonos
                        WHERE documento = ?
                        LIMIT 1", [$repArr['numero_documento']]);

                    $repArr['telefonos'] = $phone ? (array)$phone : [];
                    $representantes[] = $repArr;
                }
            }

            $result['representantes'] = $representantes;
            return $result;
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::searchByRuc: " . $e->getMessage());
            return null;
        }
    }

    public static function searchAllMassive(int $page = 1, int $perPage = 25, ?array $search_filter = null): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            
            // Test connection
            $testQuery = $conn->selectOne("SELECT 1 as test");
            if (!$testQuery) {
                Log::error("RUC20Repository::searchAllMassive: Database connection test failed");
                return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'pages' => 0];
            }
            
            $where = [];
            $bindings = [];

            if (!empty($search_filter)) {
                if (!empty($search_filter['ruc'])) {
                    $where[] = 'RUC = ?';
                    $bindings[] = $search_filter['ruc'];
                }
                if (!empty($search_filter['razon_social'])) {
                    $where[] = 'Razón_Social LIKE ?';
                    $bindings[] = '%' . $search_filter['razon_social'] . '%';
                }
                if (!empty($search_filter['estado'])) {
                    $where[] = 'Estado = ?';
                    $bindings[] = $search_filter['estado'];
                }
                if (!empty($search_filter['condicion'])) {
                    $where[] = 'Condicion = ?';
                    $bindings[] = $search_filter['condicion'];
                }
                if (!empty($search_filter['departamento'])) {
                    $where[] = 'Departamento = ?';
                    $bindings[] = $search_filter['departamento'];
                }
                if (!empty($search_filter['provincia'])) {
                    $where[] = 'Provincia = ?';
                    $bindings[] = $search_filter['provincia'];
                }
                if (!empty($search_filter['distrito'])) {
                    $where[] = 'Distrito = ?';
                    $bindings[] = $search_filter['distrito'];
                }
                if (!empty($search_filter['actividad_economica'])) {
                    $values = is_array($search_filter['actividad_economica']) ? $search_filter['actividad_economica'] : [$search_filter['actividad_economica']];
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $where[] = "Actividad_Economica_Principal IN ($placeholders)";
                    foreach ($values as $value) {
                        $bindings[] = $value;
                    }
                }

                if (!empty($search_filter['min_trabajadores'])) {
                    $rango = $search_filter['min_trabajadores'];
                    $subqueryBase = "EXISTS (SELECT 1 FROM consultas_sunat cs " .
                        "WHERE cs.ruc = ruc20_febrero28.RUC " .
                        "AND cs.id = (SELECT id FROM consultas_sunat " .
                        "WHERE ruc = ruc20_febrero28.RUC " .
                        "ORDER BY fecha_consulta DESC LIMIT 1) ";

                    if (str_contains($rango, '-')) {
                        [$minVal, $maxVal] = explode('-', $rango, 2);
                        $where[] = $subqueryBase . "AND cs.cant_trabajadores BETWEEN ? AND ?)";
                        $bindings[] = intval($minVal);
                        $bindings[] = intval($maxVal);
                    } elseif (str_ends_with($rango, '+')) {
                        $minVal = intval(str_replace('+', '', $rango));
                        $where[] = $subqueryBase . "AND cs.cant_trabajadores >= ?)";
                        $bindings[] = $minVal;
                    }
                }

                if (!empty($search_filter['min_anexos'])) {
                    $rango1 = $search_filter['min_anexos'];
                    $subqueryBase = "EXISTS (SELECT 1 FROM consultas_sunat cs " .
                        "WHERE cs.ruc = ruc20_febrero28.RUC " .
                        "AND cs.id = (SELECT id FROM consultas_sunat " .
                        "WHERE ruc = ruc20_febrero28.RUC " .
                        "ORDER BY fecha_consulta DESC LIMIT 1) ";

                    if (str_contains($rango1, '-')) {
                        [$minVal, $maxVal] = explode('-', $rango1, 2);
                        $where[] = $subqueryBase . "AND cs.cant_anexos BETWEEN ? AND ?)";
                        $bindings[] = intval($minVal);
                        $bindings[] = intval($maxVal);
                    } elseif (str_ends_with($rango1, '+')) {
                        $minVal = intval(str_replace('+', '', $rango1));
                        $where[] = $subqueryBase . "AND cs.cant_anexos >= ?)";
                        $bindings[] = $minVal;
                    }
                }
            }

            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $totalRow = $conn->selectOne("SELECT COUNT(*) AS total FROM ruc20_febrero28 $whereClause", $bindings);
            $total = $totalRow ? intval($totalRow->total) : 0;
            $offset = ($page - 1) * $perPage;
            $bindingsForData = array_merge($bindings, [$perPage, $offset]);

            $rows = $conn->select("SELECT id as ruc_id, RUC, Estado, Condicion, Razón_Social,
                        direccion, Tipo, Actividad_Economica_Principal,
                        NroTrab, UBIGEO, Departamento, Provincia, Distrito,
                        motivo, subsegmento_agosto, ganado_por, gerente,
                        movistar_lines, claro_lines, entel_lines,
                        competence_lines, s_m_l
                    FROM ruc20_febrero28
                    $whereClause
                    ORDER BY id ASC
                    LIMIT ? OFFSET ?", $bindingsForData);

            $results = array_map(fn($r) => (array)$r, $rows);
            if (empty($results)) {
                return ['data' => [], 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => $total ? intdiv($total + $perPage - 1, $perPage) : 0];
            }

            $rucList = array_values(array_unique(array_column($results, 'RUC')));
            $placeholders = implode(',', array_fill(0, count($rucList), '?'));

            $consultasSql = "SELECT * FROM (SELECT id as consulta_id, ruc, nombre_razon_social, estado_contribuyente,
                        condicion_contribuyente, actividades_economicas, cant_trabajadores,
                        cant_anexos, fecha_consulta, valido, error,
                        ROW_NUMBER() OVER (PARTITION BY ruc ORDER BY fecha_consulta DESC) as rn
                    FROM consultas_sunat
                    WHERE ruc IN ($placeholders)
                ) t
                WHERE rn = 1";

            $consultas = $conn->select($consultasSql, $rucList);
            $consByRuc = [];
            $consultaIds = [];
            foreach ($consultas as $c) {
                $row = (array)$c;
                $consByRuc[$row['ruc']] = $row;
                $consultaIds[] = $row['consulta_id'];
            }

            $repsByConsulta = [];
            $allReps = [];
            if (!empty($consultaIds)) {
                $repPlaceholders = implode(',', array_fill(0, count($consultaIds), '?'));
                $repsRows = $conn->select("SELECT consulta_id, id, tipo_documento, numero_documento, nombre, cargo
                        FROM representantes_legales
                        WHERE consulta_id IN ($repPlaceholders)", $consultaIds);

                foreach ($repsRows as $rep) {
                    $repRow = (array)$rep;
                    $allReps[] = $repRow;
                    $repsByConsulta[$repRow['consulta_id']][] = $repRow;
                }

                $docs = array_values(array_unique(array_filter(array_column($allReps, 'numero_documento'))));
                if (!empty($docs)) {
                    $docPlaceholders = implode(',', array_fill(0, count($docs), '?'));
                    $telRows = $conn->select("SELECT documento, lista_movistar, lista_claro, lista_entel, lista_otros
                            FROM super_tabla_telefonos
                            WHERE documento IN ($docPlaceholders)", $docs);

                    $telMap = [];
                    foreach ($telRows as $tel) {
                        $telRow = (array)$tel;
                        $telMap[$telRow['documento']] = $telRow;
                    }

                    foreach ($allReps as &$rep) {
                        $phone = $telMap[$rep['numero_documento']] ?? [];
                        $rep['telefonos'] = $phone;
                    }
                    unset($rep);

                    // Rebuild the consulta-indexed representative list with phone data included
                    $repsByConsulta = [];
                    foreach ($allReps as $rep) {
                        $repsByConsulta[$rep['consulta_id']][] = $rep;
                    }
                }
            }

            $final = [];
            $maxReps = 5;
            foreach ($results as $row) {
                $ruc = $row['RUC'];
                $consulta = $consByRuc[$ruc] ?? null;

                if ($consulta) {
                    $row['consulta_nombre_razon_social'] = $consulta['nombre_razon_social'] ?? null;
                    $row['consulta_estado_contribuyente'] = $consulta['estado_contribuyente'] ?? null;
                    $row['consulta_condicion_contribuyente'] = $consulta['condicion_contribuyente'] ?? null;
                    $row['consulta_actividades_economicas'] = $consulta['actividades_economicas'] ?? null;
                    $row['consulta_cant_trabajadores'] = $consulta['cant_trabajadores'] ?? null;
                    $row['consulta_cant_anexos'] = $consulta['cant_anexos'] ?? null;
                    $row['consulta_fecha_consulta'] = $consulta['fecha_consulta'] ?? null;
                    $row['consulta_valido'] = $consulta['valido'] ?? null;
                    $row['consulta_error'] = $consulta['error'] ?? null;

                    $reps = $repsByConsulta[$consulta['consulta_id']] ?? [];
                } else {
                    $row['consulta_nombre_razon_social'] = null;
                    $row['consulta_estado_contribuyente'] = null;
                    $row['consulta_condicion_contribuyente'] = null;
                    $row['consulta_actividades_economicas'] = null;
                    $row['consulta_cant_trabajadores'] = null;
                    $row['consulta_cant_anexos'] = null;
                    $row['consulta_fecha_consulta'] = null;
                    $row['consulta_valido'] = null;
                    $row['consulta_error'] = null;
                    $reps = [];
                }

                $row['representantes'] = array_map(fn($rep) => [
                    'tipo_documento' => $rep['tipo_documento'] ?? null,
                    'numero_documento' => $rep['numero_documento'] ?? null,
                    'nombre' => $rep['nombre'] ?? null,
                    'cargo' => $rep['cargo'] ?? null,
                    'telefonos' => $rep['telefonos'] ?? []
                ], $reps);

                for ($i = 0; $i < $maxReps; $i++) {
                    if (isset($reps[$i])) {
                        $rep = $reps[$i];
                        $row["rep_" . ($i + 1) . "_tipo_documento"] = $rep['tipo_documento'] ?? null;
                        $row["rep_" . ($i + 1) . "_numero_documento"] = $rep['numero_documento'] ?? null;
                        $row["rep_" . ($i + 1) . "_nombre"] = $rep['nombre'] ?? null;
                        $row["rep_" . ($i + 1) . "_cargo"] = $rep['cargo'] ?? null;
                        $row["rep_" . ($i + 1) . "_movistar"] = $rep['telefonos']['lista_movistar'] ?? null;
                        $row["rep_" . ($i + 1) . "_claro"] = $rep['telefonos']['lista_claro'] ?? null;
                        $row["rep_" . ($i + 1) . "_entel"] = $rep['telefonos']['lista_entel'] ?? null;
                        $row["rep_" . ($i + 1) . "_otros"] = $rep['telefonos']['lista_otros'] ?? null;
                    } else {
                        $row["rep_" . ($i + 1) . "_tipo_documento"] = null;
                        $row["rep_" . ($i + 1) . "_numero_documento"] = null;
                        $row["rep_" . ($i + 1) . "_nombre"] = null;
                        $row["rep_" . ($i + 1) . "_cargo"] = null;
                        $row["rep_" . ($i + 1) . "_movistar"] = null;
                        $row["rep_" . ($i + 1) . "_claro"] = null;
                        $row["rep_" . ($i + 1) . "_entel"] = null;
                        $row["rep_" . ($i + 1) . "_otros"] = null;
                    }
                }

                $final[] = $row;
            }

            return [
                'data' => $final,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => $total ? intdiv($total + $perPage - 1, $perPage) : 0
            ];
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::searchAllMassive: " . $e->getMessage());
            return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'pages' => 0];
        }
    }

    public static function searchAllForExportBatch(?array $search_filter = null, int $limit = 10000, int $offset = 0): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $where = [];
            $bindings = [];

            if (!empty($search_filter)) {
                if (!empty($search_filter['ruc'])) {
                    $where[] = 'RUC = ?';
                    $bindings[] = $search_filter['ruc'];
                }
                if (!empty($search_filter['razon_social'])) {
                    $where[] = 'Razón_Social LIKE ?';
                    $bindings[] = '%' . $search_filter['razon_social'] . '%';
                }
                if (!empty($search_filter['estado'])) {
                    $where[] = 'Estado = ?';
                    $bindings[] = $search_filter['estado'];
                }
                if (!empty($search_filter['condicion'])) {
                    $where[] = 'Condicion = ?';
                    $bindings[] = $search_filter['condicion'];
                }
                if (!empty($search_filter['departamento'])) {
                    $where[] = 'Departamento = ?';
                    $bindings[] = $search_filter['departamento'];
                }
                if (!empty($search_filter['provincia'])) {
                    $where[] = 'Provincia = ?';
                    $bindings[] = $search_filter['provincia'];
                }
                if (!empty($search_filter['distrito'])) {
                    $where[] = 'Distrito = ?';
                    $bindings[] = $search_filter['distrito'];
                }
                if (!empty($search_filter['actividad_economica'])) {
                    $values = is_array($search_filter['actividad_economica']) ? $search_filter['actividad_economica'] : [$search_filter['actividad_economica']];
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $where[] = "Actividad_Economica_Principal IN ($placeholders)";
                    foreach ($values as $value) {
                        $bindings[] = $value;
                    }
                }

                if (!empty($search_filter['min_trabajadores'])) {
                    $rango = $search_filter['min_trabajadores'];
                    $subqueryBase = "EXISTS (SELECT 1 FROM consultas_sunat cs " .
                        "WHERE cs.ruc = ruc20_febrero28.RUC " .
                        "AND cs.id = (SELECT id FROM consultas_sunat " .
                        "WHERE ruc = ruc20_febrero28.RUC " .
                        "ORDER BY fecha_consulta DESC LIMIT 1) ";

                    if (str_contains($rango, '-')) {
                        [$minVal, $maxVal] = explode('-', $rango, 2);
                        $where[] = $subqueryBase . "AND cs.cant_trabajadores BETWEEN ? AND ?)";
                        $bindings[] = intval($minVal);
                        $bindings[] = intval($maxVal);
                    } elseif (str_ends_with($rango, '+')) {
                        $minVal = intval(str_replace('+', '', $rango));
                        $where[] = $subqueryBase . "AND cs.cant_trabajadores >= ?)";
                        $bindings[] = $minVal;
                    }
                }

                if (!empty($search_filter['min_anexos'])) {
                    $rango1 = $search_filter['min_anexos'];
                    $subqueryBase = "EXISTS (SELECT 1 FROM consultas_sunat cs " .
                        "WHERE cs.ruc = ruc20_febrero28.RUC " .
                        "AND cs.id = (SELECT id FROM consultas_sunat " .
                        "WHERE ruc = ruc20_febrero28.RUC " .
                        "ORDER BY fecha_consulta DESC LIMIT 1) ";

                    if (str_contains($rango1, '-')) {
                        [$minVal, $maxVal] = explode('-', $rango1, 2);
                        $where[] = $subqueryBase . "AND cs.cant_anexos BETWEEN ? AND ?)";
                        $bindings[] = intval($minVal);
                        $bindings[] = intval($maxVal);
                    } elseif (str_ends_with($rango1, '+')) {
                        $minVal = intval(str_replace('+', '', $rango1));
                        $where[] = $subqueryBase . "AND cs.cant_anexos >= ?)";
                        $bindings[] = $minVal;
                    }
                }
            }

            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $totalRow = $conn->selectOne("SELECT COUNT(*) AS total FROM ruc20_febrero28 $whereClause", $bindings);
            $totalCount = $totalRow ? intval($totalRow->total) : 0;
            if ($totalCount === 0) {
                return [[], 0];
            }

            $rows = $conn->select("SELECT id as ruc_id, RUC, Estado, Condicion, Razón_Social,
                        direccion, Tipo, Actividad_Economica_Principal,
                        UBIGEO, Departamento, Provincia, Distrito,
                        motivo, subsegmento_agosto, ganado_por, gerente,
                        movistar_lines, claro_lines, entel_lines,
                        competence_lines, s_m_l
                    FROM ruc20_febrero28
                    $whereClause
                    ORDER BY id ASC
                    LIMIT ? OFFSET ?", array_merge($bindings, [$limit, $offset]));

            $results = array_map(fn($r) => (array)$r, $rows);
            if (empty($results)) {
                return [[], $totalCount];
            }

            $rucList = array_values(array_unique(array_column($results, 'RUC')));
            $placeholders = implode(',', array_fill(0, count($rucList), '?'));

            $consultasSql = "SELECT * FROM (SELECT id as consulta_id, ruc, nombre_razon_social, estado_contribuyente,
                        condicion_contribuyente, actividades_economicas, cant_trabajadores,
                        cant_anexos, fecha_consulta, valido, error,
                        ROW_NUMBER() OVER (PARTITION BY ruc ORDER BY fecha_consulta DESC) as rn
                    FROM consultas_sunat
                    WHERE ruc IN ($placeholders)
                ) t
                WHERE rn = 1";

            $consultas = $conn->select($consultasSql, $rucList);
            $consByRuc = [];
            $consultaIds = [];
            foreach ($consultas as $c) {
                $row = (array)$c;
                $consByRuc[$row['ruc']] = $row;
                $consultaIds[] = $row['consulta_id'];
            }

            $repsByConsulta = [];
            $allReps = [];
            if (!empty($consultaIds)) {
                $repPlaceholders = implode(',', array_fill(0, count($consultaIds), '?'));
                $repsRows = $conn->select("SELECT consulta_id, id, tipo_documento, numero_documento, nombre, cargo
                        FROM representantes_legales
                        WHERE consulta_id IN ($repPlaceholders)", $consultaIds);

                foreach ($repsRows as $rep) {
                    $repRow = (array)$rep;
                    $allReps[] = $repRow;
                    $repsByConsulta[$repRow['consulta_id']][] = $repRow;
                }

                $docs = array_values(array_unique(array_filter(array_column($allReps, 'numero_documento'))));
                if (!empty($docs)) {
                    $docPlaceholders = implode(',', array_fill(0, count($docs), '?'));
                    $telRows = $conn->select("SELECT documento, lista_movistar, lista_claro, lista_entel, lista_otros
                            FROM super_tabla_telefonos
                            WHERE documento IN ($docPlaceholders)", $docs);

                    $telMap = [];
                    foreach ($telRows as $tel) {
                        $telRow = (array)$tel;
                        $telMap[$telRow['documento']] = $telRow;
                    }

                    foreach ($allReps as &$rep) {
                        $rep['telefonos'] = $telMap[$rep['numero_documento']] ?? [];
                    }
                    unset($rep);
                }
            }

            $final = [];
            $maxReps = 5;
            foreach ($results as $row) {
                $ruc = $row['RUC'];
                $consulta = $consByRuc[$ruc] ?? null;

                if ($consulta) {
                    $row['consulta_nombre_razon_social'] = $consulta['nombre_razon_social'] ?? null;
                    $row['consulta_estado_contribuyente'] = $consulta['estado_contribuyente'] ?? null;
                    $row['consulta_condicion_contribuyente'] = $consulta['condicion_contribuyente'] ?? null;
                    $row['consulta_actividades_economicas'] = $consulta['actividades_economicas'] ?? null;
                    $row['consulta_cant_trabajadores'] = $consulta['cant_trabajadores'] ?? null;
                    $row['consulta_cant_anexos'] = $consulta['cant_anexos'] ?? null;
                    $row['consulta_fecha_consulta'] = $consulta['fecha_consulta'] ?? null;
                    $row['consulta_valido'] = $consulta['valido'] ?? null;
                    $row['consulta_error'] = $consulta['error'] ?? null;

                    $reps = $repsByConsulta[$consulta['consulta_id']] ?? [];
                } else {
                    $row['consulta_nombre_razon_social'] = null;
                    $row['consulta_estado_contribuyente'] = null;
                    $row['consulta_condicion_contribuyente'] = null;
                    $row['consulta_actividades_economicas'] = null;
                    $row['consulta_cant_trabajadores'] = null;
                    $row['consulta_cant_anexos'] = null;
                    $row['consulta_fecha_consulta'] = null;
                    $row['consulta_valido'] = null;
                    $row['consulta_error'] = null;
                    $reps = [];
                }

                for ($i = 0; $i < $maxReps; $i++) {
                    if (isset($reps[$i])) {
                        $rep = $reps[$i];
                        $row["rep_" . ($i + 1) . "_tipo_documento"] = $rep['tipo_documento'] ?? null;
                        $row["rep_" . ($i + 1) . "_numero_documento"] = $rep['numero_documento'] ?? null;
                        $row["rep_" . ($i + 1) . "_nombre"] = $rep['nombre'] ?? null;
                        $row["rep_" . ($i + 1) . "_cargo"] = $rep['cargo'] ?? null;
                        $row["rep_" . ($i + 1) . "_movistar"] = $rep['telefonos']['lista_movistar'] ?? null;
                        $row["rep_" . ($i + 1) . "_claro"] = $rep['telefonos']['lista_claro'] ?? null;
                        $row["rep_" . ($i + 1) . "_entel"] = $rep['telefonos']['lista_entel'] ?? null;
                        $row["rep_" . ($i + 1) . "_otros"] = $rep['telefonos']['lista_otros'] ?? null;
                    } else {
                        $row["rep_" . ($i + 1) . "_tipo_documento"] = null;
                        $row["rep_" . ($i + 1) . "_numero_documento"] = null;
                        $row["rep_" . ($i + 1) . "_nombre"] = null;
                        $row["rep_" . ($i + 1) . "_cargo"] = null;
                        $row["rep_" . ($i + 1) . "_movistar"] = null;
                        $row["rep_" . ($i + 1) . "_claro"] = null;
                        $row["rep_" . ($i + 1) . "_entel"] = null;
                        $row["rep_" . ($i + 1) . "_otros"] = null;
                    }
                }

                $final[] = $row;
            }

            return [$final, $totalCount];
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::searchAllForExportBatch: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getFilterValues(string $column): array
    {
        $allowed = ['Estado', 'Condicion', 'Tipo', 'Departamento', 'Provincia', 'Distrito', 'motivo', 'subsegmento_agosto'];
        if (!in_array($column, $allowed, true)) {
            return [];
        }

        try {
            $conn = DB::connection('mysql_flask');
            $rows = $conn->select("SELECT DISTINCT `$column` as value FROM ruc20_febrero28 WHERE `$column` IS NOT NULL AND `$column` != '' ORDER BY `$column` ASC LIMIT 1000");
            return array_values(array_filter(array_map(fn($r) => $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::getFilterValues: " . $e->getMessage());
            return [];
        }
    }

    public static function getProvinciasByDepartamento(string $departamento): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $rows = $conn->select("SELECT DISTINCT Provincia as value FROM ruc20_febrero28 WHERE Departamento = ? AND Provincia IS NOT NULL AND Provincia != '' ORDER BY Provincia ASC LIMIT 1000", [$departamento]);
            return array_values(array_filter(array_map(fn($r) => $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::getProvinciasByDepartamento: " . $e->getMessage());
            return [];
        }
    }

    public static function getDistritosByProvincia(string $departamento, string $provincia): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $rows = $conn->select("SELECT DISTINCT Distrito as value FROM ruc20_febrero28 WHERE Departamento = ? AND Provincia = ? AND Distrito IS NOT NULL AND Distrito != '' ORDER BY Distrito ASC LIMIT 1000", [$departamento, $provincia]);
            return array_values(array_filter(array_map(fn($r) => $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::getDistritosByProvincia: " . $e->getMessage());
            return [];
        }
    }

    public static function getSuggestions(string $query, int $limit = 10): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $q = '%' . $query . '%';
            $rows = $conn->select("SELECT RUC, `Razón_Social` as razon_social FROM ruc20_febrero28 WHERE RUC LIKE ? OR `Razón_Social` LIKE ? LIMIT ?", [$q, $q, $limit]);
            return array_map(fn($r) => (array)$r, $rows);
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::getSuggestions: " . $e->getMessage());
            return [];
        }
    }

    public static function getActividadEconomicaOptions(string $query = '', int $limit = 50): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            if (trim($query) === '') {
                $rows = $conn->select("SELECT DISTINCT Actividad_Economica_Principal as value FROM ruc20_febrero28 WHERE Actividad_Economica_Principal IS NOT NULL AND Actividad_Economica_Principal != '' ORDER BY Actividad_Economica_Principal ASC LIMIT ?", [$limit]);
            } else {
                $q = '%' . $query . '%';
                $rows = $conn->select("SELECT DISTINCT Actividad_Economica_Principal as value FROM ruc20_febrero28 WHERE Actividad_Economica_Principal IS NOT NULL AND Actividad_Economica_Principal != '' AND Actividad_Economica_Principal LIKE ? ORDER BY Actividad_Economica_Principal ASC LIMIT ?", [$q, $limit]);
            }
            return array_values(array_filter(array_map(fn($r) => $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::getActividadEconomicaOptions: " . $e->getMessage());
            return [];
        }
    }

    public static function getStatistics(): array
    {
        try {
            $conn = DB::connection('mysql_flask');

            $totalRow = $conn->selectOne("SELECT COUNT(*) as total FROM ruc20_febrero28");
            $total = $totalRow ? intval($totalRow->total) : 0;

            $activosRow = $conn->selectOne("SELECT SUM(CASE WHEN Estado = 'ACTIVO' THEN 1 ELSE 0 END) as activos FROM ruc20_febrero28");
            $activos = $activosRow ? intval($activosRow->activos) : 0;
            $inactivos = $total - $activos;

            $hasFuente = Schema::connection('mysql_flask')->hasColumn('ruc20_febrero28', 'fuente_datos');
            $hasSource = Schema::connection('mysql_flask')->hasColumn('ruc20_febrero28', 'source');

            $datosReniec = 0; $datosSunat = 0;
            if ($hasFuente) {
                $r = $conn->selectOne("SELECT SUM(CASE WHEN fuente_datos = 'reniec' THEN 1 ELSE 0 END) as reniec, SUM(CASE WHEN fuente_datos = 'sunat' THEN 1 ELSE 0 END) as sunat FROM ruc20_febrero28");
                $datosReniec = $r->reniec ?? 0; $datosSunat = $r->sunat ?? 0;
            } elseif ($hasSource) {
                $r = $conn->selectOne("SELECT SUM(CASE WHEN `source` = 'reniec' THEN 1 ELSE 0 END) as reniec, SUM(CASE WHEN `source` = 'sunat' THEN 1 ELSE 0 END) as sunat FROM ruc20_febrero28");
                $datosReniec = $r->reniec ?? 0; $datosSunat = $r->sunat ?? 0;
            }

            $top = $conn->select("SELECT Departamento as departamento, COUNT(*) as total FROM ruc20_febrero28 WHERE Departamento IS NOT NULL AND Departamento != '' GROUP BY Departamento ORDER BY total DESC LIMIT 10");
            $topArr = array_map(fn($r)=>(array)$r, $top);

            return [
                'success' => true,
                'data' => [
                    'total_registros' => $total,
                    'activos' => $activos,
                    'inactivos' => $inactivos,
                    'porcentaje_activos' => $total > 0 ? round(($activos / $total) * 100, 2) : 0,
                    'top_departamentos' => $topArr,
                    'datos_reniec' => intval($datosReniec),
                    'datos_sunat' => intval($datosSunat)
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Error RUC20Repository::getStatistics: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener estadísticas', 'data' => null];
        }
    }
}
