<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Permission;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $this->syncPermissions();
    }

    private function syncPermissions(): void
    {
        $permissionIds = [];
        $modules = Permission::distinct()->pluck('module');

        foreach ($modules as $module) {
            $key = "permissions_{$module}";
            $selected = $this->data[$key] ?? [];
            if (is_array($selected)) {
                $permissionIds = array_merge($permissionIds, $selected);
            }
        }

        $this->record->permissions()->sync($permissionIds);
    }
}
