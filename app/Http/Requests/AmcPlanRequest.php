<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AmcPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_name' => ['required', 'string', 'max:150'],
            'machine_category_id' => ['required', 'exists:machine_categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'plan_type' => ['required', Rule::in(['comprehensive', 'semi_comprehensive', 'cleaning'])],
            'description' => ['nullable', 'string'],
            'duration' => ['required', Rule::in(['1_year', '2_years', '3_years'])],
            'parts_included' => ['required', Rule::in(['1', '0'])],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
