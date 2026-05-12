<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\ProjectsRelationManager;
use App\Models\Customer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'People';

    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool   { return auth()->user()?->hasPermission('customers.view') ?? false; }
    public static function canViewAny(): bool   { return auth()->user()?->hasPermission('customers.view') ?? false; }
    public static function canCreate(): bool    { return auth()->user()?->hasPermission('customers.create') ?? false; }
    public static function canEdit($r): bool    { return auth()->user()?->hasPermission('customers.edit') ?? false; }
    public static function canDelete($r): bool  { return auth()->user()?->hasPermission('customers.delete') ?? false; }
    public static function canView($r): bool    { return auth()->user()?->hasPermission('customers.view') ?? false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('company')->nullable(),
                    TextInput::make('email')->email()->nullable(),
                    TextInput::make('phone')->tel()->nullable(),
                    TextInput::make('website')->url()->nullable()->placeholder('https://'),
                    Select::make('status')
                        ->options([
                            'prospect' => 'Prospect',
                            'active'   => 'Active',
                            'churned'  => 'Churned',
                        ])
                        ->default('prospect')
                        ->required(),
                    TextInput::make('industry')->nullable()->placeholder('SaaS, E-commerce, Agency…'),
                ]),
            Section::make('Notes')
                ->collapsible()
                ->schema([
                    Textarea::make('notes')->nullable()->rows(4)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('projects'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->company),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active'   => 'success',
                        'prospect' => 'warning',
                        'churned'  => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('industry')->default('—')->toggleable(),
                TextColumn::make('email')->copyable()->default('—')->toggleable(),
                TextColumn::make('phone')->copyable()->default('—')->toggleable(),
                TextColumn::make('projects_count')
                    ->label('Projects')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                TextColumn::make('created_at')->since()->toggleable()->label('Added'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'prospect' => 'Prospect',
                        'active'   => 'Active',
                        'churned'  => 'Churned',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_active')
                        ->label('Mark as Active')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['status' => 'active']))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('mark_churned')
                        ->label('Mark as Churned')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['status' => 'churned']))
                        ->requiresConfirmation(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            ProjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view'   => Pages\ViewCustomer::route('/{record}'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
