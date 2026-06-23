<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
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
        $section = $this->route('section');

        // Evaluamos directamente como booleano (si es true, se cumple la condición)
        $isTypeOne = ($section instanceof \App\Models\Section) && $section->section_type_id;

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo_file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png',
            'content_file' => [
                $isTypeOne ? 'required' : 'nullable',
                'file',
                'max:10240',
                'mimes:pdf',
            ],
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
            'file' => 'Imagen',
            'section_id' => 'Sección',
        ];
    }
}
