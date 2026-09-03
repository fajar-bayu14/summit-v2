<?php

namespace App\Http\Requests\Staff;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMitraStaffRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:guide,porter,petugas'],
            'telepon' => ['required', 'string', 'max:50'],
            'is_available' => ['nullable', 'boolean'],
            'jadwal_tugas' => ['nullable', 'string'],
        ];
    }
}
