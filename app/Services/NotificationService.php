<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Stamp;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * @return array{
     *   data: \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Notification>|\Illuminate\Support\Collection<int, \App\Models\Notification>,
     *   unread: int,
     *   unview: int
     * }
     */
    public function getNotificationsForUser(int $userId, bool $paginate = true): array
    {
        $query = Notification::where('user_id', $userId)->orderBy('created_at', 'desc');

        $notifications = $paginate
            ? $query->paginate(10)->onEachSide(1)
            : $query->paginate(100)->onEachSide(1);

        $unRead = Notification::where('user_id', $userId)->where('is_read', false)->count();
        $unView = Notification::where('user_id', $userId)->where('is_view', false)->count();

        return [
            'data' => $notifications,
            'unread' => (int) $unRead,
            'unview' => (int) $unView,
        ];
    }

    public function markAsRead(Notification $notification): Notification
    {
        $userId = Auth::id();
        if (! $userId || $notification->user_id !== $userId) {
            throw new AuthorizationException('No autorizado para marcar notificación como leída.');
        }

        $notification->is_read = true;
        $notification->save();

        return $notification->refresh();
    }

    public function markAllAsRead(): int
    {
        $userId = Auth::id();
        if (! $userId) {
            return 0;
        }

        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function markAllAsViewed(): int
    {
        $userId = Auth::id();
        if (! $userId) {
            return 0;
        }

        return Notification::where('user_id', $userId)
            ->where('is_view', false)
            ->update(['is_view' => true]);
    }

    public function sendStampNotification(Stamp $stamp, int $notificationType): void
    {
        $notificationInfo = [
            1 => ['title' => 'Hay un nuevo expediente pendiente por evaluar.', 'descriptions' => 'Se registró un nuevo expedienteque requiere tu evaluación.', 'users' => null, 'link' => '/admin/modulos?stampId={$stamp->id}'],
            2 => ['title' => 'El expediente se esta revisando.', 'descriptions' => 'Su expediente se encuentra en revisión.', 'users' => $stamp->provider_id, 'link' => '/usuario/programa-modulos'],
            3 => ['title' => 'El expediente fue Aprobado.', 'descriptions' => 'El expediente ha sido aprobado. Espere instrucciones para el siguiente paso.', 'users' => $stamp->provider_id, 'link' => '/usuario'],
            4 => ['title' => 'El expediente fue rechazado.', 'descriptions' => 'El expediente ha sido rechazado, por favor realice las correcciones necesarias.', 'users' => $stamp->provider_id, 'link' => '/usuario/programa-modulos'],
            5 => ['title' => 'El expediente fue corregido.', 'descriptions' => 'El expediente ha sido corregido, por favor vuelva a evaluar.', 'users' => $stamp->reviewer_id, 'link' => '/admin/modulos?stampId={$stamp->id}'],
        ];

        $notificationData = $notificationInfo[$notificationType];
        $users = $this->getUsers($notificationData['users']);
        foreach ($users as $user) {
            Notification::create(
                [
                    'user_id' => $user['id'],
                    'title' => $notificationData['title'],
                    'description' => $notificationData['descriptions'],
                    'link' => $notificationData['link'],
                ],
            );
        }
    }

    /**
     * @return array<int,array{id:int}>
     */
    public function getUsers(?int $userId): array
    {
        if ($userId !== null) {
            return User::select('id')->where('id', $userId)->get()->toArray();
        }

        return User::select('id')->where('auth_method_id', 2)->get()->toArray();
    }
}
