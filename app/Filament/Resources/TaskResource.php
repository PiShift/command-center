<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Project;
use App\Models\Task;
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

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?int $navigationSort = 2;

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('project_id')
                ->label('Project')
                ->relationship('project', 'name')
                ->searchable()
                ->required(),
            TextInput::make('title')
                ->required(),
            Textarea::make('description')
                ->nullable()
                ->rows(4),
            Select::make('type')
                ->options([
                    'bug'     => 'Bug',
                    'feature' => 'Feature',
                    'change'  => 'Change',
                ])
                ->default('feature'),
            Select::make('priority')
                ->options([
                    'low'    => 'Low',
                    'medium' => 'Medium',
                    'high'   => 'High',
                ])
                ->default('medium'),
            Select::make('status')
                ->options([
                    'backlog'     => 'Backlog',
                    'in-progress' => 'In Progress',
                    'done'        => 'Done',
                ])
                ->default('backlog'),
            Select::make('source')
                ->options([
                    'manual'  => 'Manual',
                    'ai-chat' => 'AI Chat',
                ])
                ->default('manual')
                ->hiddenOn('create'),
            Textarea::make('original_input')
                ->label('Original Input (raw customer feedback)')
                ->nullable()
                ->rows(4)
                ->hidden(fn ($get) => $get('source') !== 'ai-chat'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('project.name')
                    ->label('Project'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'feature' => 'info',
                        'bug'     => 'danger',
                        'change'  => 'gray',
                        default   => 'gray',
                    }),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high'   => 'danger',
                        'medium' => 'warning',
                        'low'    => 'gray',
                        default  => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'backlog'     => 'gray',
                        'in-progress' => 'info',
                        'done'        => 'success',
                        default       => 'gray',
                    }),
                TextColumn::make('source')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'backlog'     => 'Backlog',
                        'in-progress' => 'In Progress',
                        'done'        => 'Done',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        'bug'     => 'Bug',
                        'feature' => 'Feature',
                        'change'  => 'Change',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'low'    => 'Low',
                        'medium' => 'Medium',
                        'high'   => 'High',
                    ]),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::pluck('name', 'id')->toArray()),
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
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
