<?php

namespace App\Http\Requests\Mitra;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMitraProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'mitra';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mitra = $this->user()->mitra;
        $mitraId = $mitra?->id;

        $isPatch = $this->isMethod('PATCH');
        $prefix = $isPatch ? 'sometimes|' : '';

        return [
            'nama_pemilik' => $prefix.'required|string|max:255',
            'telepon' => $prefix.'required|string|max:255',
            'alamat' => $prefix.'required|string',
            'deskripsi' => 'sometimes|nullable|string',
            'npwp' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('mitras', 'npwp')->ignore($mitraId),
            ],
            'nik' => [
                $prefix.'required',
                'string',
                'max:255',
                Rule::unique('mitras', 'nik')->ignore($mitraId),
            ],
            'rekening_bank' => [
                $prefix.'required',
                'string',
                'max:255',
                Rule::unique('mitras', 'rekening_bank')->ignore($mitraId),
            ],
            'nama_rekening' => $prefix.'required|string|max:255',
            'bank' => $prefix.'required|string|max:255',
            'ewallet' => 'sometimes|nullable|string|max:255',
        ];
    }
}
