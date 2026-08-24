<?php

namespace App\Providers;

use App\Http\View\Composers\ClientLayoutComposer;
use App\Models\Client;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Photo;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectUpdate;
use App\Models\Setting;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Observers\MediaCleanupObserver;
use App\Observers\ProjectUpdateObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Defines application-level Gates for role-based access (SEC-01 / D-16).
     */
    public function boot(): void
    {
        ProjectUpdate::observe(ProjectUpdateObserver::class);

        // Audit trail (SEC-05). ProjectUpdate is deliberately absent: it is
        // client-facing content, already visible in the project history.
        //
        // Plan and PlanVersion are audited for the same reason Document is: a
        // plan is a contractual artefact, and "which drawing was current, who
        // uploaded it, when it was sent and superseded" is precisely the kind
        // of question the trail exists to answer. PlanVersion carries those
        // columns — auditing only Plan would record the label and lose the
        // facts. It is also what PORT-05 will read once the client can approve
        // a version from the portal.
        foreach ([
            Project::class, Client::class, User::class, Expense::class,
            Payment::class, Photo::class, Document::class, ProjectPhase::class,
            Setting::class, Plan::class, PlanVersion::class,
        ] as $auditable) {
            $auditable::observe(AuditObserver::class);
        }

        // Limpeza dos arquivos no disco quando a exclusão passa a ser
        // definitiva. Só a obra e a planta: são as duas exclusões que deixam
        // arquivos para trás (o `destroy` de foto, documento e despesa já
        // apaga o arquivo no momento). Ver MediaCleanupObserver.
        Project::observe(MediaCleanupObserver::class);
        Plan::observe(MediaCleanupObserver::class);

        View::composer('components.layouts.client', ClientLayoutComposer::class);

        // Grants access to the admin panel (admin, manager, financial roles).
        Gate::define('access-admin', fn (User $u) => in_array($u->role, [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_FINANCIAL,
        ], true));

        // Grants user management capabilities (admin only).
        Gate::define('manage-users', fn (User $u) => $u->isAdmin());

        // Grants write access to the project structure — projects, phases,
        // photos, documents, updates (admin and manager roles). Mirrors the
        // `role:admin,manager` middleware so views can hide affordances that
        // would 403 on click; the routes remain the enforcement point.
        Gate::define('manage-projects', fn (User $u) => in_array($u->role, [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
        ], true));

        // Grants access to financial data (admin and financial roles).
        Gate::define('access-financial', fn (User $u) => in_array($u->role, [
            User::ROLE_ADMIN,
            User::ROLE_FINANCIAL,
        ], true));
    }
}
