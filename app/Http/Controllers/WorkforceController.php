<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\SalaryRecord;
use App\Models\Technician;
use App\Models\TechnicianLeave;
use App\Models\WorkAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkforceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance,view')->only(['attendance']);
        $this->middleware('permission:attendance,create')->only(['saveAttendance']);
        $this->middleware('permission:leave,view')->only(['leaves']);
        $this->middleware('permission:leave,create')->only(['storeLeave']);
        $this->middleware('permission:leave,update')->only(['updateLeave']);
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

        return view('workforce.attendance', ['date' => $date, 'technicians' => Technician::where('status', 'active')->orderBy('name')->get(), 'records' => Attendance::with('technician')->whereDate('attendance_date', $date)->get()->keyBy('technician_id')]);
    }

    public function saveAttendance(Request $request)
    {
        $data = $request->validate(['attendance_date' => 'required|date', 'attendance' => 'required|array', 'attendance.*.status' => ['required', Rule::in(['present', 'absent', 'leave', 'half_day', 'weekly_off'])], 'attendance.*.check_in' => 'nullable|date_format:H:i', 'attendance.*.check_out' => 'nullable|date_format:H:i', 'attendance.*.overtime_hours' => 'nullable|numeric|min:0|max:24', 'attendance.*.remarks' => 'nullable|max:500']);
        foreach ($data['attendance'] as $technicianId => $row) {
            Attendance::updateOrCreate(['technician_id' => $technicianId, 'attendance_date' => $data['attendance_date']], ['attendance_status' => $row['status'], 'check_in' => $row['check_in'] ?? null, 'check_out' => $row['check_out'] ?? null, 'overtime_hours' => $row['overtime_hours'] ?? 0, 'remarks' => $row['remarks'] ?? null, 'marked_by' => $request->user()->id]);
        }

        return back()->with('success', 'Attendance saved.');
    }

    public function leaves()
    {
        return view('workforce.leaves', ['records' => TechnicianLeave::with('technician')->latest()->paginate(20), 'technicians' => Technician::where('status', 'active')->orderBy('name')->get()]);
    }

    public function storeLeave(Request $request)
    {
        $data = $request->validate(['technician_id' => 'required|exists:technicians,id', 'leave_type' => ['required', Rule::in(['casual', 'sick', 'earned', 'unpaid'])], 'from_date' => 'required|date', 'to_date' => 'required|date|after_or_equal:from_date', 'reason' => 'required|max:1000']);
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
            for ($date = $leave->from_date->copy(); $date->lte($leave->to_date); $date->addDay()) {
                Attendance::updateOrCreate(['technician_id' => $leave->technician_id, 'attendance_date' => $date], ['attendance_status' => 'leave', 'marked_by' => $request->user()->id]);
            }
        }

        return back()->with('success', 'Leave status updated.');
    }

    public function salaries(Request $request)
    {
        $month = Carbon::parse($request->input('month', now()->format('Y-m').'-01'))->startOfMonth();

        return view('workforce.salaries', ['month' => $month, 'records' => SalaryRecord::with('technician')->whereDate('salary_month', $month)->paginate(30)]);
    }

    public function generateSalaries(Request $request)
    {
        $month = Carbon::parse($request->validate(['month' => 'required|date_format:Y-m'])['month'].'-01');
        $workingDays = collect(range(1, $month->daysInMonth))->map(fn ($day) => $month->copy()->day($day))->filter(fn ($date) => ! $date->isSunday())->count();
        foreach (Technician::where('status', 'active')->get() as $technician) {
            $attendance = Attendance::where('technician_id', $technician->id)->whereBetween('attendance_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
            $present = (clone $attendance)->where('attendance_status', 'present')->count() + ((clone $attendance)->where('attendance_status', 'half_day')->count() * .5);
            $leave = (clone $attendance)->where('attendance_status', 'leave')->count();
            $overtime = (float) (clone $attendance)->sum('overtime_hours');
            $base = $technician->salary_type === 'daily' ? (float) $technician->daily_salary * $present : ((float) $technician->monthly_salary / max($workingDays, 1)) * min($present + $leave, $workingDays);
            $allowances = (float) $technician->travel_allowance + (float) $technician->food_allowance + (float) $technician->other_allowance;
            $overtimeAmount = $overtime * (float) $technician->overtime_rate;
            $deductions = (float) $technician->pf + (float) $technician->esi;
            SalaryRecord::updateOrCreate(['technician_id' => $technician->id, 'salary_month' => $month], ['salary_code' => 'SAL-'.$month->format('Ym').'-'.$technician->id, 'working_days' => $workingDays, 'present_days' => $present, 'paid_leave_days' => $leave, 'overtime_hours' => $overtime, 'basic_salary' => round($base, 2), 'allowances' => $allowances, 'overtime_amount' => $overtimeAmount, 'deductions' => $deductions, 'net_salary' => round($base + $allowances + $overtimeAmount - $deductions, 2)]);
        }

        return back()->with('success', 'Salary register generated from attendance.');
    }

    public function paySalary(Request $request, SalaryRecord $salary)
    {
        $data = $request->validate(['payment_reference' => 'required|max:100']);
        $salary->update($data + ['status' => 'paid', 'paid_at' => today()]);

        return back()->with('success', 'Salary marked paid.');
    }

    public function expenses()
    {
        return view('workforce.expenses', ['records' => Expense::with(['technician', 'assignment'])->latest()->paginate(20), 'technicians' => Technician::where('status', 'active')->orderBy('name')->get(), 'assignments' => WorkAssignment::latest()->get()]);
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate(['technician_id' => 'required|exists:technicians,id', 'work_assignment_id' => 'nullable|exists:work_assignments,id', 'expense_date' => 'required|date', 'expense_type' => ['required', Rule::in(['travel', 'food', 'lodging', 'parts', 'other'])], 'amount' => 'required|numeric|min:0.01', 'description' => 'required|max:1000', 'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120']);
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
}
