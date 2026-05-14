<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['nullable', 'string', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'check_in' => '入住日期',
            'check_out' => '退房日期',
            'guests' => '旅客人数',
            'guest_name' => '姓名',
            'guest_email' => '邮箱',
            'notes' => '备注',
        ];
    }
}
