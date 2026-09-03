<?php

namespace App\Http\Requests\Staff;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMitraStaffRequest extends FormRequest
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
        return [
            'basecamp_id' => ['nullable', 'integer', 'exists:basecamps,id'],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'role' => ['sometimes', 'required', 'string', 'in:guide,porter,petugas'],
            'telepon' => ['sometimes', 'required', 'string', 'max:50'],
            'is_available' => ['sometimes', 'boolean'],
            'jadwal_tugas' => ['nullable', 'string'],
        ];
    }
}
