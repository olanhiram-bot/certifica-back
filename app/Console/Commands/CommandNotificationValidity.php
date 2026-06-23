<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Stamp;
use Illuminate\Console\Command;

class CommandNotificationValidity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:command-notification-validity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $diasProvSubirTodo = config('app.dias_prov_subir_todo', 0);
        $diasProvCorregir = config('app.dias_prov_corregir', 0);
        $diasValEvalTodo = config('app.dias_val_eval_todo', 0);

        $firstDate = now()->subDays($diasProvSubirTodo)->toDateString();
        $secondDate = now()->subDays($diasProvCorregir)->toDateString();
        $thirdDate = now()->subDays($diasValEvalTodo)->toDateString();

        Stamp::where(function ($query) use ($firstDate) {
            $query->where('stamp_status_id', 1)
                ->whereDate('created_at', $firstDate);
        })
            ->orWhere(function ($query) use ($secondDate) {
                $query->where('stamp_status_id', 6)
                    ->whereDate('reject_date', $secondDate);
            })
            ->chunkById(100, function ($stamps) {
                foreach ($stamps as $stamp) {
                    $stamp->stamp_status_id = 5;
                    $stamp->save();

                    Notification::create([
                        'user_id' => $stamp->provider_id,
                        'title' => 'El tiempo para subir evidencias ha expirado.',
                        'description' => 'Ha expirado el expediente su fecha de validez.',
                        'link' => 'usuario/dashboard',
                    ]);
                }
            });

        Stamp::where(function ($query) use ($thirdDate) {
            $query->where('stamp_status_id', 3)
                ->whereDate('take_review_date', $thirdDate);
        })
            ->chunkById(100, function ($stamps) {
                foreach ($stamps as $stamp) {
                    $stamp->stamp_status_id = 5;
                    $stamp->save();

                    Notification::create([
                        'user_id' => $stamp->reviewer_id,
                        'title' => 'El tiempo de revisión ha expirado.',
                        'description' => 'El tiempo de revisión ha expirado.',
                        'link' => '/admin',
                    ]);
                }
            });
    }
}
