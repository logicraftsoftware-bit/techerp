<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $records = Department::query()
            ->when($request->search, fn ($query, $search) => $query->where('department_name', 'like', "%{$search}%"))
            ->orderBy('department_name')
            ->paginate(15)
            ->withQueryString();

        return view('master.departments.index', compact('records'));
    }

    public function create(): View
    {
        return view('master.departments.form', ['department' => new Department]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return to_route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department): View
    {
        return view('master.departments.form', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return to_route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }
}
