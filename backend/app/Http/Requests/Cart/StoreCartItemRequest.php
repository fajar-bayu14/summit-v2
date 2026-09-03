<?php

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
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
            'produk_id' => ['required', 'integer', 'exists:produks,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'jalur_id' => ['required', 'integer', 'exists:jalur_pendakians,id'],
            'tanggal_booking' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'tanggal_selesai_booking' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:tanggal_booking'],
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
            'produk_id.required' => 'ID produk wajib diisi.',
            'produk_id.exists' => 'Produk tidak ditemukan.',
            'qty.required' => 'Jumlah produk wajib diisi.',
            'qty.min' => 'Jumlah produk minimal 1.',
            'jalur_id.required' => 'ID jalur pendakian wajib diisi.',
            'jalur_id.exists' => 'Jalur pendakian tidak ditemukan.',
            'tanggal_booking.required' => 'Tanggal booking wajib diisi.',
            'tanggal_booking.after_or_equal' => 'Tanggal booking minimal hari ini.',
            'tanggal_selesai_booking.after_or_equal' => 'Tanggal selesai booking harus setelah tanggal booking.',
            'tanggal_selesai_sewa.after_or_equal' => 'Tanggal selesai sewa harus setelah tanggal mulai sewa.',
        ];
    }
}
