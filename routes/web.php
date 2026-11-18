<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Homepage
Route::get('/', fn() => Auth::check() ? redirect()->route('dashboard') : redirect()->route('login'));

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.post');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// RUTE TERPROTEKSI (Membutuhkan Login)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // Dashboard & Profil
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Katalog & Detail Mata Kuliah (Umum: Dosen & Mahasiswa)
    Route::get('/courses', [MataKuliahController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [MataKuliahController::class, 'show'])->whereNumber('course')->name('courses.show');

    // ------------------------------------------
    // RUTE KHUSUS DOSEN (role:dosen)
    // ------------------------------------------
    Route::middleware(['role:dosen'])->group(function () {
        // Manajemen Mata Kuliah
        Route::get('/courses/create', [MataKuliahController::class, 'create'])->name('courses.create');
        Route::post('/courses', [MataKuliahController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}/edit', [MataKuliahController::class, 'edit'])->whereNumber('course')->name('courses.edit');
        Route::put('/courses/{course}', [MataKuliahController::class, 'update'])->whereNumber('course')->name('courses.update');
        Route::delete('/courses/{course}', [MataKuliahController::class, 'destroy'])->whereNumber('course')->name('courses.destroy');

        // Persetujuan & Manajemen Mahasiswa di Kelas
        Route::patch('/courses/{course}/enrollments/{user}/approve', [MataKuliahController::class, 'approveStudent'])->whereNumber('course')->whereNumber('user')->name('courses.enrollments.approve');
        Route::patch('/courses/{course}/enrollments/{user}/reject', [MataKuliahController::class, 'rejectStudent'])->whereNumber('course')->whereNumber('user')->name('courses.enrollments.reject');
        Route::delete('/courses/{course}/unenroll/{user}', [MataKuliahController::class, 'unenrollStudent'])->whereNumber('course')->whereNumber('user')->name('courses.unenroll');

        // Materi Perkuliahan / Materials
        Route::get('/courses/{course}/materials/create', [MaterialController::class, 'create'])->whereNumber('course')->name('materials.create');
        Route::post('/courses/{course}/materials', [MaterialController::class, 'store'])->whereNumber('course')->name('materials.store');
        Route::get('/materials/{material}/edit', [MaterialController::class, 'edit'])->whereNumber('material')->name('materials.edit');
        Route::put('/materials/{material}', [MaterialController::class, 'update'])->whereNumber('material')->name('materials.update');
        Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->whereNumber('material')->name('materials.destroy');

        // Tugas & Evaluasi / Assignments
        Route::get('/courses/{course}/assignments/create', [AssignmentController::class, 'create'])->whereNumber('course')->name('assignments.create');
        Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->whereNumber('course')->name('assignments.store');
        Route::get('/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->whereNumber('assignment')->name('assignments.edit');
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->whereNumber('assignment')->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->whereNumber('assignment')->name('assignments.destroy');
        Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])->whereNumber('assignment')->name('assignments.submissions');

        // Penilaian Tugas & Export CSV (Diaktifkan sesuai Modul 4)
        Route::patch('/submissions/{submission}/grade', [SubmissionController::class, 'grade'])->whereNumber('submission')->name('submissions.grade');
        Route::get('/assignments/{assignment}/export-csv', [SubmissionController::class, 'exportCsv'])->whereNumber('assignment')->name('assignments.export-csv');
    });

    // ------------------------------------------
    // RUTE KHUSUS MAHASISWA (role:mahasiswa)
    // ------------------------------------------
    Route::middleware(['role:mahasiswa'])->group(function () {
        Route::post('/courses/{course}/enroll-request', [MataKuliahController::class, 'enrollRequest'])->whereNumber('course')->name('courses.enroll-request');
        Route::post('/assignments/{assignment}/submit', [SubmissionController::class, 'store'])->whereNumber('assignment')->name('submissions.store');
    });

});