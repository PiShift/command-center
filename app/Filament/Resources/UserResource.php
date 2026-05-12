<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool   { return auth()->user()?->hasPermission('users.view') ?? false; }
    public static function canViewAny(): bool   { return auth()->user()?->hasPermission('users.view') ?? false; }
    public static function canCreate(): bool    { return auth()->user()?->hasPermission('users.create') ?? false; }
    public static function canEdit($r): bool    { return auth()->user()?->hasPermission('users.edit') ?? false; }
    public static function canDelete($r): bool  { return auth()->user()?->hasPermission('users.delete') ?? false; }
    public static function canView($r): bool    { return auth()->user()?->hasPermission('users.view') ?? false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(128)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $words = explode(' ', trim($state));
                                $initials = strtoupper(
                                    count($words) >= 2
                                        ? $words[0][0] . end($words)[0]
                                        : substr($words[0], 0, 2)
                                );
                                $set('initials', $initials);
                            }
                        }),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(128),
                    TextInput::make('initials')
                        ->maxLength(3)
                        ->helperText('Auto-generated from name. Max 3 characters.'),
                    ColorPicker::make('color')
                        ->default('#D97757'),
                ]),

            Section::make('Access')
                ->columns(2)
                ->schema([
                    Select::make('role_id')
                        ->label('Role')
                        ->relationship('roleModel', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Determines what this user can see and do.'),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('Leave blank to keep current password when editing.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('avatar')
                    ->label('')
                    ->html()
                    ->getStateUsing(fn (User $record) =>
                        '<span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:' . e($record->color) . ';color:#fff;font-size:11px;font-weight:700;">'
                        . e($record->initials ?? strtoupper(substr($record->name, 0, 2)))
                        . '</span>'
                    )
                    ->width(48),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->searchable()
                    ->color('gray'),
                TextColumn::make('roleModel.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (User $record) => $record->roleModel?->color ? 'gray' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ?? '—'),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Role')
                    ->relationship('roleModel', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
