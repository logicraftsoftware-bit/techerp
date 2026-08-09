<?php

namespace App\Http\Controllers;

use App\Http\Requests\MachineRequest;
use App\Models\Brand;
use App\Models\Machine;
use App\Models\MachineDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MachineController extends Controller
{
    public function index(Request $r): View
    {
        $records = Machine::with('customer')->when($r->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('machine_name', 'like', "%$s%")->orWhere('machine_code', 'like', "%$s%")->orWhere('serial_number', 'like', "%$s%")))->latest()->paginate(15)->withQueryString();

        return view('master.machines.index', compact('records'));
    }

    public function create(): View
    {
        return $this->form(new Machine);
    }

    public function store(MachineRequest $r): RedirectResponse
    {
        $m = DB::transaction(fn () => Machine::create($this->data($r)));
        $this->files($r, $m);

        return to_route('machines.index')->with('success', 'Machine created.');
    }

    public function show(Machine $machine): View
    {
        return view('master.machines.show', ['machine' => $machine->load(['brandMaster', 'documents'])]);
    }

    public function document(MachineDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->response(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type ?: 'application/octet-stream'],
            'inline'
        );
    }

    public function edit(Machine $machine): View
    {
        return $this->form($machine);
    }

    public function update(MachineRequest $r, Machine $machine): RedirectResponse
    {
        $machine->update($this->data($r));
        $this->files($r, $machine);

        return to_route('machines.index')->with('success', 'Machine updated.');
    }

    public function destroy(Machine $machine): RedirectResponse
    {
        foreach ($machine->documents as $d) {
            Storage::disk('public')->delete($d->file_path);
        }$machine->delete();

        return back()->with('success', 'Machine deleted.');
    }

    private function files(MachineRequest $r, Machine $m): void
    {
        foreach ($r->file('machine_photos', []) as $file) {
            $this->saveFile($m, $file, 'photo');
        }
        foreach (['warranty_card' => 'warranty', 'service_coupon' => 'service_coupon'] as $field => $type) {
            if ($r->hasFile($field)) {
                $this->saveFile($m, $r->file($field), $type);
            }
        }
    }

    private function saveFile(Machine $machine, $file, string $type): void
    {
        $machine->documents()->create(['document_type' => $type, 'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 'file_path' => $file->store('machines', 'public'), 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize()]);
    }

    private function form(Machine $machine): View
    {
        return view('master.machines.form', ['machine' => $machine, 'brands' => Brand::orderBy('brand_name')->get()]);
    }

    private function data(MachineRequest $request): array
    {
        $data = $request->safe()->except('machine_photos', 'warranty_card', 'service_coupon');
        $data['brand'] = isset($data['brand_id']) ? Brand::find($data['brand_id'])?->brand_name : null;

        return $data;
    }
}
