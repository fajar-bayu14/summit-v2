<?php

namespace App\Http\Requests\Refund;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
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
            'alasan' => ['required', 'string', 'max:1000'],
            'bank_tujuan' => ['required', 'string', 'max:50'],
            'rekening_tujuan' => ['required', 'string', 'max:50'],
            'nama_tujuan' => ['required', 'string', 'max:255'],
            'nominal' => ['nullable', 'numeric', 'min:1000'],
            'refund_category' => ['nullable', 'string', 'in:pre_trip,incident,force_majeure,dispute'],
        ];
    }
}
