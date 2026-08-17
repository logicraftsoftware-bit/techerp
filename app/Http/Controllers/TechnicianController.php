<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicianRequest;
use App\Models\CommissionType;
use App\Models\Department;
use App\Models\Skill;
use App\Models\Technician;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicianController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:technicians,view')->only(['index', 'show']);
        $this->middleware('permission:technicians,create')->only(['create', 'store']);
        $this->middleware('permission:technicians,update')->only(['edit', 'update']);
        $this->middleware('permission:technicians,delete')->only(['destroy']);
    }

    public function index(Request $r): View
    {
        $records = Technician::with(['manager', 'skills'])->when($r->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('employee_code', 'like', "%$s%")->orWhere('mobile', 'like', "%$s%")))->latest()->paginate(15)->withQueryString();

        return view('master.technicians.index', compact('records'));
    }

    public function create(): View
    {
        return $this->form(new Technician);
    }

    public function store(TechnicianRequest $r): RedirectResponse
    {
        DB::transaction(function () use ($r) {
            $data = $this->data($r);
            if ($r->hasFile('profile_photo')) {
                $data['profile_photo'] = $r->file('profile_photo')->store('technicians', 'public');
            }$t = Technician::create($data);
            $t->skills()->sync($r->validated('skills', []));
            $t->commissionTypes()->sync($r->validated('commission_type_ids', []));
        });

        return to_route('technicians.index')->with('success', 'Technician created.');
    }

    public function show(Technician $technician): View
    {
        return view('master.technicians.show', ['technician' => $technician->load(['manager', 'reportingUser.roles', 'departmentMaster', 'skills'])]);
    }

    public function photo(Technician $technician): StreamedResponse
    {
        abort_unless($technician->profile_photo && Storage::disk('public')->exists($technician->profile_photo), 404);

        return Storage::disk('public')->response($technician->profile_photo, basename($technician->profile_photo), [], 'inline');
    }

    public function idCard(Technician $technician): Response
    {
        $technician->load(['departmentMaster']);
        $logo = $this->dataUri(public_path('images/fieldservice-logo.png'), 'image/png');
        $photo = null;

        if ($technician->profile_photo && Storage::disk('public')->exists($technician->profile_photo)) {
            $photo = 'data:'.(Storage::disk('public')->mimeType($technician->profile_photo) ?: 'image/jpeg').';base64,'.base64_encode(Storage::disk('public')->get($technician->profile_photo));
        }

        return Pdf::loadView('master.technicians.id-card', compact('technician', 'logo', 'photo'))
            ->setPaper([0, 0, 153.07, 243.78])
            ->download($technician->employee_code.'-id-card.pdf');
    }

    public function edit(Technician $technician): View
    {
        return $this->form($technician);
    }

    public function update(TechnicianRequest $r, Technician $technician): RedirectResponse
    {
        DB::transaction(function () use ($r, $technician) {
            $data = $this->data($r);
            if ($r->hasFile('profile_photo')) {
                $data['profile_photo'] = $r->file('profile_photo')->store('technicians', 'public');
            }$technician->update($data);
            $technician->skills()->sync($r->validated('skills', []));
            $technician->commissionTypes()->sync($r->validated('commission_type_ids', []));
        });

        return to_route('technicians.index')->with('success', 'Technician updated.');
    }

    public function destroy(Technician $technician): RedirectResponse
    {
        abort_if($technician->reports()->exists(), 422, 'Reassign reporting technicians first.');
        $technician->delete();

        return back()->with('success', 'Technician deleted.');
    }

    private function form(Technician $technician): View
    {
        return view('master.technicians.form', compact('technician') + ['managers' => Technician::whereKeyNot($technician->id)->orderBy('name')->get(), 'adminUsers' => User::with('roles')->where('is_active', true)->orderBy('name')->get(), 'departments' => Department::orderBy('department_name')->get(), 'skills' => Skill::where('is_active', true)->orderBy('name')->get(), 'commissionTypes' => CommissionType::orderBy('type_name')->get()]);
    }

    private function data(TechnicianRequest $request): array
    {
        $data = $request->safe()->except('skills', 'commission_type_ids', 'profile_photo', 'reporting_manager', 'password');
        $data['department'] = isset($data['department_id']) ? Department::find($data['department_id'])?->department_name : null;
        $data['reporting_manager_id'] = null;
        $data['reporting_user_id'] = null;

        if ($manager = $request->validated('reporting_manager')) {
            [$type, $id] = explode(':', $manager);
            $data[$type === 'technician' ? 'reporting_manager_id' : 'reporting_user_id'] = $id;
        }
        if ($request->filled('password')) {
            $data['password'] = Hash::make((string) $request->string('password'));
        }
        foreach (['monthly_salary', 'daily_salary', 'overtime_rate', 'travel_allowance', 'food_allowance', 'other_allowance', 'pf', 'esi', 'monthly_paid_leave_days'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        return $data;
    }

    private function dataUri(string $path, string $mime): string
    {
        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
