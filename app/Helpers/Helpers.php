<?php

namespace App\Helpers;

use App\Models\Notificacion;
use Illuminate\Support\Facades\Storage;

class Helpers
{
    public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }

    /**
     * @return array de configuracion
     */
    public static function NotificacionRecordatorio($user)
    {
        $notificaciones = [];
        if ($user) {
            $query = Notificacion::query()
                ->where('fecha', '>=', now()->format('Y-m-d'))
                ->where('atendido', false)
                ->orderBy('fecha');
            if ($user->hasRole(['sistema', 'administrador', 'gerente comercial', 'gerente comercial'])) {
                $notificaciones = $query->get();
            } elseif ($user->hasRole('supervisor')) {
                $idsEjecutivos = $user->equipo->users->pluck('id');
                $notificaciones = $query->whereIn('user_id', $idsEjecutivos)->get();
            } else {
                $notificaciones = $query->where('user_id', $user->id)->get();
            }
        }
        return $notificaciones;
    }

    /**
     * @return array de configuracion
     */
    public static function configuracionJsonGet()
    {
        return true;
    }

    /**
     * Crea o actualiza el json de configuracion
     *
     * @return array de configuracion
     */
    public static function configuracionJsonPut($configTipo = '', $configData = [])
    {
        return true;
    }

    /**
     * @return array de configuracion de excel
     */
    public static function configuracionExcelJsonGet()
    {
        $archivoJson = Storage::disk('public')->get('configuracionExcel.json');
        $configuracionExcel = json_decode($archivoJson, true);
        if (is_null($configuracionExcel)) {
            $configuracionExcel = Helpers::configuracionExcelJsonPut();
        }

        return $configuracionExcel;
    }

    /**
     * Crea o actualiza el json de configuracion
     *
     * @return array de configuracion de excel
     */
    public static function configuracionExcelJsonPut($configTipo = '', $configData = [])
    {
        switch ($configTipo) {
            case 'excel':
                $configuracionExcel = [
                    'excel' => [
                        'indotech' => $configData['indotech'],
                        'secodi' => $configData['secodi'],
                    ],
                ];
                break;
            default:
                $configuracionExcel = [
                    'excel' => [
                        'indotech' => false,
                        'secodi' => false,
                    ],
                ];
                break;
        }
        $json = json_encode($configuracionExcel, JSON_PRETTY_PRINT);
        Storage::disk('public')->put('configuracionExcel.json', $json);

        return $configuracionExcel;
    }

    /**
     * @return array de configuracion de datos adiconales
     */
    public static function configuracionDatosAdicionalesJsonGet()
    {
        $archivoJson = Storage::disk('public')->get('configuracionDatosAdicionales.json');
        $configuracionDatosAdicionales = json_decode($archivoJson, true);
        if (is_null($configuracionDatosAdicionales)) {
            $configuracionDatosAdicionales = Helpers::configuracionDatosAdicionalesJsonPut();
        }

        return $configuracionDatosAdicionales;
    }

    /**
     * Crea o actualiza el json de configuracion de datos adiconales
     *
     * @return array de configuracion de datos adiconales
     */
    public static function configuracionDatosAdicionalesJsonPut($configTipo = '', $configData = [])
    {
        switch ($configTipo) {
            case 'datosAdicionales':
                $configuracionDatosAdicionales = [
                    'datosAdicionales' => [
                        'estadoWick' => $configData['estadoWick'],
                        'estadoDito' => $configData['estadoDito'],
                        'lineaClaro' => $configData['lineaClaro'],
                        'lineaEntel' => $configData['lineaEntel'],
                        'lineaBitel' => $configData['lineaBitel'],
                        'lineaMovistar' => $configData['lineaMovistar'],
                        'tipoCliente' => $configData['tipoCliente'],
                        'ejecutivoSalesforce' => $configData['ejecutivoSalesforce'],
                        'agencia' => $configData['agencia'],
                    ],
                ];
                break;
            default:
                $configuracionDatosAdicionales = [
                    'datosAdicionales' => [
                        'estadoWick' => true,
                        'estadoDito' => true,
                        'lineaClaro' => true,
                        'lineaEntel' => true,
                        'lineaBitel' => true,
                        'lineaMovistar' => true,
                        'tipoCliente' => true,
                        'ejecutivoSalesforce' => true,
                        'agencia' => true,
                    ],
                ];
                break;
        }
        $json = json_encode($configuracionDatosAdicionales, JSON_PRETTY_PRINT);
        Storage::disk('public')->put('configuracionDatosAdicionales.json', $json);

        return $configuracionDatosAdicionales;
    }

    /**
     * Filtro desde gestion de clientes
     * Que sirve para exportar los excel
     *
     * @return array where para Exportcliente
     */
    public static function filtroExportCliente($filtro, $user, bool $skipRoleRestrictions = false)
    {
        $where = [];
        if (isset($filtro->filtro_ruc)) {
            $where[] = ['ruc', 'LIKE', $filtro->filtro_ruc.'%'];
        }
        if ($filtro->filtro_etapa_id != 0) {
            $where[] = ['etapa_id', $filtro->filtro_etapa_id];
        }

        // Restringir por rol a menos que se indique lo contrario
        if (! $skipRoleRestrictions) {
            if ($user->hasRole('ejecutivo')) {
                $where[] = ['ejecutivo_id', $user->id];
            } else {
                if ($filtro->filtro_user_id != 0) {
                    $where[] = ['ejecutivo_id', $filtro->filtro_user_id];
                }
            }
            if ($user->hasRole('supervisor')) {
                $where[] = ['ejecutivo_equipo_id', $user->equipo->id];
            } else {
                if ($filtro->filtro_equipo_id != 0) {
                    $where[] = ['ejecutivo_equipo_id', $filtro->filtro_equipo_id];
                }
            }
            if ($user->hasRole('gerente comercial') || $user->hasRole('supervisor') || $user->hasRole('ejecutivo')) {
                $where[] = ['ejecutivo_sede_id', $user->sede_id];
            } else { // administrador, sistema
                if ($filtro->filtro_sede_id != 0) {
                    $where[] = ['ejecutivo_sede_id', $filtro->filtro_sede_id];
                }
            }
        } else {
            // Si se omiten restricciones de rol, permitir filtro por user/equipo/sede si vienen en el payload
            if ($filtro->filtro_user_id != 0) {
                $where[] = ['ejecutivo_id', $filtro->filtro_user_id];
            }
            if ($filtro->filtro_equipo_id != 0) {
                $where[] = ['ejecutivo_equipo_id', $filtro->filtro_equipo_id];
            }
            if ($filtro->filtro_sede_id != 0) {
                $where[] = ['ejecutivo_sede_id', $filtro->filtro_sede_id];
            }
        }

        if (isset($filtro->filtro_fecha_desde)) {
            $where[] = ['fecha_ultimo_contacto', '>=', $filtro->filtro_fecha_desde.' 00:00:00'];
        }
        if (isset($filtro->filtro_fecha_hasta)) {
            $where[] = ['fecha_ultimo_contacto', '<=', $filtro->filtro_fecha_hasta.' 23:59:59'];
        }

        return $where;
    }
}
