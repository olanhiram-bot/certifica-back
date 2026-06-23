<?php

namespace App\Rules;

use App\Models\Section;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida que el archivo de contenido (content_file) sea obligatorio
 * cuando la sección pertenece a un tipo de modalidad 1 (Módulos / Sistema Certifica).
 *
 * NOTA: Esta regla NO debe combinarse con 'nullable' en el array de reglas del FormRequest,
 * ya que 'nullable' hace que Laravel omita todas las reglas subsecuentes cuando el valor
 * es null, impidiendo que esta regla se ejecute.
 *
 * La validación condicional se resuelve directamente en el FormRequest usando:
 *   $section?->section_type_id === 1 ? 'required' : 'nullable'
 */
class ContentFileRequiredForModality implements ValidationRule
{
    public function __construct(
        protected ?Section $section,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->section === null) {
            return;
        }

        // content_file es obligatorio cuando la sección es de tipo 1 (Módulos),
        // que corresponde a la modalidad "Sistema Certifica" (modality_type_id = 1),
        // donde los usuarioes aprenden a través del material en línea.
        if ($this->section->section_type_id === 1 && empty($value)) {
            $fail("El campo {$attribute} es obligatorio para actividades de tipo Módulo.");
        }
    }
}
