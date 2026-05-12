<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\Permission;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $this->syncPermissionsWithDependencies();
    }

    private function syncPermissionsWithDependencies(): void
    {
        // Collect all checked permission IDs from form state (each group section
        // has its own CheckboxList named 'permissions')
        $checkedIds = collect($this->form->getRawState())
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
