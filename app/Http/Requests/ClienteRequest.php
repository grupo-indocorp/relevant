<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ruc' => 'required|numeric|digits:11|starts_with:20,10|unique:clientes,ruc|bail',
            'razon_social' => 'required|bail',
            'ciudad' => 'required|bail',
            'departamento_codigo' => 'required',
            'provincia_codigo' => 'required',
            'distrito_codigo' => 'required',
            'comentario' => 'required|bail',
            'etapa_id' => 'required|bail',
            'linea_entel' => 'required|bail',
            'linea_bitel' => 'required|bail',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ruc.required' => 'El "Ruc" es obligatorio.',
            'ruc.numeric' => 'El "Ruc" debe ser numérico.',
            'ruc.digits' => 'El "Ruc" debe tener exactamente 11 dígitos.',
            'ruc.starts_with' => 'El "Ruc" debe iniciar con 20 o 10.',
            'ruc.unique' => 'El "Ruc" ya se encuentra registrado.',
            'razon_social.required' => 'La "Razón Social" es obligatorio.',
            'ciudad.required' => 'La "Ciudad" es obligatorio.',
            'departamento_codigo.required' => 'El "Departamento" es obligatorio.',
            'provincia_codigo.required' => 'La "Provincia" es obligatoria.',
            'distrito_codigo.required' => 'El "Distrito" es obligatorio.',
            'comentario.required' => 'El "Comentario" es obligatorio.',
            'etapa_id.required' => 'La "Etapa" es obligatorio.',
            'linea_entel.required' => 'La "Cantidad de trabajadores" es obligatorio.',
            'linea_bitel.required' => 'La "Cantidad de sucursales" es obligatorio.',
        ];
    }
}
