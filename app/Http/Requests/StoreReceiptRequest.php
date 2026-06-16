<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->has('data')) {
            return [
                'data'                       => ['required', 'array', 'min:1'],
                'data.*.id'                  => ['required', 'uuid'],
                'data.*.number'              => ['required', 'integer'],
                'data.*.dateOpen'            => ['required', 'date'],
                'data.*.dateClose'           => ['required', 'date'],
                'data.*.type'                => ['required', 'string'],
                'data.*.cashier'             => ['required', 'string'],
                'data.*.status'              => ['required', 'string'],
                'data.*.card'                => ['nullable', 'string'],
                'data.*.pos'                 => ['required', 'integer'],
                'data.*.total'               => ['required', 'numeric'],
                'data.*.shop'                => ['required', 'string'],
                'data.*.shift'               => ['nullable', 'integer'],
                // items / payments / discounts may be missing or empty arrays
                'data.*.items'               => ['nullable', 'array'],
                'data.*.items.*.code'        => ['required', 'integer'],
                'data.*.items.*.name'        => ['required', 'string'],
                'data.*.items.*.price'       => ['required', 'numeric'],
                'data.*.items.*.total'       => ['required', 'numeric'],
                'data.*.items.*.discountTotal' => ['required', 'numeric'],
                'data.*.items.*.qty'         => ['required', 'numeric'],
                'data.*.items.*.roundTotal'  => ['required', 'numeric'],
                'data.*.items.*.status'      => ['required', 'boolean'],
                'data.*.items.*.no'          => ['required', 'integer'],
                'data.*.payments'            => ['nullable', 'array'],
                'data.*.payments.*.type'     => ['required', 'string'],
                'data.*.payments.*.total'    => ['required', 'numeric'], // negative = change/refund
                'data.*.discounts'           => ['nullable', 'array'],
                'data.*.discounts.*.receipt' => ['required', 'boolean'],
                'data.*.discounts.*.total'   => ['required', 'numeric'],
                'data.*.discounts.*.no'      => ['nullable', 'integer'],
            ];
        }

        return [
            'id'               => ['required', 'uuid'],
            'number'           => ['required', 'integer'],
            'dateOpen'         => ['required', 'date'],
            'dateClose'        => ['required', 'date'],
            'type'             => ['required', 'string'],
            'cashier'          => ['required', 'string'],
            'status'           => ['required', 'string'],
            'card'             => ['nullable', 'string'],
            'pos'              => ['required', 'integer'],
            'total'            => ['required', 'numeric'],
            'shop'             => ['required', 'string'],
            'shift'            => ['nullable', 'integer'],
            'items'            => ['nullable', 'array'],
            'items.*.code'     => ['required', 'integer'],
            'items.*.name'     => ['required', 'string'],
            'items.*.price'    => ['required', 'numeric'],
            'items.*.total'    => ['required', 'numeric'],
            'items.*.discountTotal' => ['required', 'numeric'],
            'items.*.qty'      => ['required', 'numeric'],
            'items.*.roundTotal'    => ['required', 'numeric'],
            'items.*.status'   => ['required', 'boolean'],
            'items.*.no'       => ['required', 'integer'],
            'payments'         => ['nullable', 'array'],
            'payments.*.type'  => ['required', 'string'],
            'payments.*.total' => ['required', 'numeric'], // negative = change/refund
            'discounts'        => ['nullable', 'array'],
            'discounts.*.receipt' => ['required', 'boolean'],
            'discounts.*.total'   => ['required', 'numeric'],
            'discounts.*.no'      => ['nullable', 'integer'],
        ];
    }
}
