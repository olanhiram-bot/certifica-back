<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLandingContentRequest extends FormRequest
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
        $param = $this->route('content_type') ?? $this->input('landing_content_type_id');
        $contentTypeId = $param instanceof Model ? $param->getKey() : (int) $param;

        // 2. Asignación de mimes usando una constante y un ternario
        $mime = ($contentTypeId === 6)
            ? 'jpg,jpeg,png,pdf,mp4'
            : 'jpg,jpeg,png';

        return [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file_url' => "nullable|file|max:10240|mimes:$mime",
            'position' => [
                'required',
                'integer',
                Rule::unique('landing_contents')->where(function ($query) {
                    // Obtenemos el ID actual que se está enviando o procesando
                    $contentType = $this->route('content_type') ?? $this->input('landing_content_type_id');

                    $contentTypeId = ($contentType instanceof \App\Models\LandingContentType)
                        ? $contentType->id
                        : (int) $contentType;

                    // Esto le dice a Laravel: "Busca si la posición ya existe,
                    // PERO solo dentro de los registros que tengan este mismo landing_content_type_id"
                    return $query->where('landing_content_type_id', $contentTypeId)->whereNull('deleted_at');
                }),
            ],
            'link' => 'nullable|url',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * @return array<string,string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'file' => 'Archivo',
            'link' => 'Dirección URL',
            'position' => 'Posición',
        ];
    }
}
