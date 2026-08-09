<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['brand_name' => ['required', 'string', 'max:120', Rule::unique('brands')->ignore($this->route('brand')?->id)]];
    }
}
