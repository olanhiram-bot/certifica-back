<?php

namespace App\Traits;

trait CreatedUpdatedBy
{
    public static function bootCreatedUpdatedBy(): void
    {
        // Use auth()->id() inside closures to avoid null-access warnings
        // and to ensure callbacks are set even when no user is authenticated at boot time.
        static::creating(function ($model) {
            $userId = auth()->id();
            if ($userId) {
                if (! $model->isDirty('created_by')) {
                    $model->created_by = $userId;
                }
                if (! $model->isDirty('updated_by')) {
                    $model->updated_by = $userId;
                }
            }
        });

        static::updating(function ($model) {
            $userId = auth()->id();
            if ($userId && ! $model->isDirty('updated_by')) {
                $model->updated_by = $userId;
            }
        });

        static::deleting(function ($model) {
            $userId = auth()->id();
            if ($userId && ! $model->isDirty('deleted_by')) {
                $model->deleted_by = $userId;
            }
        });
    }
}
