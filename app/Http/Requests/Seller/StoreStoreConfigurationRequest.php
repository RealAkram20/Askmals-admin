<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'timing' => 'required|string',
            'status' => 'required|string|in:online,offline',
            'max_delivery_distance' => 'nullable|numeric',
            'domestic_shipping_charges' => 'nullable|numeric',
            'international_shipping_charges' => 'nullable|numeric',
            'description' => 'nullable|string',
            'about_us' => 'nullable|string',
            'promotional_text' => 'nullable|string',
            'return_replacement_policy' => 'required|string',
            'refund_policy' => 'required|string',
            'terms_and_conditions' => 'required|string',
            'delivery_policy' => 'required|string',
            'metadata' => 'nullable|array',
            'pos_upi_vpa' => ['nullable', 'string', 'max:100'],
            'pos_upi_payee_name' => ['nullable', 'string', 'max:100'],
            'receipt_template' => ['nullable', 'array'],
            'receipt_template.footer_note' => ['nullable', 'string', 'max:500'],
            'pos_payment_config' => ['nullable', 'array'],
            'pos_payment_config.cash' => ['nullable', 'boolean'],
            'pos_payment_config.upi' => ['nullable', 'boolean'],
            'pos_payment_config.online_qr' => ['nullable', 'boolean'],
            'pos_payment_config.split' => ['nullable', 'boolean'],
            'pos_payment_config.custom_methods' => ['nullable', 'array'],
            'pos_payment_config.custom_methods.*.name' => ['required_with:pos_payment_config.custom_methods', 'string', 'max:50'],
            'pos_payment_config.custom_methods.*.instructions' => ['nullable', 'string', 'max:250'],
            'pos_payment_config.custom_methods.*.icon' => ['nullable', 'string', 'max:10'],
            'pos_payment_config.custom_methods.*.enabled' => ['nullable', 'boolean'],
            'metadata.seo_title' => 'nullable|string|max:255',
            'metadata.seo_keywords' => 'nullable|array',
            'metadata.seo_keywords.*' => 'nullable|string|max:60',
            'metadata.seo_description' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'timing.required' => 'Store timing is required',
            'timing.string' => 'Store timing must be a string',
            'status.required' => 'Store status is required',
            'status.string' => 'Store status must be a string',
            'status.in' => 'Store status must be either online or offline',
            'max_delivery_distance.required' => 'Maximum delivery distance is required',
            'max_delivery_distance.numeric' => 'Maximum delivery distance must be a number',
            'domestic_shipping_charges.required' => 'Domestic shipping charges are required',
            'domestic_shipping_charges.numeric' => 'Domestic shipping charges must be a number',
            'international_shipping_charges.required' => 'International shipping charges are required',
            'international_shipping_charges.numeric' => 'International shipping charges must be a number',
            'return_replacement_policy.required' => 'Return and replacement policy is required',
            'refund_policy.required' => 'Refund policy is required',
            'terms_and_conditions.required' => 'Terms and conditions are required',
            'delivery_policy.required' => 'Delivery policy is required',
        ];
    }

}
