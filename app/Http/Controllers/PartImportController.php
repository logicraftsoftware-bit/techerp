<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\MachineCategory;
use App\Models\Part;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartImportController extends Controller
{
    private const COLUMNS = [
        'part_name', 'category', 'brand', 'start_date', 'unit',
        'dealer_price', 'mrp', 'total_stock',
        'has_amc', 'has_warranty', 'warranty_months', 'status',
    ];

    public function __construct()
    {
        $this->middleware('permission:parts,create');
    }

    public function create(): View
    {
        return view('parts-inventory.parts-import');
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

            $categoryName = trim((string) ($payload['category'] ?? ''));
            $unitName = trim((string) ($payload['unit'] ?? ''));
            $brandName = trim((string) ($payload['brand'] ?? ''));

            $category = $categoryName !== '' ? MachineCategory::whereRaw('LOWER(category_name) = ?', [strtolower($categoryName)])->first() : null;
            $unit = $unitName !== '' ? Unit::whereRaw('LOWER(unit_name) = ?', [strtolower($unitName)])->first() : null;
            $brand = $brandName !== '' ? Brand::whereRaw('LOWER(brand_name) = ?', [strtolower($brandName)])->first() : null;

            if (! $category) {
                $errors[] = "Row {$rowNumber}: Category \"{$categoryName}\" not found -- add it in Machine Category Master first.";

                continue;
            }
            if (! $unit) {
                $errors[] = "Row {$rowNumber}: Unit \"{$unitName}\" not found -- add it in Unit Master first.";

                continue;
            }
            if ($brandName !== '' && ! $brand) {
                $errors[] = "Row {$rowNumber}: Brand \"{$brandName}\" not found -- add it in Brand Master first.";

                continue;
            }

            $hasWarranty = in_array(strtolower(trim((string) ($payload['has_warranty'] ?? ''))), ['y', 'yes', '1', 'true'], true);

            $validator = Validator::make([
                'part_name' => $payload['part_name'] ?? null,
                'start_date' => $payload['start_date'] ?? null,
                'dealer_price' => $payload['dealer_price'] ?? null,
                'mrp' => $payload['mrp'] ?? null,
                'total_stock' => $payload['total_stock'] ?? null,
                'warranty_months' => $payload['warranty_months'] ?? null,
                'status' => $payload['status'] ?? null,
                'has_warranty' => $hasWarranty,
            ], [
                'part_name' => ['required', 'max:150'],
                'start_date' => ['nullable', 'date'],
                'dealer_price' => ['required', 'numeric', 'min:0'],
                'mrp' => ['required', 'numeric', 'min:0'],
                'total_stock' => ['nullable', 'integer', 'min:0'],
                'warranty_months' => ['nullable', 'required_if:has_warranty,1', 'integer', 'min:0'],
                'status' => ['nullable', Rule::in(['active', 'inactive'])],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".$validator->errors()->first();

                continue;
            }

            $clean = $validator->validated();
            unset($clean['has_warranty']);

            Part::create([
                'part_code' => 'PT-'.str_pad((string) (Part::count() + 1), 5, '0', STR_PAD_LEFT),
                'part_name' => $clean['part_name'],
                'machine_category_id' => $category->id,
                'brand_id' => $brand?->id,
                'start_date' => $clean['start_date'] ?: null,
                'unit_id' => $unit->id,
                'purchase_price' => $clean['dealer_price'],
                'selling_price' => $clean['mrp'],
                'current_stock' => $clean['total_stock'] ?? 0,
                'has_amc' => in_array(strtolower(trim((string) ($payload['has_amc'] ?? ''))), ['y', 'yes', '1', 'true'], true),
                'has_warranty' => $hasWarranty,
                'warranty_months' => $hasWarranty ? $clean['warranty_months'] : null,
                'status' => $clean['status'] ?: 'active',
            ]);
            $imported++;
        }
        fclose($handle);

        return to_route('parts.index')
            ->with('success', "{$imported} part(s) imported.".($errors ? ' Some rows were skipped -- see details below.' : ''))
            ->with('import_errors', $errors);
    }

    public function sample(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS);
            fputcsv($out, ['Control Board', 'Chimney', 'Kutchina', '2026-01-15', 'piece', '850', '1200', '25', 'Y', 'Y', '12', 'active']);
            fclose($out);
        }, 'parts-sample.csv', ['Content-Type' => 'text/csv']);
    }
}
