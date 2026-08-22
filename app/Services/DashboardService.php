<?php

namespace App\Services;

use App\Models\CustomerAmcTagging;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    public function data(): array
    {
        $month = now()->startOfMonth();
        $metrics = [
            ['label' => 'Total Customers', 'value' => $this->count('customers'), 'tone' => 'blue'],
            ['label' => 'Total Machines', 'value' => $this->count('machines'), 'tone' => 'violet'],
            ['label' => 'Total Technicians', 'value' => $this->count('technicians'), 'tone' => 'cyan'],
            ['label' => "Today's Requests", 'value' => $this->countDate('service_requests', 'request_date'), 'tone' => 'amber'],
            ['label' => "Today's Jobs", 'value' => $this->countDate('job_cards', 'scheduled_date'), 'tone' => 'indigo'],
            ['label' => 'Pending Jobs', 'value' => $this->countStatus('job_cards', ['new', 'assigned', 'accepted']), 'tone' => 'orange'],
            ['label' => 'In Progress', 'value' => $this->countStatus('job_cards', ['on_way', 'checked_in', 'in_progress']), 'tone' => 'blue'],
            ['label' => 'Completed Jobs', 'value' => $this->countStatus('job_cards', ['completed', 'closed']), 'tone' => 'emerald'],
            ['label' => 'Overdue Jobs', 'value' => $this->overdue(), 'tone' => 'red'],
            ['label' => 'Technicians Present', 'value' => $this->attendance('present'), 'tone' => 'emerald'],
            ['label' => 'On Leave', 'value' => $this->attendance('leave'), 'tone' => 'pink'],
            ['label' => 'Low Stock Parts', 'value' => $this->lowStock(), 'tone' => 'red'],
            ['label' => 'Service Revenue', 'value' => $this->money('machine_service_histories', 'service_cost', $month), 'tone' => 'emerald', 'money' => true],
            ['label' => 'Staff Salary', 'value' => $this->money('salary_records', 'net_salary', $month), 'tone' => 'slate', 'money' => true],
            ['label' => 'Monthly Expenses', 'value' => $this->money('expenses', 'amount', $month), 'tone' => 'rose', 'money' => true],
        ];

        return [
            'metrics' => $metrics,
            'charts' => $this->charts(),
            'recentUsers' => User::with('roles')->latest()->limit(5)->get(),
            'expiringAmcs' => $this->expiringAmcs(),
        ];
    }

    private function count(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function countDate(string $table, string $column): int
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column) ? DB::table($table)->whereDate($column, today())->count() : 0;
    }

    private function countStatus(string $table, array $statuses): int
    {
        return Schema::hasTable($table) ? DB::table($table)->whereIn('status', $statuses)->count() : 0;
    }

    private function attendance(string $status): int
    {
        return Schema::hasTable('attendances') ? DB::table('attendances')->whereDate('attendance_date', today())->where('attendance_status', $status)->count() : 0;
    }

    private function lowStock(): int
    {
        return Schema::hasTable('parts') ? DB::table('parts')->whereColumn('current_stock', '<=', 'minimum_stock')->count() : 0;
    }

    private function overdue(): int
    {
        return Schema::hasTable('job_cards') ? DB::table('job_cards')->whereDate('expected_completion', '<', today())->whereNotIn('status', ['completed', 'cancelled', 'closed'])->count() : 0;
    }

    private function money(string $table, string $column, Carbon $from): float
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column) ? (float) DB::table($table)->where('created_at', '>=', $from)->sum($column) : 0;
    }

    private function charts(): array
    {
        return [
            'work' => ['labels' => collect(range(6, 0))->map(fn ($d) => now()->subDays($d)->format('D'))->values(), 'completed' => array_fill(0, 7, 0), 'pending' => array_fill(0, 7, 0)],
            'status' => ['labels' => ['Completed', 'Pending', 'In Progress', 'Overdue'], 'values' => [0, 0, 0, 0]],
            'attendance' => ['labels' => ['Present', 'Leave', 'Absent'], 'values' => [$this->attendance('present'), $this->attendance('leave'), $this->attendance('absent')]],
        ];
    }

    private function expiringAmcs()
    {
        if (! auth()->user()?->hasRole('super-admin', 'admin') || ! Schema::hasTable('customer_amc_taggings')) {
            return collect();
        }

        return CustomerAmcTagging::with(['customer', 'machine', 'amcPlan'])
            ->whereBetween('end_date', [today(), today()->addMonth()])
            ->orderBy('end_date')
            ->get();
    }
}
