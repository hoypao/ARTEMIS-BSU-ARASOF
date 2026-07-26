<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOpsController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SpartanController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\AdmissionAppealController;
use App\Http\Controllers\DeanDashboardController;
use App\Http\Controllers\PathfitFacultyDashboardController;
use App\Http\Controllers\TrainerDashboardController;
use Illuminate\Support\Facades\Route;

// ---------------- Public pages ----------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events', [EventsController::class, 'index'])->name('events');
Route::get('/track', [TrackController::class, 'page'])->name('track');
Route::post('/track/lookup', [TrackController::class, 'lookup'])->name('track.lookup');
Route::get('/appeal/apply', [AdmissionAppealController::class, 'showForm'])->name('appeal.apply');
Route::post('/appeal/apply', [AdmissionAppealController::class, 'submit'])->name('appeal.submit');
Route::get('/ask-spartan', [SpartanController::class, 'page'])->name('ask-spartan');
Route::post('/chat', [SpartanController::class, 'chat'])->name('chat');

// ---------------- Auth ----------------
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// ---------------- Student ----------------
Route::middleware('role:student')->group(function () {
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
    Route::post('/applications/apply', [ApplicationController::class, 'apply'])->name('applications.apply');
    Route::post('/events/rsvp', [EventsController::class, 'rsvp'])->name('events.rsvp');
    Route::post('/profile/photo', [StudentDashboardController::class, 'uploadPhoto'])->name('profile.photo.upload');
    Route::post('/benefits/report', [StudentDashboardController::class, 'submitBenefitReport'])->name('benefits.report.submit');
    Route::post('/academic/report', [StudentDashboardController::class, 'submitAcademicReport'])->name('academic.report.submit');
});

// ---------------- Trainer ----------------
Route::middleware('role:trainer')->group(function () {
    Route::get('/trainer/dashboard', [TrainerDashboardController::class, 'index'])->name('trainer.dashboard');
    Route::post('/trainer/hours', [TrainerDashboardController::class, 'updateHours'])->name('trainer.hours.update');
    Route::post('/trainer/endorse', [TrainerDashboardController::class, 'endorseApplication'])->name('trainer.endorse');
    Route::post('/trainer/audition/rate', [TrainerDashboardController::class, 'rateAudition'])->name('trainer.audition.rate');
});

// ---------------- PATHFit Faculty ----------------
Route::middleware('role:pathfit_faculty')->group(function () {
    Route::get('/pathfit-faculty/dashboard', [PathfitFacultyDashboardController::class, 'index'])->name('pathfit-faculty.dashboard');
});

// ---------------- College Dean ----------------
Route::middleware('role:college_dean')->group(function () {
    Route::get('/dean/dashboard', [DeanDashboardController::class, 'index'])->name('dean.dashboard');
});

// ---------------- Shared (admin + PATHFit Faculty / College Dean) ----------------
Route::middleware('role:admin,pathfit_faculty')->group(function () {
    Route::post('/applications/review', [ApplicationController::class, 'review'])->name('applications.review');
});
Route::middleware('role:admin,college_dean')->group(function () {
    Route::post('/faculty-complaints/update', [AdminOpsController::class, 'facultyComplaints'])->name('faculty-complaints.update');
});

// ---------------- Admin ----------------
Route::middleware('role:admin')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/applications/review', [ApplicationController::class, 'review'])->name('admin.applications.review');
    Route::post('/admin/events/save', [AdminOpsController::class, 'events'])->name('admin.events.save');
    Route::post('/admin/events/attendance', [AdminOpsController::class, 'attendance'])->name('admin.events.attendance');
    Route::post('/admin/events/checkin', [AdminOpsController::class, 'checkin'])->name('admin.events.checkin');
    Route::post('/admin/announcements', [AdminOpsController::class, 'announcements'])->name('admin.announcements');
    Route::post('/admin/faculty-complaints', [AdminOpsController::class, 'facultyComplaints'])->name('admin.faculty-complaints');
    Route::post('/admin/trainer-evaluations', [AdminOpsController::class, 'trainerEvaluations'])->name('admin.trainer-evaluations');
    Route::post('/admin/honoraria', [AdminOpsController::class, 'honoraria'])->name('admin.honoraria');
    Route::post('/admin/bantog-evaluators', [AdminOpsController::class, 'bantogEvaluators'])->name('admin.bantog-evaluators');
    Route::post('/admin/partnerships', [AdminOpsController::class, 'partnerships'])->name('admin.partnerships');
    Route::post('/admin/admission-appeals', [AdminOpsController::class, 'admissionAppeals'])->name('admin.admission-appeals');
    Route::post('/admin/academic-support', [AdminOpsController::class, 'academicSupport'])->name('admin.academic-support');
    Route::post('/admin/settings', [AdminOpsController::class, 'settings'])->name('admin.settings.save');
});
