<?php

use App\Http\Controllers\AmcPlanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JobCardController;
use App\Http\Controllers\MachineCategoryController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceReportController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkAssignmentController;
use App\Http\Controllers\WorkStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/modules/{module}', ModuleController::class)->name('modules.show');
    Route::get('/machine-documents/{document}', [MachineController::class, 'document'])->name('machine-documents.show');
    Route::delete('/machine-documents/{document}', [MachineController::class, 'destroyDocument'])->name('machine-documents.destroy');
    Route::get('/technicians/{technician}/photo', [TechnicianController::class, 'photo'])->name('technicians.photo');
    Route::get('/technicians/{technician}/id-card', [TechnicianController::class, 'idCard'])->name('technicians.id-card');
    Route::resources(['customers' => CustomerController::class, 'machines' => MachineController::class, 'technicians' => TechnicianController::class]);
    Route::resource('skills', SkillController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('departments', DepartmentController::class)->except('show');
    Route::resource('machine-categories', MachineCategoryController::class)->except('show');
    Route::resource('amc-plans', AmcPlanController::class)->except('show');
    Route::resource('service-requests', ServiceRequestController::class);
    Route::resource('assignments', WorkAssignmentController::class);
    Route::get('/job-cards', [JobCardController::class, 'index'])->name('job-cards.index');
    Route::get('/work-status', [WorkStatusController::class, 'index'])->name('work-status.index');
    Route::get('/work-status/{assignment}', [WorkStatusController::class, 'show'])->name('work-status.show');
    Route::patch('/work-status/{assignment}', [WorkStatusController::class, 'update'])->name('work-status.update');
    Route::get('/service-reports', [ServiceReportController::class, 'index'])->name('service-reports.index');
    Route::get('/service-reports/{serviceRequest}', [ServiceReportController::class, 'show'])->name('service-reports.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::resource('users', UserController::class);
});
