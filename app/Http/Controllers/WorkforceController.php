<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Holiday;
use App\Models\SalaryRecord;
use App\Models\Technician;
use App\Models\TechnicianLeave;
use App\Models\User;
use App\Models\WorkAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkforceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance,view')->only(['attendance']);
        $this->middleware('permission:attendance,create')->only(['saveAttendance']);
        $this->middleware('permission:leave,view')->only(['leaves']);
        $this->middleware('permission:leave,create')->only(['storeLeave']);
        $this->middleware('permission:leave,update')->only(['updateLeave', 'editLeave', 'updateLeaveDetails']);
        $this->middleware('permission:leave,delete')->only(['destroyLeave']);
        $this->middleware('permission:salary,view')->only(['salaries']);
        $this->middleware('permission:salary,create')->only(['generateSalaries']);
        $this->middleware('permission:salary,update')->only(['paySalary']);
        $this->middleware('permission:expenses,view')->only(['expenses']);
        $this->middleware('permission:expenses,create')->only(['storeExpense']);
        $this->middleware('permission:expenses,update')->only(['updateExpense']);
    }

    public function attendance(Request $request)
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));
        $records = Attendance::whereDate('attendance_date', $date)->get()
            ->keyBy(fn ($a) => $a->technician_id ? "technician:{$a->technician_id}" : "user:{$a->user_id}");

        return view('workforce.attendance', ['date' => $date, 'staff' => $this->staffList(), 'records' => $records]);
    }

    public function saveAttendance(Request $request)
    {
        $data = $request->validate(['attendance_date' => 'required|date', 'attendance' => 'required|array', 'attendance.*.status' => ['required', Rule::in(['present', 'absent', 'leave', 'half_day', 'weekly_off'])], 'attendance.*.check_in' => 'nullable|date_format:H:i', 'attendance.*.check_out' => 'nullable|date_format:H:i', 'attendance.*.overtime_hours' => 'nullable|numeric|min:0|max:24', 'attendance.*.remarks' => 'nullable|max:500']);
        foreach ($data['attendance'] as $key => $row) {
            [$type, $id] = $this->splitStaffKey($key);
            Attendance::updateOrCreate(['technician_id' => $type === 'technician' ? $id : null, 'user_id' => $type === 'user' ? $id : null, 'attendance_date' => $data['attendance_date']], ['attendance_status' => $row['status'], 'check_in' => $row['check_in'] ?? null, 'check_out' => $row['check_out'] ?? null, 'overtime_hours' => $row['overtime_hours'] ?? 0, 'remarks' => $row['remarks'] ?? null, 'marked_by' => $request->user()->id]);
        }

        return back()->with('success', 'Attendance saved.');
    }

    public function leaves()
    {
        return view('workforce.leaves', ['records' => TechnicianLeave::with(['technician', 'user'])->latest()->paginate(20), 'staff' => $this->staffList()]);
    }

    public function storeLeave(Request $request)
    {
        $data = $request->validate(['staff' => ['required', 'regex:/^(technician|user):\d+$/'], 'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])], 'from_date' => 'required|date', 'to_date' => 'required|date|after_or_equal:from_date', 'reason' => 'required|max:1000']);
        [$type, $id] = $this->splitStaffKey($data['staff']);
        unset($data['staff']);
        $data['technician_id'] = $type === 'technician' ? $id : null;
        $data['user_id'] = $type === 'user' ? $id : null;
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $data['leave_code'] = 'LV-'.now()->format('ymd').'-'.random_int(1000, 9999);
        TechnicianLeave::create($data);

        return back()->with('success', 'Leave request created.');
    }

    public function updateLeave(Request $request, TechnicianLeave $leave)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'cancelled'])], 'approval_remarks' => 'nullable|max:1000']);
        $leave->update($data + ['actioned_by' => $request->user()->id, 'actioned_at' => now()]);
        if ($data['status'] === 'approved') {
            $this->markLeaveAttendance($leave, $request->user()->id);
        }

        return back()->with('success', 'Leave status updated.');
    }

    public function editLeave(TechnicianLeave $leave)
    {
        return view('workforce.leave-edit', ['leave' => $leave, 'staff' => $this->staffList()]);
    }

    public function updateLeaveDetails(Request $request, TechnicianLeave $leave)
    {
        $data = $request->validate(['staff' => ['required', 'regex:/^(technician|user):\d+$/'], 'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])], 'from_date' => 'required|date', 'to_date' => 'required|date|after_or_equal:from_date', 'reason' => 'required|max:1000']);
        [$type, $id] = $this->splitStaffKey($data['staff']);
        unset($data['staff']);
        $data['technician_id'] = $type === 'technician' ? $id : null;
        $data['user_id'] = $type === 'user' ? $id : null;
        $data['total_days'] = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        $leave->update($data);
        if ($leave->status === 'approved') {
            $this->markLeaveAttendance($leave, $request->user()->id);
        }

        return to_route('leave.index')->with('success', 'Leave request updated.');
    }

    public function destroyLeave(Request $request, TechnicianLeave $leave)
    {
        if ($leave->status === 'approved') {
            Attendance::where('technician_id', $leave->technician_id)->where('user_id', $leave->user_id)
                ->whereBetween('attendance_date', [$leave->from_date, $leave->to_date])
                ->where('attendance_status', 'leave')->delete();
        }
        $leave->delete();

        return back()->with('success', 'Leave request deleted.');
    }

    private function markLeaveAttendance(TechnicianLeave $leave, int $markedBy): void
    {
        for ($date = $leave->from_date->copy(); $date->lte($leave->to_date); $date->addDay()) {
            Attendance::updateOrCreate(['technician_id' => $leave->technician_id, 'user_id' => $leave->user_id, 'attendance_date' => $date], ['attendance_status' => 'leave', 'marked_by' => $markedBy]);
        }
    }

    public function salaries(Request $request)
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m').'-01'))->startOfMonth();

        return view('workforce.salaries', ['month' => $month, 'records' => SalaryRecord::with(['technician', 'user'])->whereDate('salary_month', $month)->paginate(30)]);
    }

    public function generateSalaries(Request $request)
    {
        $month = Carbon::parse($request->validate(['month' => 'required|date_format:Y-m'])['month'].'-01');
        $holidayDates = Holiday::whereBetween('holiday_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->pluck('holiday_date')->map(fn ($date) => $date->format('Y-m-d'))->all();
        $workingDays = collect(range(1, $month->daysInMonth))->map(fn ($day) => $month->copy()->day($day))->filter(fn ($date) => ! $date->isSunday() && ! in_array($date->format('Y-m-d'), $holidayDates, true))->count();

        foreach (Technician::where('status', 'active')->get() as $technician) {
            $this->generateSalaryRecord('technician', $technician, $month, $workingDays);
        }
        foreach (User::where('is_active', true)->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'super-admin'))->get() as $user) {
            $this->generateSalaryRecord('user', $user, $month, $workingDays);
        }

        return back()->with('success', 'Salary register generated from attendance.');
    }

    private function generateSalaryRecord(string $type, Technician|User $staff, Carbon $month, int $workingDays): void
    {
        $column = "{$type}_id";
        $attendance = Attendance::where($column, $staff->id)->whereBetween('attendance_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
        $present = (clone $attendance)->where('attendance_status', 'present')->count() + ((clone $attendance)->where('attendance_status', 'half_day')->count() * .5);
        $leave = (clone $attendance)->where('attendance_status', 'leave')->count();
        $overtime = (float) (clone $attendance)->sum('overtime_hours');
        $base = $staff->salary_type === 'daily' ? (float) $staff->daily_salary * $present : ((float) $staff->monthly_salary / max($workingDays, 1)) * min($present + $leave, $workingDays);
        $allowances = (float) $staff->travel_allowance + (float) $staff->food_allowance + (float) $staff->other_allowance;
        $overtimeAmount = $overtime * (float) $staff->overtime_rate;
        $deductions = (float) $staff->pf + (float) $staff->esi;
        SalaryRecord::updateOrCreate(
            ['technician_id' => $type === 'technician' ? $staff->id : null, 'user_id' => $type === 'user' ? $staff->id : null, 'salary_month' => $month],
            ['salary_code' => 'SAL-'.$month->format('Ym').'-'.($type === 'technician' ? 'T' : 'U').$staff->id, 'working_days' => $workingDays, 'present_days' => $present, 'paid_leave_days' => $leave, 'overtime_hours' => $overtime, 'basic_salary' => round($base, 2), 'allowances' => $allowances, 'overtime_amount' => $overtimeAmount, 'deductions' => $deductions, 'net_salary' => round($base + $allowances + $overtimeAmount - $deductions, 2)]
        );
    }

    public function paySalary(Request $request, SalaryRecord $salary)
    {
        $data = $request->validate(['payment_reference' => 'required|max:100']);
        $salary->update($data + ['status' => 'paid', 'paid_at' => today()]);

        return back()->with('success', 'Salary marked paid.');
    }

    public function expenses()
    {
        return view('workforce.expenses', ['records' => Expense::with(['technician', 'user', 'assignment'])->latest()->paginate(20), 'staff' => $this->staffList(), 'assignments' => WorkAssignment::latest()->get()]);
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate(['staff' => ['required', 'regex:/^(technician|user):\d+$/'], 'work_assignment_id' => 'nullable|exists:work_assignments,id', 'expense_date' => 'required|date', 'expense_type' => ['required', Rule::in(['travel', 'food', 'lodging', 'parts', 'other'])], 'amount' => 'required|numeric|min:0.01', 'description' => 'required|max:1000', 'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120']);
        [$type, $id] = $this->splitStaffKey($data['staff']);
        unset($data['staff']);
        $data['technician_id'] = $type === 'technician' ? $id : null;
        $data['user_id'] = $type === 'user' ? $id : null;
        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('expense-receipts', 'local');
        } unset($data['receipt']);
        $data['expense_code'] = 'EXP-'.now()->format('ymd').'-'.random_int(1000, 9999);
        Expense::create($data);

        return back()->with('success', 'Expense submitted.');
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'paid'])], 'approval_remarks' => 'nullable|max:1000']);
        $expense->update($data + ['actioned_by' => $request->user()->id]);

        return back()->with('success', 'Expense status updated.');
    }

    private function staffList()
    {
        $technicians = Technician::where('status', 'active')->orderBy('name')->get()
            ->map(fn ($t) => (object) ['key' => "technician:{$t->id}", 'code' => $t->employee_code, 'name' => $t->name]);
        $users = User::where('is_active', true)->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'super-admin'))->orderBy('name')->get()
            ->map(fn ($u) => (object) ['key' => "user:{$u->id}", 'code' => $u->employee_code, 'name' => $u->name]);

        return $technicians->concat($users)->sortBy('name')->values();
    }

    private function splitStaffKey(string $key): array
    {
        if (! preg_match('/^(technician|user):(\d+)$/', $key, $matches)) {
            throw ValidationException::withMessages(['staff' => 'Invalid staff selection.']);
        }

        return [$matches[1], (int) $matches[2]];
    }
}
