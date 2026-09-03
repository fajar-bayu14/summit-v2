<?php

namespace App\Http\Requests\Logbook;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLogbookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'foto_summit' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'catatan_pendaki' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'string', 'max:50'],
            'longitude' => ['nullable', 'string', 'max:50'],
            'waktu_summit' => ['nullable', 'date'],
        ];
    }
}
