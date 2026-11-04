<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeGoalController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EvaluationPeriodController;
use App\Http\Controllers\ImprovementPlanController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrganizationChartController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PasswordResetRequestController;
use App\Http\Controllers\PeerFeedbackController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Support\PageAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    return $user
        ? redirect()->route(PageAccess::homeRoute($user))
        : view('landing');
})->name('landing');

Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/captcha/math', [CaptchaController::class, 'math'])->name('captcha.math');
Route::get('/captcha/physics', [CaptchaController::class, 'physics'])->name('captcha.physics');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.request.store');
});

Route::middleware(['auth', 'page.access'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/kpi', [EmployeeGoalController::class, 'overview'])->name('kpi.index');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::get('/employees/import', [EmployeeImportController::class, 'index'])->name('employees.import');
        Route::post('/employees/import', [EmployeeImportController::class, 'import'])->name('employees.import.store');
        Route::get('/employees/import/template', [EmployeeImportController::class, 'template'])->name('employees.import.template');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::patch('/employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    });
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/goals', [EmployeeGoalController::class, 'index'])->name('employees.goals.index');
    Route::post('/employees/{employee}/goals', [EmployeeGoalController::class, 'store'])->name('employees.goals.store');
    Route::put('/employees/{employee}/goals/{goal}', [EmployeeGoalController::class, 'update'])->name('employees.goals.update');
    Route::delete('/employees/{employee}/goals/{goal}', [EmployeeGoalController::class, 'destroy'])->name('employees.goals.destroy');

    Route::middleware('role:admin')->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('positions', PositionController::class)->except('show');
        Route::resource('periods', EvaluationPeriodController::class)->except('show');
        Route::resource('criteria', CriterionController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');

        Route::patch('/periods/{period}/activate', [EvaluationPeriodController::class, 'activate'])->name('periods.activate');
        Route::patch('/periods/{period}/close', [EvaluationPeriodController::class, 'close'])->name('periods.close');
        Route::post('/periods/{period}/remind', [EvaluationPeriodController::class, 'remind'])->name('periods.remind');
        Route::patch('/periods/{id}/restore', [EvaluationPeriodController::class, 'restore'])->name('periods.restore');

        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.index');
        Route::patch('/activity-log/{log}/revert', [ActivityLogController::class, 'revert'])->name('activity.revert');
        Route::delete('/activity-log/{log}', [ActivityLogController::class, 'destroy'])->name('activity.destroy');

        Route::get('/password-requests', [PasswordResetRequestController::class, 'index'])->name('password-requests.index');
        Route::patch('/password-requests/{passwordRequest}/reset', [PasswordResetRequestController::class, 'reset'])->name('password-requests.reset');
        Route::patch('/password-requests/{passwordRequest}/dismiss', [PasswordResetRequestController::class, 'dismiss'])->name('password-requests.dismiss');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/settings/access', [AccessController::class, 'index'])->name('access.index');
        Route::put('/settings/access', [AccessController::class, 'update'])->name('access.update');
        Route::put('/settings/access/defaults', [AccessController::class, 'updateDefaults'])->name('access.defaults');
        Route::post('/settings/access/reset', [AccessController::class, 'reset'])->name('access.reset');

        Route::get('/settings/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/settings/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/settings/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/settings/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('/settings/branding', [BrandingController::class, 'edit'])->name('branding.edit');
        Route::post('/settings/branding', [BrandingController::class, 'update'])->name('branding.update');

        Route::get('/settings/database', [DatabaseController::class, 'index'])->name('database.index');
        Route::get('/settings/database/export', [DatabaseController::class, 'export'])->name('database.export');
        Route::post('/settings/database/import', [DatabaseController::class, 'import'])->name('database.import');
        Route::get('/settings/database/backups/{backup}/download', [DatabaseController::class, 'download'])->name('database.backup.download');
        Route::delete('/settings/database/backups/{backup}', [DatabaseController::class, 'destroy'])->name('database.backup.destroy');
    });

    Route::get('/org-chart', [OrganizationChartController::class, 'index'])
        ->middleware('role:admin,manager')
        ->name('organization.chart');

    Route::middleware('role:admin')->group(function () {
        Route::patch('/org-chart/departments/{department}/head', [OrganizationChartController::class, 'updateHead'])
            ->name('organization.departments.head');
        Route::patch('/org-chart/employees/{employee}/manager', [OrganizationChartController::class, 'updateManager'])
            ->name('organization.employees.manager');
    });

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/{project}/members', [ProjectController::class, 'addMember'])->name('projects.members.store');
        Route::delete('/projects/{project}/members/{employee}', [ProjectController::class, 'removeMember'])->name('projects.members.destroy');
    });

    Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluations/team', [EvaluationController::class, 'team'])->middleware('role:admin,manager')->name('evaluations.team');
    Route::get('/evaluations/create', [EvaluationController::class, 'create'])->middleware('role:admin,manager')->name('evaluations.create');
    Route::post('/evaluations', [EvaluationController::class, 'store'])->middleware('role:admin,manager')->name('evaluations.store');
    Route::get('/evaluations/{evaluation}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::get('/evaluations/{evaluation}/pdf', [EvaluationController::class, 'pdf'])->name('evaluations.pdf');
    Route::get('/evaluations/{evaluation}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::put('/evaluations/{evaluation}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::patch('/evaluations/{evaluation}/reopen', [EvaluationController::class, 'reopen'])->middleware('role:admin')->name('evaluations.reopen');
    Route::delete('/evaluations/{evaluation}', [EvaluationController::class, 'destroy'])->middleware('role:admin')->name('evaluations.destroy');

    Route::post('/evaluations/{evaluation}/reviewers', [PeerFeedbackController::class, 'store'])->name('evaluations.reviewers.store');
    Route::delete('/evaluations/{evaluation}/reviewers/{feedback}', [PeerFeedbackController::class, 'destroy'])->name('evaluations.reviewers.destroy');

    Route::post('/evaluations/{evaluation}/plans', [ImprovementPlanController::class, 'store'])->name('evaluations.plans.store');
    Route::patch('/evaluations/{evaluation}/plans/{plan}', [ImprovementPlanController::class, 'update'])->name('evaluations.plans.update');
    Route::delete('/evaluations/{evaluation}/plans/{plan}', [ImprovementPlanController::class, 'destroy'])->name('evaluations.plans.destroy');

    Route::get('/feedback', [PeerFeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback/requests', [PeerFeedbackController::class, 'sendRequest'])->name('feedback.requests.store');
    Route::post('/feedback/{feedback}', [PeerFeedbackController::class, 'submit'])->name('feedback.submit');

    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/progress', [ProgressController::class, 'index'])->name('reports.progress');
        Route::get('/reports/calibration', [ReportController::class, 'calibration'])->name('reports.calibration');
        Route::get('/reports/export/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reports/export/excel', [ReportController::class, 'excel'])->name('reports.excel');
    });
});
