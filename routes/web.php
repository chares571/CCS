<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AccountSecuritySetupController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\EndUser\ApplicationController;
use App\Http\Controllers\EndUser\HomepageController;
use App\Http\Controllers\EndUser\ProfileController;
use App\Http\Controllers\SuperAdmin\BackupController;
use App\Http\Controllers\SuperAdmin\DecisionController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\SettingsController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        if (in_array((string) auth()->user()->role, ['super_admin', 'admin'], true)) {
            $email = mb_strtolower((string) auth()->user()->email);
            $domains = (array) config('ccs.admin_account_security.local_email_domains', ['ccs.local']);
            $domains = array_values(array_filter(array_map('trim', $domains), fn ($value) => $value !== ''));

            foreach ($domains as $domain) {
                $needle = '@'.mb_strtolower($domain);
                if ($needle !== '@' && \Illuminate\Support\Str::endsWith($email, $needle)) {
                    return redirect()->route('account.security.setup.form');
                }
            }
        }

        if (auth()->user()->force_password_change && in_array((string) auth()->user()->role, ['super_admin', 'admin'], true)) {
            return redirect()->route('password.change.form');
        }

        return match (auth()->user()->role) {
            'super_admin' => redirect()->route('super-admin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'parent', 'student' => redirect()->route('homepage.feed'),
            default => redirect()->route('homepage.feed'),
        };
    })->name('dashboard');

    Route::get('/home', fn () => redirect()->route('dashboard'));

    Route::get('/super-admin-dashboard/{any?}', function (?string $any = null) {
        $target = '/superadmin-dashboard';

        if ($any) {
            $target .= '/'.$any;
        }

        return redirect($target);
    })->where('any', '.*');

    Route::get('/super-dashboard/{any?}', function (?string $any = null) {
        $target = '/superadmin-dashboard';

        if ($any) {
            $target .= '/'.$any;
        }

        return redirect($target);
    })->where('any', '.*');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showRequest'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'requestCode'])->name('password.request-code');
    Route::get('/forgot-password/parent-student', [ForgotPasswordController::class, 'showParentStudentRequest'])->name('password.request.parent-student');
    Route::post('/forgot-password/parent-student', [ForgotPasswordController::class, 'requestParentStudentCode'])->name('password.request-code.parent-student');
    Route::get('/forgot-password/parent-student/verify/{id}', [ForgotPasswordController::class, 'showParentStudentVerify'])->name('password.verify.parent-student');
    Route::post('/forgot-password/parent-student/verify/{id}', [ForgotPasswordController::class, 'verifyParentStudentCode'])->name('password.verify.submit.parent-student');
    Route::post('/forgot-password/parent-student/resend/{id}', [ForgotPasswordController::class, 'resendParentStudentCode'])->name('password.resend.parent-student');
    Route::get('/forgot-password/parent-student/reset/{id}', [ForgotPasswordController::class, 'showParentStudentReset'])->name('password.reset.parent-student');
    Route::post('/forgot-password/parent-student/reset/{id}', [ForgotPasswordController::class, 'resetParentStudentPassword'])->name('password.reset.submit.parent-student');

    Route::get('/forgot-password/admin', [ForgotPasswordController::class, 'showAdminRequest'])->name('password.request.admin');
    Route::post('/forgot-password/admin', [ForgotPasswordController::class, 'requestAdminCode'])->name('password.request-code.admin');
    Route::get('/forgot-password/admin/verify/{id}', [ForgotPasswordController::class, 'showAdminVerify'])->name('password.verify.admin');
    Route::post('/forgot-password/admin/verify/{id}', [ForgotPasswordController::class, 'verifyAdminCode'])->name('password.verify.submit.admin');
    Route::post('/forgot-password/admin/resend/{id}', [ForgotPasswordController::class, 'resendAdminCode'])->name('password.resend.admin');
    Route::get('/forgot-password/admin/reset/{id}', [ForgotPasswordController::class, 'showAdminReset'])->name('password.reset.admin');
    Route::post('/forgot-password/admin/reset/{id}', [ForgotPasswordController::class, 'resetAdminPassword'])->name('password.reset.submit.admin');

    Route::get('/forgot-password/super-admin', [ForgotPasswordController::class, 'showSuperAdminRequest'])->name('password.request.super-admin');
    Route::post('/forgot-password/super-admin', [ForgotPasswordController::class, 'requestSuperAdminCode'])->name('password.request-code.super-admin');
    Route::get('/forgot-password/super-admin/verify/{id}', [ForgotPasswordController::class, 'showSuperAdminVerify'])->name('password.verify.super-admin');
    Route::post('/forgot-password/super-admin/verify/{id}', [ForgotPasswordController::class, 'verifySuperAdminCode'])->name('password.verify.submit.super-admin');
    Route::post('/forgot-password/super-admin/resend/{id}', [ForgotPasswordController::class, 'resendSuperAdminCode'])->name('password.resend.super-admin');
    Route::get('/forgot-password/super-admin/reset/{id}', [ForgotPasswordController::class, 'showSuperAdminReset'])->name('password.reset.super-admin');
    Route::post('/forgot-password/super-admin/reset/{id}', [ForgotPasswordController::class, 'resetSuperAdminPassword'])->name('password.reset.submit.super-admin');
});

