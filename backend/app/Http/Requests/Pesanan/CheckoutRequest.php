<?php

namespace App\Http\Requests\Pesanan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'anggotas' => ['required', 'array', 'min:1'],
            'anggotas.*.nama_anggota' => ['required', 'string', 'max:255'],
            'anggotas.*.nik_identitas' => ['required', 'string', 'size:16'],
            'anggotas.*.telepon' => ['nullable', 'string', 'max:20'],
            'anggotas.*.telepon_darurat' => ['nullable', 'string', 'max:20'],
            'anggotas.*.hubungan_darurat' => ['nullable', 'string', 'max:50'],
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
            'anggotas.required' => 'Anggota pendaki wajib diisi minimal 1 orang.',
            'anggotas.*.nama_anggota.required' => 'Nama anggota wajib diisi.',
            'anggotas.*.nik_identitas.required' => 'NIK identitas wajib diisi.',
            'anggotas.*.nik_identitas.size' => 'NIK identitas harus 16 digit.',
        ];
    }
}
