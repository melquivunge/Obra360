<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectDocumentController;
use App\Http\Controllers\Admin\ProjectExpenseController;
use App\Http\Controllers\Admin\ProjectPhaseController;
use App\Http\Controllers\Admin\ProjectPhotoController;
use App\Http\Controllers\Admin\ProjectPlanController;
use App\Http\Controllers\Admin\ProjectPlanVersionController;
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

// ── Área admin ───────────────────────────────────────────────────────────────
//
// Tectos de escrita da área interna (POST/PUT/DELETE). Não é uma porta aberta
// ao mundo — todas exigem sessão de admin, gestor ou financeiro — por isso os
// valores são deliberadamente largos: travam o que um humano nunca faz (um
// script em ciclo, uma conta interna comprometida) sem partir o trabalho de
// quem lança um lote de dados de uma vez. Um tecto apertado de mais é pior do
// que tecto nenhum, porque bloqueia quem paga pelo produto.
//
//   throttle:120,1  escritas leves — uma linha na base de dados por pedido
//                   (despesa, fase, pagamento, atualização, mensagem, cliente,
//                   obra, utilizador). 2/s está muito acima do ritmo de quem
//                   preenche formulários, mesmo a lançar 50 despesas seguidas.
//   throttle:30,1   upload de documentos — um ficheiro de até 50 MB por pedido,
//                   enviado de cada vez; 1 a cada 2 s nunca é atingido à mão.
//   throttle:20,1   upload de fotos/vídeos — N ficheiros de até 50 MB cada no
//                   mesmo pedido, mais a geração de miniaturas. É de longe o
//                   pedido mais caro do sistema e o único com tecto apertado.

// O formulário de nova obra tem de ser declarado ANTES de `/obras/{project}`:
// o Laravel casa pela ordem de declaração, o parâmetro apanhava o literal
// "nova", o route-model binding falhava e a resposta era 404 — na prática, não
// se conseguia criar uma obra pela interface. Fica no seu próprio grupo para
// manter a criação em admin,manager sem a alargar ao papel financeiro.
Route::middleware(['auth', 'role:admin,manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/obras/nova', [ProjectController::class, 'create'])->name('projects.create');
});

