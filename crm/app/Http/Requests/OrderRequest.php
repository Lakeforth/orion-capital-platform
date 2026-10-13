<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'vendor_id' => ['required', Rule::exists('vendors', 'id')],
            'cart_items' => ['required', 'array', 'min:1'],
            'total' => ['required'],
            'delivery_mode' => ['required', 'in:Delivery,Pickup,ShopForMe'],
            'payment_method' => ['required', 'in:wallet,online,link,bank_transfer'],
            'delivery_address' => ['required'],
            'vendor_note' => ['nullable'],
            'rider_note' => ['nullable'],
            'map_lat' => ['required'],
            'map_lng' => ['required'],
            'cart_items.*.id' => ['required'],
            'cart_items.*.quantity' => ['required'],
            'cart_items.*.item_id' => ['required', 'uuid'],
            'cart_items.*.title' => ['required', 'string'],
            'cart_items.*.price' => ['required', 'numeric', 'min:0'],
            'cart_items.*.selectedAddons' => ['array']
        ];
    }
    public function messages(): array
    {
        return [
            'vendor_id.required' => 'Something went wrong, vendor not found.',
            'vendor_id.exists' => 'Something went wrong, vendor not found.',
            'cart_items.required' => 'No items in your cart',
            'map_lat.required' => 'Invalid delivery address provided',
            'map_lng.required' => 'Invalid delivery address provided',
            'cart_items.*.item_id.required' => 'Invalid cart item, please clear cart',
            'cart_items.*.title.required' => 'Invalid cart item, please clear cart',
            'cart_items.*.price.required' => 'Invalid cart item, please clear cart',
        ];
    }

}
