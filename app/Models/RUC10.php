<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RUC10 extends Model
{
    use HasFactory;

    protected $connection = 'mysql_flask';
    protected $table = 'ruc10_febrero28';

    protected $fillable = [
        'dni',
        'ruc',
        'razon_social',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'estado',
        'condicion',
        'departamento',
        'provincia',
        'distrito',
        'direccion',
        'tipo_via',
        'nombre_via',
        'tipo_zona',
        'numero_zona',
        'interior',
        'lote',
        'manzana',
        'kilometro',
        'block',
        'etapa',
        'referencia',
        'ubigeo',
        'sistema_emision',
        'sistema_contabilidad',
        'actividad_economica',
        'actividad_comercio_exterior',
        'fecha_inscripcion',
        'fecha_inicio_actividades',
        'estado_contribuyente',
        'condicion_domicilio',
        'emision_electronica',
        'ple_ventas',
        'ple_compras',
        'ple_diario',
        'ple_inventory',
        'fecha_nacimiento',
        'sexo',
        'estado_civil',
        'grado_instruccion',
        'fuente_datos', // 'reniec' o 'sunat'
        'fecha_actualizacion'
    ];

    protected $casts = [
        'fecha_inscripcion' => 'date',
        'fecha_inicio_actividades' => 'date',
        'fecha_nacimiento' => 'date',
        'fecha_actualizacion' => 'datetime'
    ];

    /**
     * Scope para búsqueda por DNI
     */
    public function scopeByDni($query, $dni)
    {
        return $query->where('dni', 'like', "%{$dni}%");
    }

    /**
     * Scope para búsqueda por RUC
     */
    public function scopeByRuc($query, $ruc)
    {
        return $query->where('ruc', 'like', "%{$ruc}%");
    }

    /**
     * Scope para búsqueda por razón social
     */
    public function scopeByRazonSocial($query, $razonSocial)
    {
        return $query->where('razon_social', 'like', "%{$razonSocial}%");
    }

    /**
     * Scope para búsqueda por nombres completos
     */
    public function scopeByNombres($query, $nombres)
    {
        return $query->where(function($q) use ($nombres) {
            $q->where('nombres', 'like', "%{$nombres}%")
              ->orWhere('apellido_paterno', 'like', "%{$nombres}%")
              ->orWhere('apellido_materno', 'like', "%{$nombres}%")
              ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ', ', nombres) LIKE ?", ["%{$nombres}%"]);
        });
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por condición
     */
    public function scopeByCondicion($query, $condicion)
    {
        return $query->where('condicion', $condicion);
    }

    /**
     * Scope para filtrar por departamento
     */
    public function scopeByDepartamento($query, $departamento)
    {
        return $query->where('departamento', 'like', "%{$departamento}%");
    }

    /**
     * Scope para filtrar por provincia
     */
    public function scopeByProvincia($query, $provincia)
    {
        return $query->where('provincia', 'like', "%{$provincia}%");
    }

    /**
     * Scope para filtrar por distrito
     */
    public function scopeByDistrito($query, $distrito)
    {
        return $query->where('distrito', 'like', "%{$distrito}%");
    }

    /**
     * Scope para filtrar por fuente de datos
     */
    public function scopeByFuente($query, $fuente)
    {
        $table = (new static)->getTable();
        $hasFuente = Schema::connection('mysql_flask')->hasColumn($table, 'fuente_datos');
        $hasSource = Schema::connection('mysql_flask')->hasColumn($table, 'source');

        if ($hasFuente) {
            return $query->where('fuente_datos', $fuente);
        }

        if ($hasSource) {
            return $query->where('source', $fuente);
        }

        // Si ninguna columna existe, retornar la query sin filtrar
        return $query;
    }

    /**
     * Scope para búsqueda combinada con múltiples filtros
     */
    public function scopeByFilters($query, $filters)
    {
        if (empty($filters)) {
            return $query;
        }

        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                switch ($key) {
                    case 'dni':
                        $query->byDni($value);
                        break;
                    case 'ruc':
                        $query->byRuc($value);
                        break;
                    case 'razon_social':
                        $query->byRazonSocial($value);
                        break;
                    case 'nombres':
                        $query->byNombres($value);
                        break;
                    case 'estado':
                        $query->byEstado($value);
                        break;
                    case 'condicion':
                        $query->byCondicion($value);
                        break;
                    case 'departamento':
                        $query->byDepartamento($value);
                        break;
                    case 'provincia':
                        $query->byProvincia($value);
                        break;
                    case 'distrito':
                        $query->byDistrito($value);
                        break;
                    case 'fuente':
                        $query->byFuente($value);
                        break;
                }
            }
        }

        return $query;
    }

    /**
     * Obtiene nombre completo formateado
     */
    public function getNombreCompletoAttribute()
    {
        $nombreCompleto = '';
        
        if ($this->apellido_paterno) {
            $nombreCompleto .= $this->apellido_paterno . ' ';
        }
        if ($this->apellido_materno) {
            $nombreCompleto .= $this->apellido_materno . ', ';
        }
        if ($this->nombres) {
            $nombreCompleto .= $this->nombres;
        }
        
        return trim($nombreCompleto);
    }

    /**
     * Obtiene dirección completa formateada
     */
    public function getDireccionCompletaAttribute()
    {
        $direccion = '';
        
        if ($this->tipo_via) {
            $direccion .= $this->tipo_via . ' ';
        }
        if ($this->nombre_via) {
            $direccion .= $this->nombre_via . ' ';
        }
        if ($this->numero_zona) {
            $direccion .= 'N° ' . $this->numero_zona . ' ';
        }
        if ($this->interior) {
            $direccion .= 'Int. ' . $this->interior . ' ';
        }
        if ($this->lote) {
            $direccion .= 'Lte. ' . $this->lote . ' ';
        }
        if ($this->manzana) {
            $direccion .= 'Mza. ' . $this->manzana . ' ';
        }
        
        return trim($direccion);
    }

    /**
     * Obtiene ubicación completa formateada
     */
    public function getUbicacionCompletaAttribute()
    {
        $ubicacion = '';
        
        if ($this->distrito) {
            $ubicacion .= $this->distrito . ', ';
        }
        if ($this->provincia) {
            $ubicacion .= $this->provincia . ', ';
        }
        if ($this->departamento) {
            $ubicacion .= $this->departamento;
        }
        
        return trim($ubicacion);
    }

    /**
     * Accesor para normalizar el campo `fuente_datos`.
     * Si la tabla tiene `fuente_datos` lo devuelve, si tiene `source` devuelve ese valor.
     */
    public function getFuenteDatosAttribute()
    {
        if (array_key_exists('fuente_datos', $this->attributes)) {
            return $this->attributes['fuente_datos'];
        }

        if (array_key_exists('source', $this->attributes)) {
            return $this->attributes['source'];
        }

        return null;
    }

    /**
     * Verifica si los datos vienen de RENIEC
     */
    public function getEsReniecAttribute()
    {
        return $this->fuente_datos === 'reniec';
    }

    /**
     * Verifica si los datos vienen de SUNAT
     */
    public function getEsSunatAttribute()
    {
        return $this->fuente_datos === 'sunat';
    }
}
