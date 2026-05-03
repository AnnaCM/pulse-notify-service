<?php

namespace App\Http\Controllers;

use App\DTOs\Notification\CreateBatchNotificationDTO;
use App\DTOs\Notification\CreateNotificationDTO;
use App\DTOs\Notification\ListNotificationsDTO;
use App\Http\Requests\ListNotificationsRequest;
use App\Http\Requests\StoreBatchNotificationRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Support\Enums\NotificationPriority;
use App\UseCases\Notification\CancelBatchNotification;
use App\UseCases\Notification\CancelNotification;
use App\UseCases\Notification\CreateBatchNotification;
use App\UseCases\Notification\CreateNotification;
use App\UseCases\Notification\GetBatchNotification;
use App\UseCases\Notification\GetNotification;
use App\UseCases\Notification\ListNotifications;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class NotificationController extends BaseController
{
    public function store(StoreNotificationRequest $request, CreateNotification $useCase)
    {
        $data = $request->validated();

        $dto = new CreateNotificationDTO(
            channel: $data['channel'],
            recipient: $data['recipient'],
            content: $data['content'],
            priority: $data['priority'] ?? NotificationPriority::NORMAL->value,
        );

        $notification = $useCase->execute($dto);

        return (new NotificationResource($notification))
            ->response()
            ->setStatusCode(201);
    }

    public function batch(StoreBatchNotificationRequest $request, CreateBatchNotification $useCase)
    {
        $data = $request->validated();

        $batchPriority = $data['priority'] ?? NotificationPriority::NORMAL->value;

        $notifications = array_map(
            fn ($item) => new CreateNotificationDTO(
                channel: $item['channel'],
                recipient: $item['recipient'],
                content: $item['content'],
                priority: $item['priority'] ?? $batchPriority,
            ),
            $data['notifications']
        );

        $dto = new CreateBatchNotificationDTO(
            notifications: $notifications,
            priority: $batchPriority
        );

        $batchId = $useCase->execute($dto);

        return response()->json([
            'batch_id' => $batchId,
            'queued' => count($notifications),
        ], 201);
    }

    public function cancel(string $id, CancelNotification $useCase)
    {
        Validator::make(['id' => $id], [
            'id' => ['required', 'uuid'],
        ])->validate();

        $useCase->execute($id);

        return response()->json(['message' => 'Notification Cancelled'], 202);
    }

    public function cancelBatch(string $batchId, CancelBatchNotification $useCase)
    {
        Validator::make(['batchId' => $batchId], [
            'batchId' => ['required', 'uuid'],
        ])->validate();

        $useCase->execute($batchId);

        return response()->json(['message' => 'Batch Cancelled'], 202);
    }

    public function index(ListNotificationsRequest $request, ListNotifications $useCase)
    {
        $data = $request->validated();

        $dto = new ListNotificationsDTO(
            status: $data['status'] ?? null,
            channel: $data['channel'] ?? null,
            from: $data['from'] ?? null,
            to: $data['to'] ?? null,
            perPage: $data['perPage'] ?? 20
        );

        $notifications = $useCase->execute($dto);
        return NotificationResource::collection($notifications);
    }

    public function show(string $id, GetNotification $useCase)
    {
        Validator::make(['id' => $id], [
            'id' => ['required', 'uuid'],
        ])->validate();

        $notification = $useCase->execute($id);

        return new NotificationResource($notification);
    }

    public function showByBatch(string $batchId, GetBatchNotification $useCase)
    {
        Validator::make(['batchId' => $batchId], [
            'batchId' => ['required', 'uuid'],
        ])->validate();

        $notifications = $useCase->execute($batchId);

        return NotificationResource::collection($notifications);
    }
}
