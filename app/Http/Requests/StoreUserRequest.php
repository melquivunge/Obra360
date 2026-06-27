<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'role'     => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
                User::ROLE_FINANCIAL,
                User::ROLE_CLIENT,
            ])],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'Este campo é obrigatório.',
            'email.required'    => 'Este campo é obrigatório.',
            'email.email'       => 'Insira um e-mail válido.',
            'email.unique'      => 'Este e-mail já está em uso.',
            'role.required'     => 'Este campo é obrigatório.',
            'role.in'           => 'Perfil inválido selecionado.',
            'password.required' => 'Este campo é obrigatório.',
            'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ];
    }
}
