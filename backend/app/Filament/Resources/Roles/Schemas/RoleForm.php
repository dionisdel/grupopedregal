<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $permissionsByModule = Permission::all()->groupBy('module');

        $permissionSections = [];
        foreach ($permissionsByModule as $module => $permissions) {
            $permissionSections[] = CheckboxList::make("permissions_{$module}")
                ->label(ucfirst($module ?? 'General'))
                ->options($permissions->pluck('name', 'id')->toArray())
                ->columns(2)
                ->dehydrated(false)
                ->afterStateHydrated(function ($component, $state, $record) use ($permissions) {
                    if ($record) {
                        $rolePermissionIds = $record->permissions->pluck('id')->toArray();
                        $modulePermissionIds = $permissions->pluck('id')->toArray();
                        $component->state(array_values(array_intersect($rolePermissionIds, $modulePermissionIds)));
                    }
                });
        }

        return $schema
            ->components([
                Section::make('Información del Rol')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2),
                        Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),
                    ])->columns(2),

                Section::make('Permisos por Módulo')
                    ->description('Selecciona los permisos que tendrá este rol')
                    ->schema($permissionSections),
            ]);
    }
}
