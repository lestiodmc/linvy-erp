<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RoleController extends ResourceController
{
    protected string $model = Role::class;
    protected string $route = 'roles';
    protected string $title = 'Role';
    protected array $columns = ['code', 'name', 'permissions', 'is_active'];
    protected array $rules = ['code' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'permissions' => ['nullable', 'array'], 'is_active' => ['nullable']];

    public function __construct()
    {
        $modules = collect(config('linvy.modules'))->mapWithKeys(fn ($module, $key) => [$key => $module['label']])->toArray();

        $this->fields = [
            'code' => ['label' => 'Code', 'type' => 'text'],
            'name' => ['label' => 'Name', 'type' => 'text'],
            'permissions' => ['label' => 'Allowed Modules', 'type' => 'multicheckbox', 'options' => ['*' => 'All Modules'] + $modules],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    protected function validated(Request $request, ?Model $record = null): array
    {
        $data = parent::validated($request, $record);
        $data['permissions'] = $request->input('permissions', []);

        return $data;
    }
}
