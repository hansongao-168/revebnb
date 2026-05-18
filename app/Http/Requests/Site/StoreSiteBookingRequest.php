<?php

namespace App\Http\Requests\Site;

use App\Models\Listing;
use App\Support\GuestComposition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'guest_adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'guest_children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'guest_infants' => ['nullable', 'integer', 'min:0', 'max:20'],
            'guest_pets' => ['nullable', 'integer', 'min:0', 'max:20'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:20'],
            'pets' => ['nullable', 'integer', 'min:0', 'max:20'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['nullable', 'string', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $listing = $this->route('listing');

            if (! $listing instanceof Listing) {
                return;
            }

            $composition = GuestComposition::fromArray($this->all());

            if (! $composition->fitsListing($listing)) {
                $validator->errors()->add('adults', '所选人数超出该房源接待上限。');
            }
        });
    }

    public function guestComposition(): GuestComposition
    {
        return GuestComposition::fromArray($this->validated());
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
            'guest_adults' => '成人',
            'guest_children' => '儿童',
            'guest_infants' => '婴儿',
            'guest_pets' => '宠物',
            'adults' => '成人',
            'children' => '儿童',
            'infants' => '婴儿',
            'pets' => '宠物',
            'guest_name' => '姓名',
            'guest_email' => '邮箱',
            'notes' => '备注',
        ];
    }
}
