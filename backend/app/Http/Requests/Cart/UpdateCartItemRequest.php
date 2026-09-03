<?php

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'pendaki';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'qty' => ['nullable', 'integer', 'min:1'],
            'tanggal_mulai_sewa' => ['nullable', 'date', 'date_format:Y-m-d'],
            'tanggal_selesai_sewa' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai_sewa'],
            'catatan_item' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qty.min' => 'Jumlah produk minimal 1.',
            'tanggal_selesai_sewa.after_or_equal' => 'Tanggal selesai sewa harus setelah tanggal mulai sewa.',
        ];
    }
}
