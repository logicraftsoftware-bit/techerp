<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Machine;
use App\Models\MachineCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MachineImportController extends Controller
{
    private const COLUMNS = [
        'brand_name', 'machine_category_name', 'machine_name', 'model', 'manufacturing_date',
        'service_period', 'buying_price', 'selling_price', 'total_stock', 'location_name', 'status',
    ];

    public function __construct()
    {
        $this->middleware('permission:machines,create');
    }

    public function create(): View
    {
        return view('master.machines.import');
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

            $brandName = trim((string) ($payload['brand_name'] ?? ''));
            $categoryName = trim((string) ($payload['machine_category_name'] ?? ''));
            $brand = $brandName !== '' ? Brand::whereRaw('LOWER(brand_name) = ?', [strtolower($brandName)])->first() : null;
            $category = $categoryName !== '' ? MachineCategory::whereRaw('LOWER(category_name) = ?', [strtolower($categoryName)])->first() : null;

            if (! $brand) {
                $errors[] = "Row {$rowNumber}: Brand \"{$brandName}\" not found -- add it in Brand Master first.";

                continue;
            }
            if (! $category) {
                $errors[] = "Row {$rowNumber}: Machine Category \"{$categoryName}\" not found -- add it in Machine Category Master first.";

                continue;
            }

            $validator = Validator::make($payload, [
                'machine_name' => ['required', 'string', 'max:150'],
                'model' => ['required', 'string', 'max:100'],
                'manufacturing_date' => ['nullable', 'date', 'before_or_equal:today'],
                'service_period' => ['required', Rule::in(['4_months', '6_months', '1_year', '2_years'])],
                'buying_price' => ['required', 'numeric', 'min:0'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'total_stock' => ['required', 'integer', 'min:0'],
                'location_name' => ['required', 'string', 'max:190'],
                'status' => ['nullable', Rule::in(['active', 'inactive'])],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".$validator->errors()->first();

                continue;
            }

            $clean = $validator->validated();
            $clean['manufacturing_date'] = $clean['manufacturing_date'] ?? null;
            $clean['status'] = ($clean['status'] ?? null) ?: 'active';
            $clean['brand_id'] = $brand->id;
            $clean['brand'] = $brand->brand_name;
            $clean['machine_category_id'] = $category->id;

            Machine::create($clean);
            $imported++;
        }
        fclose($handle);

        return to_route('machines.index')
            ->with('success', "{$imported} machine(s) imported.".($errors ? ' Some rows were skipped -- see details below.' : ''))
            ->with('import_errors', $errors);
    }

    public function sample(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS);
            fputcsv($out, ['Kutchina', 'Chimney', 'Kutchina Amaze 90 Kitchen Chimney', 'Amaze 90', '2026-01-15', '4_months', '14500', '21990', '10', 'Main Warehouse - Sector 5', 'active']);
            fclose($out);
        }, 'machines-sample.csv', ['Content-Type' => 'text/csv']);
    }
}
