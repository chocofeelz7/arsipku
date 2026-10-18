<?php

use App\Http\Controllers\{AdminController, AuthController, DashboardController, DocumentController, ProfileController};
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:login')->name('login.store');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil/foto', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('profile.update');
    Route::get('/profil/foto', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::delete('/profil/foto', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/kata-sandi', [AuthController::class, 'password'])->name('password.edit');
    Route::put('/kata-sandi', [AuthController::class, 'updatePassword'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/aktivitas', [DashboardController::class, 'activities'])->name('activities');
    Route::get('/notifikasi', [DashboardController::class, 'inbox'])->name('inbox');
    Route::post('/notifikasi/{message}/baca', [DashboardController::class, 'read'])->name('inbox.read');
    Route::get('/validasi', [DocumentController::class, 'index'])->name('validation.index');
    Route::get('/dokumen/ekspor', [DocumentController::class, 'export'])->name('documents.export');
    Route::resource('dokumen', DocumentController::class)->parameters(['dokumen' => 'document'])->names('documents')->except('destroy');
    Route::post('/dokumen/{document}/ajukan', [DocumentController::class, 'submit'])->name('documents.submit');
    Route::post('/dokumen/{document}/tarik', [DocumentController::class, 'withdraw'])->name('documents.withdraw');
    Route::post('/dokumen/{document}/revisi', [DocumentController::class, 'revise'])->name('documents.revise');
    Route::post('/versi/{version}/validasi', [DocumentController::class, 'review'])->name('versions.review');
    Route::get('/berkas/{file}', [DocumentController::class, 'file'])->name('files.show');
    Route::get('/administrasi/pengguna', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/administrasi/divisi', [AdminController::class, 'divisions'])->name('admin.divisions');
    Route::post('/administrasi/divisi', [AdminController::class, 'storeDivision'])->name('admin.divisions.store');
    Route::post('/administrasi/pengguna', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::post('/administrasi/pengguna/{user}/status', [AdminController::class, 'toggleUser'])->name('admin.users.toggle');
    Route::put('/administrasi/pengguna/{user}/kata-sandi', [AdminController::class, 'resetPassword'])->name('admin.users.password');
    Route::get('/administrasi/kategori', [AdminController::class, 'categories'])->name('admin.categories');
    Route::post('/administrasi/kategori', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::post('/administrasi/kategori/{category}/status', [AdminController::class, 'toggleCategory'])->name('admin.categories.toggle');
});
