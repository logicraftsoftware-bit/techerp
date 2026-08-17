<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommissionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_name' => ['required', 'string', 'max:100', Rule::unique('commission_types')->ignore($this->route('commission_type')?->id)],
            'calculation_type' => ['required', Rule::in(['percentage', 'flat'])],
            'value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
