<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    protected string $viewPath = '';

    protected ?string $documentType = null;

    public function index(): View
    {
        $records = $this->query()->latest('id')->paginate(15);

        return view($this->viewName('index'), $this->viewData(compact('records')));
    }

    public function create(): View
    {
        return view($this->viewName('create'), $this->viewData([
            'record' => null,
            'method' => 'POST',
            'action' => route($this->route.'.store'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $record = DB::transaction(function () use ($request): Model {
            $data = $this->validated($request);

            if ($this->documentType && blank($data['number'] ?? null)) {
                $data['number'] = app(DocumentNumberService::class)->generate($this->documentType);
            }

            return $this->model::create($data);
        });

        return redirect()->route($this->route.'.show', $record)->with('status', $this->title.' dibuat.');
    }

    public function show(int|string $record): View
    {
        $record = $this->findRecord($record);

        return view($this->viewName('show'), $this->viewData(compact('record')));
    }

    public function edit(int|string $record): View
    {
        $record = $this->findRecord($record);

        return view($this->viewName('edit'), $this->viewData([
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
            'fields' => $this->visibleFields(),
            'viewPath' => $this->viewPath,
        ], $data);
    }

    protected function viewName(string $view): string
    {
        return $this->viewPath.'.'.$view;
    }

    protected function visibleFields(): array
    {
        if (! $this->documentType || request()->routeIs($this->route.'.edit')) {
            return $this->fields;
        }

        $fields = $this->fields;
        unset($fields['number']);

        return $fields;
    }
}
