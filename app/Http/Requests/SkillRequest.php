<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'max:100', Rule::unique('skills')->ignore($this->route('skill')?->id)], 'category' => ['required', Rule::in(['electrical', 'mechanical', 'hvac', 'plumbing', 'installation', 'maintenance', 'repair', 'other'])], 'description' => ['nullable'], 'is_active' => ['nullable', 'boolean']];
    }
}
