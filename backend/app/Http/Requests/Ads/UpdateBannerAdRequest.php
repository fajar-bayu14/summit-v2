<?php

namespace App\Http\Requests\Ads;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerAdRequest extends FormRequest
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
            'mitra_id' => ['nullable', 'integer', 'exists:mitras,id'],
            'judul' => ['sometimes', 'string', 'max:255'],
            'gambar' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'posisi' => ['sometimes', 'string', 'in:home_top,mountain_detail,search_sidebar'],
            'tanggal_mulai' => ['sometimes', 'date'],
            'tanggal_selesai' => ['sometimes', 'date', 'after_or_equal:tanggal_mulai'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
