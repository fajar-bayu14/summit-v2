<?php

namespace App\Http\Requests\Refund;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessRefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:success,failed'],
            'tipe' => ['sometimes', 'string', 'in:auto,manual'],
            'bukti_transfer' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
