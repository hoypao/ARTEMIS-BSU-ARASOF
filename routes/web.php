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
use Illuminate\Support\Facades\Route;

// ---------------- Public pages ----------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events', [EventsController::class, 'index'])->name('events');
Route::get('/track', [TrackController::class, 'page'])->name('track');
Route::post('/track/lookup', [TrackController::class, 'lookup'])->name('track.lookup');
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
});

// ---------------- Admin ----------------
Route::middleware('role:admin')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/applications/review', [ApplicationController::class, 'review'])->name('admin.applications.review');
    Route::post('/admin/events/save', [AdminOpsController::class, 'events'])->name('admin.events.save');
    Route::post('/admin/events/attendance', [AdminOpsController::class, 'attendance'])->name('admin.events.attendance');
    Route::post('/admin/announcements', [AdminOpsController::class, 'announcements'])->name('admin.announcements');
    Route::post('/admin/faculty-complaints', [AdminOpsController::class, 'facultyComplaints'])->name('admin.faculty-complaints');
    Route::post('/admin/trainer-evaluations', [AdminOpsController::class, 'trainerEvaluations'])->name('admin.trainer-evaluations');
});
