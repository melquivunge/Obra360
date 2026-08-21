<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\ProjectController as ClientProjectController;
use App\Http\Controllers\Portal\PushSubscriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    if (auth()->user()->isClient()) {
        return redirect()->route('portal.dashboard');
    }

    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ── Admin routes ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,manager,financial'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Reports: same visibility rules as project data (scoped in the controller).
    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/obras/{project}/relatorios/{type}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/obras/{project}/relatorios/{type}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    // Read project data + messages: all internal roles.
    Route::get('/obras', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/obras/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/obras/{project}/capa', [ProjectController::class, 'cover'])->name('projects.cover');
    Route::get('/obras/{project}/fotos/{photo}', [ProjectController::class, 'photo'])->name('projects.photos.show');
    Route::get('/mensagens', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/obras/{project}/mensagens', [MessageController::class, 'messages'])->name('projects.messages.index');
    Route::post('/obras/{project}/mensagens', [MessageController::class, 'store'])->name('projects.messages.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Audit trail (SEC-05) — admin only.
    Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');

    // Clients
    Route::get('/clientes', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clientes/novo', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clientes', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clientes/{client}/editar', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clientes/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clientes/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    Route::post('/clientes/{client}/vincular-usuario', [ClientController::class, 'linkUser'])->name('clients.link-user');
    Route::delete('/clientes/{client}/desvincular/{user}', [ClientController::class, 'unlinkUser'])->name('clients.unlink-user');

    // Settings
    Route::get('/configuracoes', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/configuracoes', [SettingController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', 'role:admin,manager'])->prefix('admin')->name('admin.')->group(function () {
    // Projects
    Route::get('/obras/nova', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/obras', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/obras/{project}/editar', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/obras/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/obras/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Phases (nested under project)
    Route::post('/obras/{project}/fases', [ProjectController::class, 'storePhase'])->name('projects.phases.store');
    Route::put('/obras/{project}/fases/{phase}', [ProjectController::class, 'updatePhase'])->name('projects.phases.update');
    Route::delete('/obras/{project}/fases/{phase}', [ProjectController::class, 'destroyPhase'])->name('projects.phases.destroy');

    // Photos
    Route::post('/obras/{project}/fotos', [ProjectController::class, 'storePhoto'])->name('projects.photos.store');
    Route::delete('/obras/{project}/fotos/{photo}', [ProjectController::class, 'destroyPhoto'])->name('projects.photos.destroy');

    // Documents
    Route::post('/obras/{project}/documentos', [ProjectController::class, 'storeDocument'])->name('projects.documents.store');
    Route::get('/obras/{project}/documentos/{document}/download', [ProjectController::class, 'downloadDocument'])->name('projects.documents.download');
    Route::delete('/obras/{project}/documentos/{document}', [ProjectController::class, 'destroyDocument'])->name('projects.documents.destroy');

    // Updates
    Route::post('/obras/{project}/atualizacoes', [ProjectController::class, 'storeUpdate'])->name('projects.updates.store');
});

Route::middleware(['auth', 'role:admin,financial'])->prefix('admin')->name('admin.')->group(function () {
    // Expenses
    Route::post('/obras/{project}/despesas', [ProjectController::class, 'storeExpense'])->name('projects.expenses.store');
    Route::delete('/obras/{project}/despesas/{expense}', [ProjectController::class, 'destroyExpense'])->name('projects.expenses.destroy');

    // Payments
    Route::post('/obras/{project}/pagamentos', [PaymentController::class, 'store'])->name('projects.payments.store');
    Route::put('/obras/{project}/pagamentos/{payment}', [PaymentController::class, 'update'])->name('projects.payments.update');
    Route::post('/obras/{project}/pagamentos/{payment}/pago', [PaymentController::class, 'markPaid'])->name('projects.payments.mark-paid');
    Route::delete('/obras/{project}/pagamentos/{payment}', [PaymentController::class, 'destroy'])->name('projects.payments.destroy');
});

// User management — admin only
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'user'])
        ->except(['show'])
        ->names('users');
    Route::post('usuarios/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('users.toggle-active');
});

// ── Client portal ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:client'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::get('/obras/{project}', [ClientProjectController::class, 'show'])->name('projects.show');
    Route::get('/obras/{project}/capa', [ClientProjectController::class, 'cover'])->name('projects.cover');
    Route::get('/obras/{project}/fotos/{photo}', [ClientProjectController::class, 'photo'])->name('projects.photos.show');
    Route::get('/obras/{project}/mensagens', [ClientProjectController::class, 'messages'])->name('projects.messages');
    Route::post('/obras/{project}/mensagens', [ClientProjectController::class, 'storeMessage'])->name('projects.messages.store');
    Route::get('/obras/{project}/documentos/{document}/download', [ClientProjectController::class, 'downloadDocument'])->name('projects.documents.download');
    Route::post('/mensagens/marcar-lidas', function () {
        session(['messages_last_viewed_at' => now()->timestamp]);

        return response()->json(['ok' => true]);
    })->name('messages.mark-read');

    // PWA push subscriptions
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('push.subscribe');
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('push.unsubscribe');
});

// Profile (all authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
