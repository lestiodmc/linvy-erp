<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CommandPaletteMenuRegistry
{
    public function search(User $user, string $query, int $limit = 5): array
    {
        $needle = Str::lower(trim($query));

        return $this->authorized($user)
            ->filter(function (array $item) use ($needle): bool {
                if ($needle === '') {
                    return true;
                }

                return Str::contains(Str::lower(implode(' ', [
                    $item['label'],
                    $item['module'],
                    $item['description'],
                    implode(' ', $item['keywords']),
                ])), $needle);
            })
            ->sortBy(fn (array $item): array => [
                Str::lower($item['label']) === $needle ? 0 : (Str::startsWith(Str::lower($item['label']), $needle) ? 1 : 2),
                $item['label'],
            ])
            ->take($limit)
            ->map(fn (array $item): array => $this->result($item))
            ->values()
            ->all();
    }

    public function quickAccess(User $user): array
    {
        $preferred = [
            'purchase-requests.index', 'purchase-orders.index', 'receivings.index',
            'inventory.dashboard', 'stock-balances.index', 'item-ledger.index',
            'warehouse-transfers.index', 'stock-adjustments.index',
        ];

        return $this->authorized($user)
            ->sortBy(fn (array $item): int => ($position = array_search($item['route'], $preferred, true)) === false ? 999 : $position)
            ->take(8)
            ->map(fn (array $item): array => $this->result($item))
            ->values()
            ->all();
    }

    private function authorized(User $user): Collection
    {
        return collect(config('linvy.modules', []))->flatMap(function (array $module, string $moduleKey) use ($user): array {
            if (! ModuleManager::enabled($moduleKey) || ! $user->canAccessModule($moduleKey)) {
                return [];
            }

            if ($moduleKey === 'dashboard') {
                return [$this->entry($module['label'], 'dashboard', 'Dashboard', 'Application overview')];
            }

            return collect($module['items'] ?? [])->map(fn (array $item): array => $this->entry(
                $item['label'],
                $item['route'],
                $module['label'],
                $this->description($moduleKey, $item['label'])
            ))->all();
        })->values();
    }

    private function entry(string $label, string $route, string $module, string $description): array
    {
        return [
            'label' => $label,
            'route' => $route,
            'module' => $module,
            'description' => $description,
            'keywords' => [Str::lower($module), Str::lower($label), Str::lower($description)],
        ];
    }

    private function result(array $item): array
    {
        return [
            'type' => 'MENU',
            'title' => $item['label'],
            'description' => $item['module'].' · '.$item['description'],
            'status' => null,
            'url' => route($item['route']),
            'actions' => [],
        ];
    }

    private function description(string $module, string $label): string
    {
        return match (true) {
            Str::contains($label, 'Dashboard') => 'Module overview',
            Str::contains($label, ['Balance', 'Ledger', 'Movement']) => 'Inventory inquiry',
            Str::contains($label, ['Order', 'Request', 'Receiving', 'Transfer', 'Adjustment', 'Assignment']) => 'Transaction workspace',
            $module === 'master-data' => 'Master data',
            $module === 'settings' => 'Application settings',
            default => 'Open workspace',
        };
    }
}
