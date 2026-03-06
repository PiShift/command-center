<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Customer;
use App\Models\Project;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('customer_id')
                ->label('Customer')
                ->relationship('customer', 'name')
                ->searchable()
                ->nullable(),
            TextInput::make('name')
                ->required(),
            Textarea::make('description')
                ->nullable()
                ->rows(3),
            TextInput::make('github_repo')
                ->nullable()
                ->placeholder('org/repo-name')
                ->hint('e.g. pishift/acme-platform'),
            TextInput::make('stack')
                ->nullable()
                ->placeholder('e.g. Laravel + Vue, Next.js + Supabase'),
            Select::make('status')
                ->options([
                    'active'   => 'Active',
                    'paused'   => 'Paused',
                    'complete' => 'Complete',
                ])
                ->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('tasks'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Customer'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'paused'   => 'warning',
                        'complete' => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('stack'),
                TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->counts('tasks'),
                TextColumn::make('github_repo')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'   => 'Active',
                        'paused'   => 'Paused',
                        'complete' => 'Complete',
                    ]),
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
