<?php

namespace App\Services;

use App\Models\Stamp;
use App\Models\StampActivity;
use Carbon\Carbon;

class StampValidationService
{
    private int $diasProvSubirTodo;
    private int $diasValEvalTodo;
    private int $diasProvCorregir;

    public function __construct()
    {
        $this->diasProvSubirTodo = config('app.dias_prov_subir_todo');
        $this->diasValEvalTodo = config('app.dias_val_eval_todo');
        $this->diasProvCorregir = config('app.dias_prov_corregir');
    }

    public function validateStampCurrent(Stamp $stamp): void
    {
        $isCurrent = $stamp->courseCall()
            ->currentToday()
            ->first();

        if (! $isCurrent) {
            throw new \Exception('No se pueden actualizar actividades de una certificacion cuya convocatoria ha finalizado');
        }
    }

    public function validateDateUploadEvidence(StampActivity $stampActivity): void
    {
        $stamp = $stampActivity->stamp;

        if (! $stamp) {
            throw new \Exception('Certificacion no encontrada');
        }

        $maxDate = null;
        $errorMessage = null;

        if (in_array($stampActivity->stamp_activity_status_id, [1, 2])) {
            if (! $stamp->created_at) {
                throw new \Exception('Fecha de creación de la certificacion desconocida.', 422);
            }
            $maxDate = $stamp->created_at->copy()->addDays(workingDay($this->diasProvSubirTodo));
            $errorMessage = 'expiro la fecha máxima para subir evidencias.';
        }

        if ($stampActivity->stamp_activity_status_id == 5) {
            if (empty($stamp->reject_date)) {
                throw new \Exception('Aun no se ha rechazado la certificacion', 422);
            }
            $maxDate = Carbon::parse($stamp->reject_date)->addDays(workingDay($this->diasProvCorregir));
            $errorMessage = 'expiro la fecha máxima para corregir evidencias.';
        }

        if (! $maxDate) {
            return;
        }

        $isCurrent = now()->lessThanOrEqualTo($maxDate);
        if (! $isCurrent) {
            throw new \Exception('No se pueden subir evidencias, '.$errorMessage, 422);
        }
    }

    public function validateDateEvaluate(Stamp $stamp): void
    {
        $maxDate = Carbon::parse($stamp->take_review_date)->addDays(workingDay($this->diasValEvalTodo));
        $isCurrent = now()->lessThanOrEqualTo($maxDate);
        if (! $isCurrent) {
            throw new \Exception('No se puede evaluar la certificacion, expiro la fecha máxima para evaluar', 422);
        }
    }
}
