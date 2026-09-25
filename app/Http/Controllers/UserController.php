<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['active' => true]);

        if ($user->role === User::ROLE_CLIENT) {
            $client = Client::create([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'active' => true,
            ]);
            $user->update(['client_id' => $client->id]);
        }

        return redirect()->route('users.index')
            ->with('success', __('Usuário criado com sucesso.'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $this->guardCriticalAdminChange($user, $data);

        $wasClient = $user->role === User::ROLE_CLIENT;
        $user->update($data);
        $user->refresh();

        if (! $user->active) {
            $this->invalidateUserSessions($user);
        }

        if ($user->role === User::ROLE_CLIENT && ! $wasClient && ! $user->client_id) {
            $client = Client::create([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'active' => true,
            ]);
            $user->update(['client_id' => $client->id]);
        }

        return redirect()->route('users.index')
            ->with('success', __('Usuário atualizado com sucesso.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $this->guardCriticalAdminChange($user, ['active' => false, 'role' => null]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', __('Usuário excluído com sucesso.'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->guardCriticalAdminChange($user, ['active' => ! $user->active]);

        $user->update(['active' => ! $user->active]);

        if (! $user->fresh()->active) {
            $this->invalidateUserSessions($user);
        }

        $message = $user->fresh()->active
            ? __('Usuário ativado.')
            : __('Usuário desativado.');

        return redirect()->back()->with('success', $message);
    }

    private function invalidateUserSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }

    private function guardCriticalAdminChange(User $user, array $data): void
    {
        if (! $user->isAdmin()) {
            return;
        }

        $wouldDeactivate = array_key_exists('active', $data) && ! (bool) $data['active'];
        $wouldDemote = array_key_exists('role', $data) && $data['role'] !== User::ROLE_ADMIN;
        $isSelfChange = auth()->id() === $user->id;
        $isLastActiveAdmin = User::where('role', User::ROLE_ADMIN)
            ->where('active', true)
            ->whereKeyNot($user->id)
            ->doesntExist();

        if ($isSelfChange && ($wouldDeactivate || $wouldDemote)) {
            abort(422, __('Você não pode remover seu próprio acesso de administrador.'));
        }

        if ($isLastActiveAdmin && ($wouldDeactivate || $wouldDemote)) {
            abort(422, __('É necessário manter pelo menos um administrador ativo.'));
        }
    }
}