// Logout must always resolve to welcome page even if session is already expired.
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'form'])->name('password.change.form');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::get('/account-security-setup', [AccountSecuritySetupController::class, 'form'])->name('account.security.setup.form');
    Route::post('/account-security-setup', [AccountSecuritySetupController::class, 'update'])->name('account.security.setup.update');
});

Route::middleware(['auth', 'active', 'force.password.change', 'role:parent,student'])
    ->prefix('homepage')
    ->group(function () {
        Route::get('/', [HomepageController::class, 'index'])->name('homepage');
        Route::get('/feed', [HomepageController::class, 'feed'])->name('homepage.feed');
        Route::get('/profile', [ProfileController::class, 'index'])->name('homepage.profile');
        Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('homepage.profile.update');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('homepage.profile.password.update');
        Route::post('/profile/photo', [ProfileController::class, 'updateProfilePhoto'])->name('homepage.profile.photo.update');
        Route::post('/profile/photo/remove', [ProfileController::class, 'removeProfilePhoto'])->name('homepage.profile.photo.remove');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('homepage.profile.destroy');
        Route::get('/announcements/{announcement}', [HomepageController::class, 'showAnnouncement'])->name('homepage.announcements.show');
        Route::get('/enrollment', [HomepageController::class, 'enrollment'])->name('homepage.enrollment');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::put('/applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    });

Route::middleware(['auth', 'active', 'force.password.change', 'role:admin'])
    ->prefix('admin-dashboard')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/profile', [AdminSettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/profile-photo', [AdminSettingsController::class, 'updateProfilePhoto'])->name('settings.profile-photo.update');
        Route::post('/settings/profile-photo/remove', [AdminSettingsController::class, 'removeProfilePhoto'])->name('settings.profile-photo.remove');
        Route::get('/applications', [AdminDashboardController::class, 'applications'])->name('applications.index');
        Route::get('/monitoring', [AdminDashboardController::class, 'monitoring'])->name('monitoring');
        Route::get('/monitoring/hardcopy/create', [AdminDashboardController::class, 'createOfflineEnrollment'])->name('monitoring.hardcopy.create');
        Route::post('/monitoring/hardcopy', [AdminDashboardController::class, 'storeOfflineEnrollment'])->name('monitoring.hardcopy.store');
        Route::get('/enrolled-students', [AdminDashboardController::class, 'enrolledStudents'])->name('enrolled-students');
        Route::get('/monitoring/{application}', [AdminDashboardController::class, 'showMonitoringApplication'])->name('monitoring.show');
        Route::post('/applications/{application}/review', [ReviewController::class, 'review'])->name('applications.review');
        Route::resource('announcements', AnnouncementController::class)->except(['show']);
        Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
        Route::post('announcements/{announcement}/restore', [AnnouncementController::class, 'restore'])->name('announcements.restore');
        Route::delete('announcements/{announcement}/force', [AnnouncementController::class, 'forceDestroy'])->name('announcements.force-destroy');
        Route::post('announcements/{announcement}/pin', [AnnouncementController::class, 'togglePin'])->name('announcements.pin');
        Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::post('announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('announcements.unpublish');
        Route::post('announcements/{announcement}/duplicate', [AnnouncementController::class, 'duplicate'])->name('announcements.duplicate');
        Route::post('announcements/preview', [AnnouncementController::class, 'preview'])->name('announcements.preview');
        Route::post('announcements/bulk', [AnnouncementController::class, 'bulk'])->name('announcements.bulk');
    });

Route::middleware(['auth', 'active', 'force.password.change', 'role:super_admin'])
    ->prefix('superadmin-dashboard')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/monitoring', [SuperAdminDashboardController::class, 'monitoring'])->name('monitoring');
        Route::get('/enrollment-history', [SuperAdminDashboardController::class, 'enrollmentHistory'])->name('enrollment-history');
        Route::get('/enrolled-students', [SuperAdminDashboardController::class, 'enrolledStudents'])->name('enrolled-students');
        Route::get('/monitoring/{application}', [SuperAdminDashboardController::class, 'showMonitoringApplication'])->name('monitoring.show');
        Route::post('/monitoring/{application}/unlock-edit', [SuperAdminDashboardController::class, 'unlockMonitoringEdit'])->name('monitoring.unlock-edit');
        Route::put('/monitoring/{application}', [SuperAdminDashboardController::class, 'updateMonitoringApplication'])->name('monitoring.update');
        Route::get('/enrollment', [SuperAdminDashboardController::class, 'enrollment'])->name('enrollment');
        Route::get('/school-years', [SuperAdminDashboardController::class, 'schoolYears'])->name('school-years.index');
        Route::get('/backup', [SuperAdminDashboardController::class, 'backup'])->name('backup.index');
        Route::post('/applications/{application}/decision', [DecisionController::class, 'decide'])->name('applications.decide');
        Route::delete('/enrollees/{application}/duplicate', [SuperAdminDashboardController::class, 'destroyDuplicateEnrollee'])->name('enrollees.destroy-duplicate');
        Route::post('/school-years/{schoolYear}/toggle-enrollment', [SuperAdminDashboardController::class, 'toggleEnrollment'])->name('school-years.toggle');
        Route::post('/school-years/{schoolYear}/set-active', [SuperAdminDashboardController::class, 'setActive'])->name('school-years.set-active');
        Route::post('/school-years', [SuperAdminDashboardController::class, 'storeSchoolYear'])->name('school-years.store');
        Route::post('/school-years/{schoolYear}/lock', [SuperAdminDashboardController::class, 'lockSchoolYear'])->name('school-years.lock');
        Route::put('/school-years/{schoolYear}/enrollment-window', [SuperAdminDashboardController::class, 'updateEnrollmentWindow'])->name('school-years.enrollment-window');
        Route::resource('announcements', AnnouncementController::class)->except(['show']);
        Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
        Route::post('announcements/{announcement}/restore', [AnnouncementController::class, 'restore'])->name('announcements.restore');
        Route::delete('announcements/{announcement}/force', [AnnouncementController::class, 'forceDestroy'])->name('announcements.force-destroy');
        Route::post('announcements/{announcement}/pin', [AnnouncementController::class, 'togglePin'])->name('announcements.pin');
        Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::post('announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('announcements.unpublish');
        Route::post('announcements/{announcement}/duplicate', [AnnouncementController::class, 'duplicate'])->name('announcements.duplicate');
        Route::post('announcements/preview', [AnnouncementController::class, 'preview'])->name('announcements.preview');
        Route::post('announcements/bulk', [AnnouncementController::class, 'bulk'])->name('announcements.bulk');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
        Route::post('/backup/database', [BackupController::class, 'download'])->name('backup.database');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.update-role');
        Route::post('/users/{user}/approve-deletion', [UserManagementController::class, 'approveDeletion'])->name('users.approve-deletion');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::post('/settings/password', [SettingsController::class, 'updateOwnPassword'])->name('settings.password.update');
        Route::post('/settings/profile-photo', [SettingsController::class, 'updateProfilePhoto'])->name('settings.profile-photo.update');
        Route::post('/settings/profile-photo/remove', [SettingsController::class, 'removeProfilePhoto'])->name('settings.profile-photo.remove');
    });
