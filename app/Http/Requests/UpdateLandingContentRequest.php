<?php

namespace App\Http\Requests;

use App\Models\LandingContentType;
use App\Rules\UniqueInScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLandingContentRequest extends FormRequest
{
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
            'landing_content_type_id' => [
                'required',
                'numeric',
                Rule::exists(LandingContentType::class, 'id'),
            ],
            'position' => [
                'required',
                'integer',
                new UniqueInScope(
                    model: $this->route('landing_content'),
                    scopeColumn: 'landing_content_type_id',
                    scopeValue: $this->input('landing_content_type_id'),
                ),
            ],

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
            'file_url' => 'Archivo',
            'landing_content_type_id' => 'Tipo de contenido',
        ];
    }
}
