<?php

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateCartRequest extends FormRequest
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
            'basecamp_id' => ['required', 'integer', 'exists:basecamps,id'],
            'jalur_id' => ['required', 'integer', 'exists:jalur_pendakians,id'],
            'tanggal_booking' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'tanggal_selesai_booking' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:tanggal_booking'],
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
            'basecamp_id.required' => 'ID basecamp wajib diisi.',
            'basecamp_id.exists' => 'Basecamp tidak ditemukan.',
            'jalur_id.required' => 'ID jalur pendakian wajib diisi.',
            'jalur_id.exists' => 'Jalur pendakian tidak ditemukan.',
            'tanggal_booking.required' => 'Tanggal booking wajib diisi.',
            'tanggal_booking.after_or_equal' => 'Tanggal booking minimal hari ini.',
            'tanggal_selesai_booking.after_or_equal' => 'Tanggal selesai booking harus setelah tanggal booking.',
        ];
    }
}
