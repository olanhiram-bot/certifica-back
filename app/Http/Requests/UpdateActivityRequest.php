<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo_file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png',
            'content_file' => 'nullable|file|max:10240|mimes:pdf',
            'section_id' => [
                'required',
                'numeric',
                Rule::exists(Section::class, 'id'),
            ],
            'activity_logo_delete' => 'nullable|boolean',
            'activity_content_delete' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string,string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'logo_file' => 'Imagen',
            'content_file' => 'Archivo de contenido',
            'section_id' => 'Sección',
            'activity_logo_delete' => 'Eliminar imagen',
            'activity_content_delete' => 'Eliminar archivo de contenido',
        ];
    }
}
