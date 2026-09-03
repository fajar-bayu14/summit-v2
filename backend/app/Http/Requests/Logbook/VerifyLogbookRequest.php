<?php

namespace App\Http\Requests\Logbook;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyLogbookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['mitra', 'admin'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status_validasi' => ['required', 'string', 'in:approved,rejected'],
            'catatan_petugas' => ['required_if:status_validasi,rejected', 'nullable', 'string', 'max:500'],
        ];
    }
}
