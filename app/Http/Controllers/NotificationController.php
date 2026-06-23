<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResource
    {
        $userId = (int) Auth::id();

        $shouldPaginate = $request->has('page');

        $resp = $this->notificationService->getNotificationsForUser(
            $userId,
            $shouldPaginate,
        );

        return NotificationResource::collection($resp['data'])->additional([
            'unread' => $resp['unread'],
            'unview' => $resp['unview'],
        ]);
    }

    /**
     * Read the specified notifications.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        try {
            if ($notification->user_id !== Auth::id()) {
                return response()->json(['message' => 'No autorizado para marcar esta notificación como leída.'], 403);
            }

            $this->notificationService->markAsRead($notification);

            return response()->json(['message' => 'Se ha marcado la notificación como leída.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al marcar la notificación como leída.'], 500);
        }
    }

    /**
     * Read all notifications.
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $this->notificationService->markAllAsRead();

            return response()->json(['message' => 'Se han marcado las notificaciones como leídas.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al marcar las notificaciones como leídas.'], 500);
        }
    }

    /**
     * View all notifications.
     */
    public function markAllAsViewed(): JsonResponse
    {
        try {
            $this->notificationService->markAllAsViewed();

            return response()->json(['message' => 'Se han marcado las notificaciones como vistas.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al marcar las notificaciones como vistas.'], 500);
        }

    }
}
