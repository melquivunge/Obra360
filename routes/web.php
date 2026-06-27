<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
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

// User management — admin only.
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'user'])
        ->names('users');
    Route::post('usuarios/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('users.toggle-active');
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
