<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportController extends Controller
{
    private const COLUMNS = [
        'customer_type', 'customer_name', 'company_name', 'contact_person', 'date_of_birth',
        'mobile', 'alternate_mobile', 'email', 'whatsapp', 'gst_number', 'pan_number',
        'address', 'city', 'state', 'pin_code', 'status', 'notes',
    ];

    public function __construct()
    {
        $this->middleware('permission:customers,create');
    }

    public function create(): View
    {
        return view('master.customers.import');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => str($h)->trim()->lower()->snake()->toString(), fgetcsv($handle) ?: []);

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            $payload = array_intersect_key($data, array_flip(self::COLUMNS));

            $validator = Validator::make($payload, [
                'customer_type' => ['nullable', Rule::in(['individual', 'company', 'government'])],
                'customer_name' => ['required', 'max:150'],
                'company_name' => ['nullable', 'max:150'],
                'contact_person' => ['nullable', 'max:120'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'mobile' => ['required', 'max:20'],
                'alternate_mobile' => ['nullable', 'max:20'],
                'email' => ['nullable', 'email', 'max:190'],
                'whatsapp' => ['nullable', 'max:20'],
                'gst_number' => ['nullable', 'max:20', Rule::unique('customers')],
                'pan_number' => ['nullable', 'max:15'],
                'address' => ['required'],
                'city' => ['required', 'max:100'],
                'state' => ['required', 'max:100'],
                'pin_code' => ['required', 'max:10'],
                'status' => ['nullable', Rule::in(['active', 'inactive'])],
                'notes' => ['nullable'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".$validator->errors()->first();

                continue;
            }

            $clean = $validator->validated();
            $clean['customer_type'] = ($clean['customer_type'] ?? null) ?: 'individual';
            $clean['status'] = ($clean['status'] ?? null) ?: 'active';
            $clean['entry_type'] = 'crm';
            $clean['refer_type'] = 'self';
            foreach (['company_name', 'contact_person', 'date_of_birth', 'alternate_mobile', 'email', 'whatsapp', 'gst_number', 'pan_number', 'notes'] as $optional) {
                $clean[$optional] = ($clean[$optional] ?? null) ?: null;
            }

            Customer::create($clean);
            $imported++;
        }
        fclose($handle);

        return to_route('customers.index')
            ->with('success', "{$imported} customer(s) imported.".($errors ? ' Some rows were skipped -- see details below.' : ''))
            ->with('import_errors', $errors);
    }

    public function sample(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS);
            fputcsv($out, ['individual', 'John Doe', '', '', '1990-05-20', '9876543210', '', 'john@example.com', '', '', '', '123 Main Street', 'Kolkata', 'West Bengal', '700001', 'active', '']);
            fclose($out);
        }, 'customers-sample.csv', ['Content-Type' => 'text/csv']);
    }
}
