<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withTrashed(false)->latest();
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('document', 'like', "%{$request->search}%");
            });
        }
        $clients = $query->paginate(20)->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'email' => 'nullable|email|max:255|unique:users,email|unique:clients,email',
            'phone' => 'nullable|string|max:40',
            'document' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'country' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:2000',
            'active' => 'boolean',
            'password' => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string',
        ]);

        $data['active'] = $request->boolean('active', true);
        $client = Client::create($data);

        if (! empty($data['email']) && ! empty($data['password'])) {
            $user = User::create([
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'role' => User::ROLE_CLIENT,
                'password' => Hash::make($data['password']),
                'active' => $client->active,
                'client_id' => $client->id,
            ]);
        }

        return redirect()->route('admin.clients.index')->with('success', 'Cliente criado com sucesso.');
    }

    public function edit(Client $client)
    {
        $linkedUsers = User::where('client_id', $client->id)->get();
        $availableUsers = User::where('role', User::ROLE_CLIENT)->whereNull('client_id')->get();

        return view('admin.clients.edit', compact('client', 'linkedUsers', 'availableUsers'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($client),
                Rule::unique('users', 'email')->ignore($client->users()->value('id')),
            ],
            'phone' => 'nullable|string|max:40',
            'document' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'country' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:2000',
            'active' => 'boolean',
        ]);
        $data['active'] = $request->boolean('active');
        $client->update($data);

        // Sync active status to linked users
        User::where('client_id', $client->id)->update(['active' => $client->active]);
        if (! $client->active) {
            $this->invalidateClientUserSessions($client);
        }

        return redirect()->route('admin.clients.index')->with('success', 'Cliente atualizado.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Cliente removido.');
    }

    public function linkUser(Request $request, Client $client)
    {
        $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('role', User::ROLE_CLIENT)
                    ->whereNull('client_id'),
            ],
        ]);
        User::where('id', $request->user_id)->update(['client_id' => $client->id]);

        return back()->with('success', 'Usuário vinculado ao cliente.');
    }

    public function unlinkUser(Client $client, User $user)
    {
        abort_unless((int) $user->client_id === (int) $client->id, 404);

        $user->update(['client_id' => null]);

        return back()->with('success', 'Vínculo removido.');
    }

    private function invalidateClientUserSessions(Client $client): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->whereIn('user_id', $client->users()->pluck('id'))
            ->delete();
    }
}
