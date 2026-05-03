<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationPriority;
use Illuminate\Validation\Rule;

class StoreBatchNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array', 'min:1', 'max:1000'],
            'notifications.*.channel' => [
                'required',
                'string',
                'in:' . implode(',', array_column(NotificationChannel::cases(), 'value')),
            ],
            'notifications.*.recipient' => ['required', 'string'],
            'notifications.*.content' => ['required', 'string', 'max:1000'],
            'notifications.*.priority' => [
                'nullable',
                'string',
                'in:' . implode(',', array_column(NotificationPriority::cases(), 'value')),
            ],
            'priority' => [
                'nullable',
                'string',
                'in:' . implode(',', array_column(NotificationPriority::cases(), 'value')),
            ],
        ];
    }
}
