<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class ResourceController extends Controller
{
    protected string $model;

    protected string $route;

    protected string $title;

    protected array $columns = [];

    protected array $fields = [];

    protected array $rules = [];

    protected array $with = [];

    public function index(): View
    {
        $records = $this->query()->latest('id')->paginate(15);

        return view('admin.resources.index', $this->viewData(compact('records')));
    }

    public function create(): View
    {
        return view('admin.resources.form', $this->viewData([
            'record' => null,
            'method' => 'POST',
            'action' => route($this->route.'.store'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $record = $this->model::create($this->validated($request));

        return redirect()->route($this->route.'.show', $record)->with('status', $this->title.' dibuat.');
    }

    public function show(int|string $record): View
    {
        $record = $this->findRecord($record);

        return view('admin.resources.show', $this->viewData(compact('record')));
    }

    public function edit(int|string $record): View
    {
        $record = $this->findRecord($record);

        return view('admin.resources.form', $this->viewData([
            'record' => $record,
            'method' => 'PUT',
            'action' => route($this->route.'.update', $record),
        ]));
    }

    public function update(Request $request, int|string $record): RedirectResponse
    {
        $record = $this->findRecord($record);
        $record->update($this->validated($request, $record));

        return redirect()->route($this->route.'.show', $record)->with('status', $this->title.' diperbarui.');
    }

    public function destroy(int|string $record): RedirectResponse
    {
        $record = $this->findRecord($record);
        $record->delete();

        return redirect()->route($this->route.'.index')->with('status', $this->title.' dihapus.');
    }

    protected function validated(Request $request, ?Model $record = null): array
    {
        $data = $request->validate($this->rules);

        foreach ($this->fields as $name => $field) {
            if (($field['type'] ?? null) === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }
        }

        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    protected function query()
    {
        return $this->model::query()->with($this->with);
    }

    protected function findRecord(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    protected function viewData(array $data = []): array
    {
        return array_merge([
            'title' => $this->title,
            'route' => $this->route,
            'columns' => $this->columns,
            'fields' => $this->fields,
        ], $data);
    }
}
