<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationStatus;
use Illuminate\Validation\Rule;

class ListNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'perPage' => $this->input('perPage', 20),
        ]);
    }

    public function rules(): array
    {
        return [
            'channel' => [
                'nullable',
                'string',
                'in:' . implode(',', array_column(NotificationChannel::cases(), 'value')),
            ],
            'status' => [
                'nullable',
                'string',
                'in:' . implode(',', array_column(NotificationStatus::cases(), 'value')),
            ],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function passedValidation()
    {
        $this->merge([
            'from' => $this->from
                ? \Carbon\Carbon::parse($this->from)->startOfDay()
                : null,

            'to' => $this->to
                ? \Carbon\Carbon::parse($this->to)->endOfDay()
                : null,
        ]);
    }
}
