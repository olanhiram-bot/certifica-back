<?php

namespace App\Exports;

use App\Models\Stamp;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Stamp>
 */
class ValidatorStampsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Builder<Stamp>  $query
     */
    public function __construct(protected Builder $query) {}

    /**
     * @return Builder<Stamp>
     */
    public function query(): Builder
    {
        return $this->query->with(['provider', 'stampsActivity']);
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            'ID Certificacion',
            'RFC Usuario',
            'Nombre Usuario',
            'Email Usuario',
            'Usuario',
            'Vigencia Inicio',
            'Vigencia Fin',
            'Porcentaje (%)',
            'Fecha Registro',
            'Días Restantes',
            'Estatus',
        ];
    }

    /**
     * @param  \App\Models\Stamp  $stamp
     * @return array<mixed>
     */
    public function map($stamp): array
    {
        $providerUser = $stamp->provider?->user ?? [];

        $createdAt = $stamp->created_at;
        $daysElapsed = $createdAt ? (int) now()->startOfDay()->diffInDays($createdAt->copy()->startOfDay()) : 0;
        $remainingDays = $createdAt ? max(0, 10 - $daysElapsed) : null;

        return [
            $stamp->id,
            $providerUser['rfc'] ?? '',
            $providerUser['provider_info']['nombre_completo'] ?? '',
            $providerUser['email'] ?? '',
            $stamp->provider?->username ?? '',
            $stamp->vigency_start_date,
            $stamp->vigency_end_date,
            $stamp->percent,
            $stamp->created_at,
            $remainingDays,
            $stamp->stampStatus?->name ?? '',
        ];
    }
}