Route::middleware(['auth', 'role:admin,manager,financial'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Reports: same visibility rules as project data (scoped in the controller).
    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/obras/{project}/relatorios/{type}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/obras/{project}/relatorios/{type}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    // Read project data + messages: all internal roles.
    Route::get('/obras', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/obras/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/obras/{project}/capa', [ProjectPhotoController::class, 'cover'])->name('projects.cover');
    Route::get('/obras/{project}/fotos/{photo}', [ProjectPhotoController::class, 'show'])->name('projects.photos.show');
    // PLAN-04/PLAN-06: descarregar qualquer versão, corrente ou substituída.
    // É leitura, por isso fica ao lado das fotos e acessível ao financeiro, que
    // lê a obra toda para contexto mas não lhe toca na estrutura.
    Route::get('/obras/{project}/plantas/{plan}/versoes/{version}/download', [ProjectPlanVersionController::class, 'download'])->name('projects.plans.versions.download');
    Route::get('/mensagens', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/obras/{project}/mensagens', [MessageController::class, 'messages'])->name('projects.messages.index');
    Route::post('/obras/{project}/mensagens', [MessageController::class, 'store'])
        ->middleware('throttle:120,1')
        ->name('projects.messages.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Audit trail (SEC-05) — admin only.
    Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');

    // Clients
    Route::get('/clientes', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clientes/novo', [ClientController::class, 'create'])->name('clients.create');
    Route::get('/clientes/{client}/editar', [ClientController::class, 'edit'])->name('clients.edit');

    Route::middleware('throttle:120,1')->group(function () {
        Route::post('/clientes', [ClientController::class, 'store'])->name('clients.store');
        Route::put('/clientes/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clientes/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
        Route::post('/clientes/{client}/vincular-usuario', [ClientController::class, 'linkUser'])->name('clients.link-user');
        Route::delete('/clientes/{client}/desvincular/{user}', [ClientController::class, 'unlinkUser'])->name('clients.unlink-user');
    });

    // Settings
    Route::get('/configuracoes', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/configuracoes', [SettingController::class, 'update'])
        ->middleware('throttle:120,1')
        ->name('settings.update');
});

Route::middleware(['auth', 'role:admin,manager'])->prefix('admin')->name('admin.')->group(function () {
    // Projects (o formulário de criação está declarado mais acima, por ordem)
    Route::get('/obras/{project}/editar', [ProjectController::class, 'edit'])->name('projects.edit');

    // Documents — download é leitura, fica fora do tecto de escrita.
    Route::get('/obras/{project}/documentos/{document}/download', [ProjectDocumentController::class, 'download'])->name('projects.documents.download');

    // Escritas leves: uma linha na base de dados por pedido.
    Route::middleware('throttle:120,1')->group(function () {
        Route::post('/obras', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/obras/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/obras/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

        // Phases (nested under project)
        Route::post('/obras/{project}/fases', [ProjectPhaseController::class, 'store'])->name('projects.phases.store');
        Route::put('/obras/{project}/fases/{phase}', [ProjectPhaseController::class, 'update'])->name('projects.phases.update');
        Route::delete('/obras/{project}/fases/{phase}', [ProjectPhaseController::class, 'destroy'])->name('projects.phases.destroy');

        // Apagar não move ficheiros para dentro do servidor; só remove.
        Route::delete('/obras/{project}/fotos/{photo}', [ProjectPhotoController::class, 'destroy'])->name('projects.photos.destroy');
        Route::delete('/obras/{project}/documentos/{document}', [ProjectDocumentController::class, 'destroy'])->name('projects.documents.destroy');

        // Updates
        Route::post('/obras/{project}/atualizacoes', [ProjectController::class, 'storeUpdate'])->name('projects.updates.store');

        // Plantas — a planta é uma linha na base de dados; o ficheiro é a
        // versão, que tem tecto próprio mais abaixo.
        Route::post('/obras/{project}/plantas', [ProjectPlanController::class, 'store'])->name('projects.plans.store');
        Route::put('/obras/{project}/plantas/{plan}', [ProjectPlanController::class, 'update'])->name('projects.plans.update');
        Route::delete('/obras/{project}/plantas/{plan}', [ProjectPlanController::class, 'destroy'])->name('projects.plans.destroy');
        Route::post('/obras/{project}/plantas/{plan}/versoes/{version}/enviar', [ProjectPlanVersionController::class, 'send'])->name('projects.plans.versions.send');
    });

    // Uploads: os dois pedidos pesados do sistema, com tecto próprio.
    Route::post('/obras/{project}/fotos', [ProjectPhotoController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('projects.photos.store');
    Route::post('/obras/{project}/documentos', [ProjectDocumentController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('projects.documents.store');
    // Uma versão de planta é um ficheiro de até 64 MB por pedido — o mesmo
    // perfil do upload de documentos, logo o mesmo tecto.
    Route::post('/obras/{project}/plantas/{plan}/versoes', [ProjectPlanVersionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('projects.plans.versions.store');
});

Route::middleware(['auth', 'role:admin,financial', 'throttle:120,1'])->prefix('admin')->name('admin.')->group(function () {
    // Só há escritas neste grupo, por isso o tecto vive no próprio grupo.

    // Expenses
    Route::post('/obras/{project}/despesas', [ProjectExpenseController::class, 'store'])->name('projects.expenses.store');
    Route::put('/obras/{project}/despesas/{expense}', [ProjectExpenseController::class, 'update'])->name('projects.expenses.update');
    Route::delete('/obras/{project}/despesas/{expense}', [ProjectExpenseController::class, 'destroy'])->name('projects.expenses.destroy');

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
        ->names('users')
        // Só as escritas levam tecto; os ecrãs de listagem e edição ficam livres.
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:120,1');
    Route::post('usuarios/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('throttle:120,1')
        ->name('users.toggle-active');
});

// ── Client portal ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:client'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::get('/obras/{project}', [ClientProjectController::class, 'show'])->name('projects.show');
    Route::get('/obras/{project}/capa', [ClientProjectController::class, 'cover'])->name('projects.cover');
    Route::get('/obras/{project}/fotos/{photo}', [ClientProjectController::class, 'photo'])->name('projects.photos.show');
    Route::get('/obras/{project}/mensagens', [ClientProjectController::class, 'messages'])->name('projects.messages');
    // Clients are the least-trusted authenticated role; cap their writes.
    Route::post('/obras/{project}/mensagens', [ClientProjectController::class, 'storeMessage'])
        ->middleware('throttle:20,1')
        ->name('projects.messages.store');
    Route::get('/obras/{project}/documentos/{document}/download', [ClientProjectController::class, 'downloadDocument'])->name('projects.documents.download');
    Route::post('/mensagens/marcar-lidas', function () {
        session(['messages_last_viewed_at' => now()->timestamp]);

        return response()->json(['ok' => true]);
    })->middleware('throttle:60,1')->name('messages.mark-read');

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
