<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\Permission;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-populate each group's CheckboxList with current permission IDs
        $permIds = $this->record->permissions()->pluck('permissions.id')->toArray();

        $groups = Permission::select('group')->distinct()->pluck('group');
        foreach ($groups as $group) {
            $groupIds = Permission::where('group', $group)->pluck('id')->toArray();
            $data['permissions_' . \Illuminate\Support\Str::slug($group)] = array_values(
                array_intersect($permIds, $groupIds)
            );
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncPermissionsWithDependencies();
    }

    private function syncPermissionsWithDependencies(): void
    {
        $state = $this->form->getRawState();

        // Gather IDs from every CheckboxList in the form (each group section)
        $checkedIds = collect($state)
            ->filter(fn ($v) => is_array($v))
            ->flatMap(fn ($v) => $v)
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->toArray();

        $resolved = $this->resolveWithAncestors($checkedIds);
        $this->record->permissions()->sync($resolved);
    }

    private function resolveWithAncestors(array $ids): array
    {
        $all = collect($ids);
        foreach ($ids as $id) {
            $perm = Permission::with('parent.parent.parent')->find($id);
            if ($perm) {
                foreach ($perm->ancestorSlugs() as $slug) {
                    $ancestor = Permission::where('slug', $slug)->first();
                    if ($ancestor) $all->push($ancestor->id);
                }
            }
        }
        return $all->unique()->values()->toArray();
    }
}
