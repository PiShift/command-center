<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager;
use App\Models\Customer;
use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
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
            Section::make('Project Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->columnSpanFull(),
                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->nullable(),
                    TextInput::make('stack')
                        ->nullable()
                        ->placeholder('Laravel + Vue, Next.js + Supabase'),
                    Select::make('status')
                        ->options([
                            'active'   => 'Active',
                            'paused'   => 'Paused',
                            'complete' => 'Complete',
                        ])
                        ->default('active'),
                    Select::make('health')
                        ->options([
                            'on-track' => 'On Track',
                            'at-risk'  => 'At Risk',
                            'blocked'  => 'Blocked',
                        ])
                        ->default('on-track'),
                    TextInput::make('github_repo')
                        ->nullable()
                        ->placeholder('org/repo-name')
                        ->hint('e.g. pishift/acme-platform'),
                    TextInput::make('budget')
                        ->numeric()
                        ->prefix('$')
                        ->nullable(),
                ]),
            Section::make('Timeline')
                ->columns(2)
                ->collapsible()
                ->schema([
                    DatePicker::make('start_date')->nullable(),
                    DatePicker::make('deadline')->nullable(),
                ]),
            Section::make('Description')
                ->collapsible()
                ->schema([
                    Textarea::make('description')->nullable()->rows(4)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn ($q) => $q->where('status', '!=', 'done'),
            ]))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->customer?->name),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active'   => 'success',
                        'paused'   => 'warning',
                        'complete' => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('health')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'on-track' => 'success',
                        'at-risk'  => 'warning',
                        'blocked'  => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('stack')->limit(25)->toggleable(),
                TextColumn::make('open_tasks_count')
                    ->label('Open Tasks')
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),
                TextColumn::make('deadline')
                    ->date()
                    ->color(fn ($record) => $record?->isOverdue() ? 'danger' : null)
                    ->toggleable(),
                TextColumn::make('budget')->money('USD')->toggleable(),
                TextColumn::make('github_repo')->copyable()->toggleable(),
                TextColumn::make('updated_at')->since()->toggleable()->label('Updated'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['active' => 'Active', 'paused' => 'Paused', 'complete' => 'Complete']),
                SelectFilter::make('health')
                    ->options(['on-track' => 'On Track', 'at-risk' => 'At Risk', 'blocked' => 'Blocked']),
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('toggle_health')
                    ->label('Flag')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->action(function (Project $record) {
                        $next = match ($record->health) {
                            'on-track' => 'at-risk',
                            'at-risk'  => 'blocked',
                            default    => 'on-track',
                        };
                        $record->update(['health' => $next]);
                    })
                    ->tooltip(fn (Project $record) => 'Health: ' . $record->health . ' → cycle'),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_complete')
                        ->label('Mark Complete')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['status' => 'complete']))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('mark_paused')
                        ->label('Pause')
                        ->icon('heroicon-o-pause-circle')
                        ->action(fn ($records) => $records->each->update(['status' => 'paused']))
                        ->requiresConfirmation(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view'   => Pages\ViewProject::route('/{record}'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
