<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationPriority;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => [
                'required',
                'string',
                'in:' . implode(',', array_column(NotificationChannel::cases(), 'value')),
            ],
            'recipient' => ['required', 'string'],
            'content' => ['required', 'string', 'max:1000'],
            'priority' => [
                'nullable',
                'string',
                'in:' . implode(',', array_column(NotificationPriority::cases(), 'value')),
            ],
        ];
    }
}
