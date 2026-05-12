<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool   { return auth()->user()?->hasPermission('roles.view') ?? false; }
    public static function canViewAny(): bool   { return auth()->user()?->hasPermission('roles.view') ?? false; }
    public static function canCreate(): bool    { return auth()->user()?->hasPermission('roles.create') ?? false; }
    public static function canEdit($r): bool    { return auth()->user()?->hasPermission('roles.edit') ?? false; }
    public static function canDelete($r): bool  { return auth()->user()?->hasPermission('roles.delete') ?? false; }
    public static function canView($r): bool    { return auth()->user()?->hasPermission('roles.view') ?? false; }

    public static function form(Form $form): Form
    {
        $permissionsByGroup = Permission::with('parent')
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');

        $permissionSections = $permissionsByGroup->map(function ($perms, $group) {
            $fieldName = 'permissions_' . Str::slug($group);

            $options = $perms->mapWithKeys(fn ($p) => [
                (string) $p->id => $p->name . ($p->parent ? ' — needs: ' . $p->parent->name : ''),
            ])->toArray();

            return Section::make($group)
                ->icon(match ($group) {
                    'Tasks'     => 'heroicon-o-clipboard-document-list',
                    'Projects'  => 'heroicon-o-folder',
                    'Customers' => 'heroicon-o-building-office',
                    'Users'     => 'heroicon-o-users',
                    'Roles'     => 'heroicon-o-shield-check',
                    default     => 'heroicon-o-key',
                })
                ->collapsible()
                ->schema([
                    CheckboxList::make($fieldName)
                        ->label('')
                        ->options($options)
                        ->columns(2)
                        ->gridDirection('row')
                        ->helperText('Ancestor dependencies are auto-added on save.')
                        ->dehydrated(false),
                ]);
        })->values()->toArray();

        return $form->schema([
            Section::make('Role Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(64)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) =>
                            $set('slug', Str::slug($state ?? ''))
                        ),
                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(64)
                        ->helperText('Auto-generated. "super-admin" slug grants ALL permissions unconditionally.'),
                    ColorPicker::make('color')
                        ->required()
                        ->default('#4a90d9'),
                    Textarea::make('description')
                        ->columnSpanFull()
                        ->rows(2),
                ]),

            ...$permissionSections,
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('color')
                    ->label('')
                    ->formatStateUsing(fn ($state) => '')
                    ->html()
                    ->getStateUsing(fn (Role $record) =>
                        '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' . e($record->color) . '"></span>'
                    )
                    ->width(32),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->badge()
                    ->color('info'),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->badge()
                    ->color('success'),
                TextColumn::make('description')
                    ->limit(60)
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Role $record) {
                        if ($record->slug === 'super-admin') {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Cannot delete Super Admin role')
                                ->send();
                            $record->skipDelete = true;
                        }
                    })
                    ->using(function (Role $record) {
                        if (isset($record->skipDelete)) return;
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
