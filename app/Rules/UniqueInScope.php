<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Verifica que un valor sea único dentro de un scope (columna + valor),
 * ignorando el registro actual cuando se está editando.
 *
 * Uso:
 *   new UniqueInScope(
 *       model: $landingContent,
 *       scopeColumn: 'landing_content_type_id',
 *       scopeValue: $this->input('landing_content_type_id'),
 *   )
 */
class UniqueInScope implements ValidationRule
{
    public function __construct(
        protected ?Model $model,
        protected string $scopeColumn,
        protected mixed $scopeValue,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model === null) {
            return;
        }

        // Si el valor no cambió respecto al registro actual, no hay conflicto
        if ($this->model->exists && $value == $this->model->$attribute) {
            return;
        }

        $exists = $this->model
            ->where($attribute, $value)
            ->where($this->scopeColumn, $this->scopeValue)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            $fail('El campo :attribute ya se encuentra registrado.');
        }
    }
}
