<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RUC10Repository
{
    public static function searchByDni(string $dni): ?array
    {
        try {
            $conn = DB::connection('mysql_flask');
            
            // Test connection
            $testQuery = $conn->selectOne("SELECT 1 as test");
            if (!$testQuery) {
                Log::error("RUC10Repository::searchByDni: Database connection test failed");
                return null;
            }

            $row = $conn->selectOne("
                SELECT RUC, Estado, Condicion, Actividad_Economica_Principal,
                       UBIGEO, Departamento, Provincia, Distrito,
                       Razón_Social, direccion, dni
                FROM ruc10_febrero28
                WHERE dni = ?
                LIMIT 1
            ", [$dni]);

            if (!$row) {
                // fallback to reniec
                $reniec = $conn->selectOne("
                    SELECT dni, ap_pat, ap_mat, nombres, fecha_nac, fch_inscripcion,
                           fch_emision, fch_caducidad, ubigeo_nac, ubigeo_dir,
                           direccion, sexo, est_civil, dig_ruc, madre, padre
                    FROM reniec
                    WHERE dni = ?
                    LIMIT 1
                ", [$dni]);

                if (!$reniec) {
                    Log::info("RUC10Repository::searchByDni: No data found for DNI: " . $dni);
                    return null;
                }

                return [
                    'RUC' => null,
                    'Estado' => null,
                    'Condicion' => null,
                    'Actividad_Economica_Principal' => null,
                    'UBIGEO' => $reniec->ubigeo_dir ?? null,
                    'Departamento' => null,
                    'Provincia' => null,
                    'Distrito' => null,
                    'Razón_Social' => trim((($reniec->ap_pat ?? '') . ' ' . ($reniec->ap_mat ?? '') . ' ' . ($reniec->nombres ?? ''))),
                    'direccion' => $reniec->direccion ?? null,
                    'dni' => $reniec->dni ?? null,
                    'telefonos' => [],
                    'representantes' => [],
                    'consultas_sunat' => [],
                    'reniec' => (array) $reniec,
                    'source' => 'reniec'
                ];
            }

            $result = (array) $row;

            // telefonos
            $tel = $conn->selectOne("
                SELECT documento, lista_movistar, lista_claro,
                       lista_entel, lista_otros
                FROM super_tabla_telefonos
                WHERE documento = ?
                LIMIT 1
            ", [$dni]);

            $result['telefonos'] = $tel ? (array) $tel : [];

            // consultas_sunat via representantes_legales
            $consultas = $conn->select("
                SELECT cs.id, cs.ruc, cs.nombre_razon_social as nombre_razon_social, cs.estado_contribuyente,
                       cs.condicion_contribuyente as condicion_contribuyente, cs.actividades_economicas,
                       cs.cant_trabajadores, cs.cant_anexos, cs.fecha_consulta, cs.valido, cs.error
                FROM consultas_sunat cs
                JOIN representantes_legales rl ON rl.consulta_id = cs.id
                WHERE rl.numero_documento = ?
                ORDER BY cs.fecha_consulta DESC
                LIMIT 5
            ", [$dni]);

            $result['consultas_sunat'] = array_map(fn($r) => (array) $r, $consultas);

            // representantes legales y teléfonos asociados
            $representantes = [];
            $repRows = $conn->select("
                SELECT rl.id, rl.tipo_documento, rl.numero_documento, rl.nombre, rl.cargo
                FROM representantes_legales rl
                JOIN consultas_sunat cs ON cs.id = rl.consulta_id
                WHERE rl.numero_documento = ?
                ORDER BY cs.fecha_consulta DESC
            ", [$dni]);

            $repDocs = [];
            foreach ($repRows as $repRow) {
                $repArr = (array) $repRow;
                $representantes[] = $repArr;
                if (!empty($repArr['numero_documento'])) {
                    $repDocs[] = $repArr['numero_documento'];
                }
            }

            if (!empty($repDocs)) {
                $phone = $conn->selectOne("
                    SELECT documento, lista_movistar, lista_claro,
                           lista_entel, lista_otros
                    FROM super_tabla_telefonos
                    WHERE documento = ?
                    LIMIT 1
                ", [$dni]);

                $phoneArr = $phone ? (array) $phone : [];
                foreach ($representantes as &$rep) {
                    $rep['telefonos'] = $phoneArr;
                }
                unset($rep);
            }

            $result['representantes'] = $representantes;

            // reniec data
            $reniecRow = $conn->selectOne("
                SELECT dni, ap_pat, ap_mat, nombres, fecha_nac, fch_inscripcion,
                       fch_emision, fch_caducidad, ubigeo_nac, ubigeo_dir,
                       direccion, sexo, est_civil, dig_ruc, madre, padre
                FROM reniec
                WHERE dni = ?
                LIMIT 1
            ", [$dni]);

            $result['reniec'] = $reniecRow ? (array) $reniecRow : null;
            $result['source'] = 'ruc10';

            return $result;

        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::searchByDni: " . $e->getMessage());
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
                Log::error("RUC10Repository::searchAllMassive: Database connection test failed");
                return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'pages' => 0];
            }
            
            $where = [];
            $bindings = [];

            if (!empty($search_filter)) {
                if (!empty($search_filter['dni'])) {
                    $where[] = 'dni = ?'; $bindings[] = $search_filter['dni'];
                }
                if (!empty($search_filter['razon_social'])) {
                    $where[] = 'Razón_Social LIKE ?'; $bindings[] = '%'.$search_filter['razon_social'].'%';
                }
                if (!empty($search_filter['estado'])) { $where[] = 'Estado = ?'; $bindings[] = $search_filter['estado']; }
                if (!empty($search_filter['condicion'])) { $where[] = 'Condicion = ?'; $bindings[] = $search_filter['condicion']; }
                if (!empty($search_filter['departamento'])) { $where[] = 'Departamento = ?'; $bindings[] = $search_filter['departamento']; }
                if (!empty($search_filter['provincia'])) { $where[] = 'Provincia = ?'; $bindings[] = $search_filter['provincia']; }
                if (!empty($search_filter['distrito'])) { $where[] = 'Distrito = ?'; $bindings[] = $search_filter['distrito']; }
                if (!empty($search_filter['actividad_economica'])) { $values = is_array($search_filter['actividad_economica']) ? $search_filter['actividad_economica'] : [$search_filter['actividad_economica']]; $placeholders = implode(',', array_fill(0, count($values), '?')); $where[] = "Actividad_Economica_Principal IN ($placeholders)"; foreach ($values as $value) { $bindings[] = $value; } }
            }

            $whereClause = $where ? 'WHERE '.implode(' AND ', $where) : '';

            // total
            $totalRow = $conn->selectOne("SELECT COUNT(*) as total FROM ruc10_febrero28 $whereClause", $bindings);
            $total = $totalRow ? intval($totalRow->total) : 0;

            $offset = ($page - 1) * $perPage;

            $bindingsForData = array_merge($bindings, [$perPage, $offset]);

            $rows = $conn->select("SELECT RUC, Estado, Condicion, Actividad_Economica_Principal,
                        UBIGEO, Departamento, Provincia, Distrito,
                        Razón_Social, direccion, dni
                    FROM ruc10_febrero28
                    $whereClause
                    ORDER BY dni ASC
                    LIMIT ? OFFSET ?", $bindingsForData);

            $results = array_map(fn($r) => (array) $r, $rows);

            if (empty($results)) {
                if (!empty($search_filter['dni'])) {
                    $reniec = $conn->selectOne("SELECT dni, ap_pat, ap_mat, nombres, fecha_nac, fch_inscripcion,
                           fch_emision, fch_caducidad, ubigeo_nac, ubigeo_dir,
                           direccion, sexo, est_civil, dig_ruc, madre, padre
                    FROM reniec WHERE dni = ? LIMIT 1", [$search_filter['dni']]);

                    if ($reniec) {
                        $fallback = [
                            'RUC' => null,
                            'Estado' => null,
                            'Condicion' => null,
                            'Actividad_Economica_Principal' => null,
                            'UBIGEO' => $reniec->ubigeo_dir ?? null,
                            'Departamento' => null,
                            'Provincia' => null,
                            'Distrito' => null,
                            'Razón_Social' => trim(($reniec->ap_pat ?? '').' '.($reniec->ap_mat ?? '').' '.($reniec->nombres ?? '')),
                            'direccion' => $reniec->direccion ?? null,
                            'dni' => $reniec->dni ?? null,
                            'telefonos' => [],
                            'representantes' => [],
                            'consultas_sunat' => [],
                            'reniec' => (array) $reniec,
                            'source' => 'reniec'
                        ];

                        return ['data' => [$fallback], 'total' => 1, 'page' => $page, 'per_page' => $perPage, 'pages' => 1];
                    }
                }

                return ['data' => [], 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => 0];
            }

            $dnis = array_map(fn($r) => $r['dni'], $results);

            // telefonos
            $placeholders = implode(',', array_fill(0, count($dnis), '?'));
            $telRows = $conn->select("SELECT documento, lista_movistar, lista_claro, lista_entel, lista_otros
                FROM super_tabla_telefonos WHERE documento IN ($placeholders)", $dnis);

            $telMap = [];
            foreach ($telRows as $t) { $telMap[$t->documento] = (array) $t; }

            // representantes + consultas
            $repsRows = $conn->select("SELECT rl.numero_documento, rl.consulta_id, rl.id, rl.tipo_documento, rl.nombre, rl.cargo,
                        cs.ruc, cs.nombre_razon_social, cs.estado_contribuyente, cs.condicion_contribuyente,
                        cs.actividades_economicas, cs.cant_trabajadores, cs.cant_anexos, cs.fecha_consulta, cs.valido, cs.error
                    FROM representantes_legales rl
                    JOIN consultas_sunat cs ON cs.id = rl.consulta_id
                    WHERE rl.numero_documento IN ($placeholders)
                    ORDER BY cs.fecha_consulta DESC", $dnis);

            $dataByDni = [];
            foreach ($repsRows as $r) {
                $arr = (array) $r; $dniKey = $arr['numero_documento'];
                if (!isset($dataByDni[$dniKey])) $dataByDni[$dniKey] = ['representantes' => [], 'consultas_sunat' => []];
                $dataByDni[$dniKey]['representantes'][] = ['id'=>$arr['id'],'tipo_documento'=>$arr['tipo_documento'],'numero_documento'=>$dniKey,'nombre'=>$arr['nombre'],'cargo'=>$arr['cargo']];
                $dataByDni[$dniKey]['consultas_sunat'][] = ['ruc'=>$arr['ruc'],'nombre_razon_social'=>$arr['nombre_razon_social'],'estado_contribuyente'=>$arr['estado_contribuyente'],'condicion_contribuyente'=>$arr['condicion_contribuyente'],'actividades_economicas'=>$arr['actividades_economicas'],'cant_trabajadores'=>$arr['cant_trabajadores'],'cant_anexos'=>$arr['cant_anexos'],'fecha_consulta'=>$arr['fecha_consulta'],'valido'=>$arr['valido'],'error'=>$arr['error']];
            }

            // reniec map
            $reniecRows = $conn->select("SELECT dni, ap_pat, ap_mat, nombres, fecha_nac, fch_inscripcion,
                           fch_emision, fch_caducidad, ubigeo_nac, ubigeo_dir,
                           direccion, sexo, est_civil, dig_ruc, madre, padre
                    FROM reniec WHERE dni IN ($placeholders)", $dnis);
            $reniecMap = [];
            foreach ($reniecRows as $r) { $reniecMap[$r->dni] = (array) $r; }

            // assemble
            $final = [];
            foreach ($results as $row) {
                $dniVal = $row['dni'];
                $row['telefonos'] = $telMap[$dniVal] ?? [];
                $row['representantes'] = $dataByDni[$dniVal]['representantes'] ?? [];
                $row['consultas_sunat'] = array_slice($dataByDni[$dniVal]['consultas_sunat'] ?? [], 0, 5);

                $reniecData = $reniecMap[$dniVal] ?? null;
                if ($reniecData) {
                    $row['reniec'] = $reniecData;
                    if (empty($row['direccion'])) $row['direccion'] = $reniecData['direccion'] ?? null;
                    if (empty($row['UBIGEO'])) $row['UBIGEO'] = $reniecData['ubigeo_dir'] ?? null;
                    if (empty($row['Razón_Social'])) $row['Razón_Social'] = trim(($reniecData['ap_pat'] ?? '').' '.($reniecData['ap_mat'] ?? '').' '.($reniecData['nombres'] ?? ''));
                }

                $final[] = $row;
            }

            return ['data'=>$final,'total'=>$total,'page'=>$page,'per_page'=>$perPage,'pages'=> $total ? intdiv($total+$perPage-1,$perPage) : 0];

        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::searchAllMassive: " . $e->getMessage());
            return ['data'=>[], 'total'=>0,'page'=>$page,'per_page'=>$perPage,'pages'=>0];
        }
    }

    public static function searchAllForExportBatch(?array $search_filter = null, int $limit = 10000, int $offset = 0): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $where = [];
            $bindings = [];

            if (!empty($search_filter)) {
                if (!empty($search_filter['dni'])) { $where[] = 'dni = ?'; $bindings[] = $search_filter['dni']; }
                if (!empty($search_filter['razon_social'])) { $where[] = 'Razón_Social LIKE ?'; $bindings[] = '%'.$search_filter['razon_social'].'%'; }
                if (!empty($search_filter['estado'])) { $where[] = 'Estado = ?'; $bindings[] = $search_filter['estado']; }
                if (!empty($search_filter['condicion'])) { $where[] = 'Condicion = ?'; $bindings[] = $search_filter['condicion']; }
                if (!empty($search_filter['departamento'])) { $where[] = 'Departamento = ?'; $bindings[] = $search_filter['departamento']; }
                if (!empty($search_filter['provincia'])) { $where[] = 'Provincia = ?'; $bindings[] = $search_filter['provincia']; }
                if (!empty($search_filter['distrito'])) { $where[] = 'Distrito = ?'; $bindings[] = $search_filter['distrito']; }
                if (!empty($search_filter['actividad_economica'])) { $values = is_array($search_filter['actividad_economica']) ? $search_filter['actividad_economica'] : [$search_filter['actividad_economica']]; $placeholders = implode(',', array_fill(0, count($values), '?')); $where[] = "Actividad_Economica_Principal IN ($placeholders)"; foreach ($values as $value) { $bindings[] = $value; } }
            }

            $whereClause = $where ? 'WHERE '.implode(' AND ', $where) : '';

            $totalRow = $conn->selectOne("SELECT COUNT(*) as total FROM ruc10_febrero28 $whereClause", $bindings);
            $total_count = $totalRow ? intval($totalRow->total) : 0;

            if ($total_count == 0) return [[], 0];

            $rows = $conn->select("SELECT RUC, Estado, Condicion, Actividad_Economica_Principal,
                        UBIGEO, Departamento, Provincia, Distrito,
                        Razón_Social, direccion, dni
                    FROM ruc10_febrero28
                    $whereClause
                    ORDER BY dni ASC
                    LIMIT ? OFFSET ?", array_merge($bindings, [$limit, $offset]));

            $results = array_map(fn($r)=>(array)$r, $rows);
            if (empty($results)) return [[], $total_count];

            $dnis = array_map(fn($r) => $r['dni'], $results);
            $placeholders = implode(',', array_fill(0, count($dnis), '?'));

            $telRows = $conn->select("SELECT documento, lista_movistar, lista_claro, lista_entel, lista_otros FROM super_tabla_telefonos WHERE documento IN ($placeholders)", $dnis);
            $telMap = [];
            foreach ($telRows as $t) $telMap[$t->documento] = (array)$t;

            $repsRows = $conn->select("SELECT rl.numero_documento, rl.id, rl.tipo_documento, rl.nombre, rl.cargo,
                        cs.ruc, cs.nombre_razon_social, cs.estado_contribuyente, cs.condicion_contribuyente,
                        cs.actividades_economicas, cs.cant_trabajadores, cs.cant_anexos, cs.fecha_consulta, cs.valido, cs.error
                    FROM representantes_legales rl
                    JOIN consultas_sunat cs ON cs.id = rl.consulta_id
                    WHERE rl.numero_documento IN ($placeholders)
                    ORDER BY cs.fecha_consulta DESC", $dnis);

            $dataByDni = [];
            foreach ($repsRows as $r) {
                $arr=(array)$r; $dniKey=$arr['numero_documento'];
                if(!isset($dataByDni[$dniKey])) $dataByDni[$dniKey]=['representantes'=>[],'consultas_sunat'=>[]];
                $dataByDni[$dniKey]['representantes'][]=['id'=>$arr['id'],'tipo_documento'=>$arr['tipo_documento'],'numero_documento'=>$dniKey,'nombre'=>$arr['nombre'],'cargo'=>$arr['cargo']];
                $dataByDni[$dniKey]['consultas_sunat'][]=['ruc'=>$arr['ruc'],'nombre_razon_social'=>$arr['nombre_razon_social'],'estado_contribuyente'=>$arr['estado_contribuyente'],'condicion_contribuyente'=>$arr['condicion_contribuyente'],'actividades_economicas'=>$arr['actividades_economicas'],'cant_trabajadores'=>$arr['cant_trabajadores'],'cant_anexos'=>$arr['cant_anexos'],'fecha_consulta'=>$arr['fecha_consulta'],'valido'=>$arr['valido'],'error'=>$arr['error']];
            }

            $reniecRows = $conn->select("SELECT dni, ap_pat, ap_mat, nombres, fecha_nac, fch_inscripcion,
                           fch_emision, fch_caducidad, ubigeo_nac, ubigeo_dir,
                           direccion, sexo, est_civil, dig_ruc, madre, padre
                    FROM reniec WHERE dni IN ($placeholders)", $dnis);
            $reniecMap = [];
            foreach ($reniecRows as $r) $reniecMap[$r->dni] = (array)$r;

            $final=[];
            $MAX_REPS=5; $MAX_SUNAT=5;
            foreach ($results as $row) {
                $dniVal=$row['dni'];
                $telefonos=$telMap[$dniVal] ?? [];
                $reps=$dataByDni[$dniVal]['representantes'] ?? [];
                $consultas=$dataByDni[$dniVal]['consultas_sunat'] ?? [];
                $reniecData = $reniecMap[$dniVal] ?? null;

                if ($reniecData) {
                    if (empty($row['direccion'])) $row['direccion']=$reniecData['direccion'] ?? null;
                    if (empty($row['UBIGEO'])) $row['UBIGEO']=$reniecData['ubigeo_dir'] ?? null;
                    if (empty($row['Razón_Social'])) $row['Razón_Social']=trim(($reniecData['ap_pat']??'').' '.($reniecData['ap_mat']??'').' '.($reniecData['nombres']??''));
                }

                $row['dni'] = "'" . (string)$dniVal;
                $row['movistar'] = $telefonos['lista_movistar'] ?? null;
                $row['claro'] = $telefonos['lista_claro'] ?? null;
                $row['entel'] = $telefonos['lista_entel'] ?? null;
                $row['otros'] = $telefonos['lista_otros'] ?? null;

                for ($i=0;$i<$MAX_REPS;$i++) {
                    if ($i < count($reps)) {
                        $row["rep_".($i+1)."_nombre"] = $reps[$i]['nombre'] ?? null;
                        $row["rep_".($i+1)."_cargo"] = $reps[$i]['cargo'] ?? null;
                        $row["rep_".($i+1)."_tipo_documento"] = $reps[$i]['tipo_documento'] ?? null;
                    } else {
                        $row["rep_".($i+1)."_nombre"] = null;
                        $row["rep_".($i+1)."_cargo"] = null;
                        $row["rep_".($i+1)."_tipo_documento"] = null;
                    }
                }

                for ($i=0;$i<$MAX_SUNAT;$i++) {
                    if ($i < count($consultas)) {
                        $c = $consultas[$i];
                        $row["sunat_".($i+1)."_ruc"] = $c['ruc'] ?? null;
                        $row["sunat_".($i+1)."_estado"] = $c['estado_contribuyente'] ?? null;
                        $row["sunat_".($i+1)."_condicion"] = $c['condicion_contribuyente'] ?? null;
                        $row["sunat_".($i+1)."_fecha"] = $c['fecha_consulta'] ?? null;
                        $row["sunat_".($i+1)."_nombre"] = $c['nombre_razon_social'] ?? null;
                        $row["sunat_".($i+1)."_actividades"] = $c['actividades_economicas'] ?? null;
                    } else {
                        $row["sunat_".($i+1)."_ruc"] = null;
                        $row["sunat_".($i+1)."_estado"] = null;
                        $row["sunat_".($i+1)."_condicion"] = null;
                        $row["sunat_".($i+1)."_fecha"] = null;
                        $row["sunat_".($i+1)."_nombre"] = null;
                        $row["sunat_".($i+1)."_actividades"] = null;
                    }
                }

                $final[] = $row;
            }

            Log::info("Lote exportado: " . count($final) . " registros (offset: $offset, total: $total_count)");
            return [$final, $total_count];

        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::searchAllForExportBatch: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getFilterValues(string $column): array
    {
        $allowed = ['Estado','Condicion','Departamento','Provincia','Distrito','UBIGEO'];
        if (!in_array($column, $allowed)) return [];
        try {
            $conn = DB::connection('mysql_flask');
            $rows = $conn->select("SELECT DISTINCT `$column` as value FROM ruc10_febrero28 WHERE `$column` IS NOT NULL AND `$column` != '' ORDER BY `$column` ASC LIMIT 1000");
            return array_values(array_filter(array_map(fn($r)=> $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::getFilterValues: " . $e->getMessage());
            return [];
        }
    }

    public static function getProvinciasByDepartamento(string $departamento): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $rows = $conn->select("SELECT DISTINCT Provincia as value FROM ruc10_febrero28 WHERE Departamento = ? AND Provincia IS NOT NULL AND Provincia != '' ORDER BY Provincia ASC LIMIT 1000", [$departamento]);
            return array_values(array_filter(array_map(fn($r)=> $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::getProvinciasByDepartamento: " . $e->getMessage());
            return [];
        }
    }

    public static function getDistritosByProvincia(string $departamento, string $provincia): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $rows = $conn->select("SELECT DISTINCT Distrito as value FROM ruc10_febrero28 WHERE Departamento = ? AND Provincia = ? AND Distrito IS NOT NULL AND Distrito != '' ORDER BY Distrito ASC LIMIT 1000", [$departamento, $provincia]);
            return array_values(array_filter(array_map(fn($r)=> $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::getDistritosByProvincia: " . $e->getMessage());
            return [];
        }
    }

    public static function getSuggestions(string $query, int $limit = 10): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            $q = '%' . $query . '%';
            $rows = $conn->select("SELECT dni, `Razón_Social` as razon_social FROM ruc10_febrero28 WHERE dni LIKE ? OR `Razón_Social` LIKE ? LIMIT ?", [$q, $q, $limit]);
            return array_map(fn($r) => (array)$r, $rows);
        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::getSuggestions: " . $e->getMessage());
            return [];
        }
    }

    public static function getActividadEconomicaOptions(string $query = '', int $limit = 50): array
    {
        try {
            $conn = DB::connection('mysql_flask');
            if (trim($query) === '') {
                $rows = $conn->select("SELECT DISTINCT Actividad_Economica_Principal as value FROM ruc10_febrero28 WHERE Actividad_Economica_Principal IS NOT NULL AND Actividad_Economica_Principal != '' ORDER BY Actividad_Economica_Principal ASC LIMIT ?", [$limit]);
            } else {
                $q = '%' . $query . '%';
                $rows = $conn->select("SELECT DISTINCT Actividad_Economica_Principal as value FROM ruc10_febrero28 WHERE Actividad_Economica_Principal IS NOT NULL AND Actividad_Economica_Principal != '' AND Actividad_Economica_Principal LIKE ? ORDER BY Actividad_Economica_Principal ASC LIMIT ?", [$q, $limit]);
            }
            return array_values(array_filter(array_map(fn($r) => $r->value ?? null, $rows)));
        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::getActividadEconomicaOptions: " . $e->getMessage());
            return [];
        }
    }

    public static function getStatistics(): array
    {
        try {
            $conn = DB::connection('mysql_flask');

            $totalRow = $conn->selectOne("SELECT COUNT(*) as total FROM ruc10_febrero28");
            $total = $totalRow ? intval($totalRow->total) : 0;

            $hasFuente = Schema::connection('mysql_flask')->hasColumn('ruc10_febrero28', 'fuente_datos');
            $hasSource = Schema::connection('mysql_flask')->hasColumn('ruc10_febrero28', 'source');

            $reniec = 0; $sunat = 0;
            if ($hasFuente) {
                $r = $conn->selectOne("SELECT SUM(CASE WHEN fuente_datos = 'reniec' THEN 1 ELSE 0 END) as reniec, SUM(CASE WHEN fuente_datos = 'sunat' THEN 1 ELSE 0 END) as sunat FROM ruc10_febrero28");
                $reniec = $r->reniec ?? 0; $sunat = $r->sunat ?? 0;
            } elseif ($hasSource) {
                $r = $conn->selectOne("SELECT SUM(CASE WHEN `source` = 'reniec' THEN 1 ELSE 0 END) as reniec, SUM(CASE WHEN `source` = 'sunat' THEN 1 ELSE 0 END) as sunat FROM ruc10_febrero28");
                $reniec = $r->reniec ?? 0; $sunat = $r->sunat ?? 0;
            }

            // top departamentos
            $top = $conn->select("SELECT Departamento as departamento, COUNT(*) as total FROM ruc10_febrero28 WHERE Departamento IS NOT NULL AND Departamento != '' GROUP BY Departamento ORDER BY total DESC LIMIT 10");
            $topArr = array_map(fn($r)=>(array)$r, $top);

            return [
                'success' => true,
                'data' => [
                    'total_registros' => $total,
                    'datos_reniec' => intval($reniec),
                    'datos_sunat' => intval($sunat),
                    'porcentaje_reniec' => $total > 0 ? round((intval($reniec) / $total) * 100, 2) : 0,
                    'porcentaje_sunat' => $total > 0 ? round((intval($sunat) / $total) * 100, 2) : 0,
                    'top_departamentos' => $topArr
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Error RUC10Repository::getStatistics: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener estadísticas', 'data' => null];
        }
    }
}
