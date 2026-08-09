<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('customer')?->id;

        return [
            'entry_type' => ['required', Rule::in(['crm', 'leads'])], 'refer_type' => ['required', Rule::in(['self', 'staff'])], 'referred_by_technician_id' => ['nullable', 'required_if:refer_type,staff', 'exists:technicians,id'], 'customer_type' => ['required', Rule::in(['individual', 'company', 'government'])], 'customer_name' => ['required', 'max:150'], 'company_name' => ['nullable', 'max:150'], 'contact_person' => ['nullable', 'max:120'],
            'mobile' => ['required', 'max:20'], 'alternate_mobile' => ['nullable', 'max:20'], 'email' => ['nullable', 'email', 'max:190'], 'whatsapp' => ['nullable', 'max:20'], 'gst_number' => ['nullable', 'max:20', Rule::unique('customers')->ignore($id)], 'pan_number' => ['nullable', 'max:15'],
            'address' => ['required'], 'city' => ['required', 'max:100'], 'state' => ['required', 'max:100'], 'pin_code' => ['required', 'max:10'], 'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180'], 'status' => ['required', Rule::in(['active', 'inactive'])], 'notes' => ['nullable'],
            'contacts' => ['nullable', 'array'], 'contacts.*.name' => ['required_with:contacts.*.mobile', 'nullable', 'max:120'], 'contacts.*.designation' => ['nullable', 'max:100'], 'contacts.*.mobile' => ['nullable', 'max:20'], 'contacts.*.alternate_mobile' => ['nullable', 'max:20'], 'contacts.*.email' => ['nullable', 'email'], 'contacts.*.whatsapp' => ['nullable', 'max:20'], 'contacts.*.is_primary' => ['nullable', 'boolean'], 'contacts.*.notes' => ['nullable'],
        ];
    }
}
