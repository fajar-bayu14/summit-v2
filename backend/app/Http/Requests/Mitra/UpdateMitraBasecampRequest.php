<?php

namespace App\Http\Requests\Mitra;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMitraBasecampRequest extends FormRequest
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
        $isPatch = $this->isMethod('PATCH');
        $prefix = $isPatch ? 'sometimes|' : '';

        return [
            'nama_basecamp' => $prefix.'required|string|max:255',
            'latitude' => 'sometimes|nullable|string|max:50',
            'longitude' => 'sometimes|nullable|string|max:50',
            'jam_operasional' => 'sometimes|nullable|string|max:100',
        ];
    }
}
