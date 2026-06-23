<?php

namespace App\Http\Requests;

use App\Models\SectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
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
            'section_type_id' => [
                'required',
                'numeric',
                Rule::exists(SectionType::class, 'id'),
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
            'section_type_id' => 'Tipo de sección',
        ];
    }
}
