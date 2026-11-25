<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Cadastro e edição de contas da equipe do NAE (M9).
 */
class UserRequest extends FormRequest
{
    use HasPortugueseMessages {
        messages as portugueseMessages;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $editing */
        $editing = $this->route('user');
        $canAssignRoles = $this->user()?->can('acl.assign_roles') ?? false;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editing?->id)],
            'roles' => $canAssignRoles ? ['array'] : ['prohibited'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'password' => $editing === null ? ['required', 'confirmed', Password::defaults()] : ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nome', 'email' => 'e-mail', 'roles' => 'perfis', 'password' => 'senha'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->portugueseMessages(),
            'email.unique' => 'Já existe uma conta com este e-mail.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
        ];
    }

    /**
     * @return list<int>
     */
    public function roleIds(): array
    {
        /** @var array<int|string> $roles */
        $roles = $this->validated('roles', []);

        return array_values(array_map(intval(...), $roles));
    }
}
