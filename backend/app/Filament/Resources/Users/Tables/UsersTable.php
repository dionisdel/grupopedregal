<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Customer;
use App\Models\Role;
use App\Services\EmailService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role.name')
                    ->label('Rol')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'pendiente' => 'warning',
                        'inactivo' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('empresa')
                    ->label('Empresa')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customer.nombre_comercial')
                    ->label('Cliente vinculado')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->options([
                        'activo' => 'Activo',
                        'pendiente' => 'Pendiente',
                        'inactivo' => 'Inactivo',
                    ]),
                SelectFilter::make('role_id')
                    ->label('Rol')
                    ->relationship('role', 'name'),
            ])
            ->recordActions([
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar registro de usuario')
                    ->modalDescription(fn ($record) => "¿Aprobar el registro de {$record->name} ({$record->email})?")
                    ->form([
                        Select::make('customer_id')
                            ->label('Vincular con Cliente existente')
                            ->options(Customer::where('activo', true)->pluck('nombre_comercial', 'id'))
                            ->searchable()
                            ->helperText('Selecciona un cliente para vincular (opcional)'),
                    ])
                    ->action(function ($record, array $data) {
                        $clienteRole = Role::where('slug', 'cliente')->first();

                        $record->update([
                            'estado' => 'activo',
                            'role_id' => $clienteRole?->id,
                            'customer_id' => $data['customer_id'] ?? $record->customer_id,
                        ]);

                        try {
                            app(EmailService::class)->sendAccountApproval($record);
                        } catch (\Throwable $e) {
                            // Log but don't block approval
                        }

                        Notification::make()
                            ->title('Usuario aprobado')
                            ->body("Se ha activado la cuenta de {$record->name}")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
