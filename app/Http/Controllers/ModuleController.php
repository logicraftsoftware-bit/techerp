<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __invoke(Request $request, string $module): View
    {
        $item = collect(config('crm.navigation'))->flatten(1)->first(fn (array $item) => $item[0] === $module);
        abort_unless($item, 404);

        return view('modules.show', [
            'module' => $module,
            'title' => $item[1],
            'description' => $item[2],
            'features' => $this->features($module),
        ]);
    }

    private function features(string $module): array
    {
        return match ($module) {
            'customers' => ['Customer master', 'Multiple contacts', 'Machine mapping', 'Service history', 'AMC summary', 'Cost overview'],
            'machines' => ['Machine master', 'Documents & photos', 'Warranty and AMC', 'Complaints', 'Parts replaced', 'Downtime tracking'],
            'technicians' => ['Personal details', 'Employment', 'Reporting manager', 'Salary structure', 'Allowances', 'Deductions'],
            'skills' => ['Skill master', 'Technician mapping', 'Skill levels', 'Experience', 'Certifications', 'Expiry tracking'],
            'attendance' => ['Daily attendance', 'Monthly register', 'Bulk attendance', 'Corrections', 'Approval', 'Technician report'],
            'leave' => ['Leave types', 'Applications', 'Balances', 'Approval workflow', 'Attachments', 'Leave calendar'],
            'service-requests' => ['Complaint intake', 'Customer & machine', 'Priority', 'Attachments', 'Technician assignment', 'Status workflow'],
            'job-cards' => ['Create from request', 'Scheduling', 'Required skill', 'Team assignment', 'Location', 'Job instructions'],
            'assignments' => ['Technician availability', 'Leave check', 'Existing jobs', 'Workload', 'Skill matching', 'Conflict warning'],
            'work-status' => ['Status updates', 'Complete timeline', 'Changed by', 'Remarks', 'Date & time', 'Status reports'],
            'service-reports' => ['Problem found', 'Diagnosis', 'Work performed', 'Before/during/after photos', 'Documents', 'Completion notes'],
            'service-history' => ['Machine timeline', 'Customer timeline', 'Technicians', 'Parts used', 'Service costs', 'Documents'],
            'ratings' => ['Customer signature', 'Approval', 'Remarks', '1–5 star rating', 'Feedback', 'Completion record'],
            'parts' => ['Part catalog', 'Categories', 'Brands & models', 'Prices', 'Stock levels', 'Warranty'],
            'suppliers' => ['Supplier master', 'Contacts', 'GST details', 'Address', 'Purchase history', 'Status'],
            'inventory' => ['Stock in', 'Stock out', 'Transactions', 'Warehouses', 'Damaged parts', 'Low-stock alerts'],
            'parts-issues' => ['Technician issue', 'Job allocation', 'Used quantity', 'Returns', 'Damage tracking', 'Stock update'],
            'job-parts' => ['Job consumption', 'Quantity & rate', 'Serial number', 'Warranty', 'Totals', 'Automatic stock'],
            'parts-requests' => ['Technician request', 'Urgency', 'Approval', 'Rejection', 'Issue workflow', 'Remarks'],
            'salary' => ['Monthly generation', 'Attendance calculation', 'Overtime', 'Allowances', 'Deductions', 'Printable slip'],
            'expenses' => ['Expense claims', 'Job mapping', 'Receipts', 'Approval', 'Payment', 'Expense reports'],
            'amc' => ['Contract master', 'Service frequency', 'Completed services', 'Remaining services', 'Next service', 'Expiry alerts'],
            'preventive-maintenance' => ['Maintenance schedule', 'Frequency', 'Last service', 'Next service', 'Assignment', 'Overdue view'],
            'notifications' => ['Service alerts', 'Job alerts', 'Leave alerts', 'Parts alerts', 'AMC expiry', 'Salary alerts'],
            'reports' => ['Technician reports', 'Customer reports', 'Machine reports', 'Inventory reports', 'Work reports', 'Excel & PDF export'],
            'roles' => ['Roles', 'Permissions', 'Module access', 'User mapping', 'Policies', 'Active status'],
            'settings' => ['Company information', 'Branding', 'Salary settings', 'Attendance settings', 'Job workflow', 'Notifications'],
            default => [],
        };
    }
}
