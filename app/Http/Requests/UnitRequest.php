<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_name' => ['required', 'string', 'max:50', Rule::unique('units')->ignore($this->route('unit')?->id)],
        ];
    }
}
