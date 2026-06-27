<?php

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Legacy /dashboard route — redirect to the role-appropriate dashboard.
Route::get('/dashboard', function () {
    if (auth()->user()->isClient()) {
        return redirect()->route('portal.dashboard');
    }

    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin dashboard + future Phase 2 admin routes — restricted to admin/manager/financial.
Route::middleware(['auth', 'role:admin,manager,financial'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

// Client portal dashboard + future Phase 4 client routes — restricted to client.
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('/portal', function () {
        return view('client.dashboard');
    })->name('portal.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
