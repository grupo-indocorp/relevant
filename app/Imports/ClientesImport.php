<?php

namespace App\Imports;

use App\Models\Cliente;
use App\Models\Comentario;
use App\Models\Etapa;
use App\Models\Etiqueta;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClientesImport implements ToModel, WithHeadingRow
{
    protected $user_id;
    protected $etiqueta_id;

    public function __construct($user_id, $etiqueta_id)
    {
        $this->user_id = $user_id;
        $this->etiqueta_id = $etiqueta_id;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $existe = Cliente::where('ruc', (string) $row['ruc'])->first();
        if ($existe) {
            return null;
        }

        $user = User::find($this->user_id);
        $etiqueta = Etiqueta::find($this->etiqueta_id);
        $etapa = Etapa::where('estado', 1)->orderBy('id')->first();

        $cliente = new Cliente;
        $cliente->ruc = (string) $row['ruc'];
        $cliente->razon_social = $row['razon_social'];
        $cliente->ciudad = $row['ciudad'] ?? '';
        $cliente->fecha_gestion = now();
        $cliente->fecha_nuevo = now();
        $cliente->etiqueta_id = 1;
        $cliente->user_id = $user->id;
        $cliente->equipo_id = $user->equipos->last()->id ?? 1;
        $cliente->sede_id = $user->equipos->last()->sede->id ?? 1;
        $cliente->etapa_id = $etapa->id;
        $cliente->contactabilidad = true;
        $cliente->tipobase = $etiqueta->nombre;
        $cliente->departamento_codigo = '12';
        $cliente->provincia_codigo = '1201';
        $cliente->distrito_codigo = '120101';
        $cliente->save();

        $comentario = new Comentario();
        $comentario->comentario = 'Cliente importado';
        $comentario->detalle = 'Cambio de etapa a '.$etapa->nombre;
        $comentario->user_id = $user->id;
        $comentario->cliente_id = $cliente->id;
        $comentario->etiqueta_id = 1; // 1=nuevo;
        $comentario->save();

        $cliente->etapas()->attach($etapa->id);

        return $cliente;
    }
}
