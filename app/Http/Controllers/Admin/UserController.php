<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends ResourceController
{
    protected string $model = User::class;
    protected string $route = 'users';
    protected string $title = 'User';
    protected string $viewPath = 'settings.users';
    protected array $with = ['role'];
    protected array $columns = ['name', 'email', 'role.name', 'created_at'];
    protected array $rules = ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'role_id' => ['nullable', 'integer'], 'password' => ['nullable', 'string', 'min:8']];

    public function __construct()
    {
        $this->fields = [
            'name' => ['label' => 'Name', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'email'],
            'role_id' => ['label' => 'Role', 'type' => 'select', 'options' => Role::orderBy('name')->pluck('name', 'id')->toArray(), 'nullable' => true],
            'password' => ['label' => 'Password', 'type' => 'password', 'always_empty' => true],
        ];
    }

    protected function validated(Request $request, ?Model $record = null): array
    {
        $data = parent::validated($request, $record);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }
}
