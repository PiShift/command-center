<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Task::where('status', '!=', 'done')
            ->where(fn ($q) => $q->where('priority', 'high')
                ->orWhere(fn ($q2) => $q2->whereNotNull('due_date')->whereDate('due_date', '<', now())))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Task')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->required()->columnSpanFull(),
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->required(),
                    Select::make('assigned_to')
                        ->label('Assignee')
                        ->options(fn () => User::pluck('name', 'id')->toArray())
                        ->searchable()
                        ->nullable(),
                    Select::make('type')
                        ->options(['bug' => 'Bug', 'feature' => 'Feature', 'change' => 'Change'])
                        ->default('feature'),
                    Select::make('priority')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                        ->default('medium'),
                    Select::make('status')
                        ->options(fn () => KanbanColumn::orderBy('position')->pluck('name', 'slug'))
                        ->default('backlog'),
                    DatePicker::make('due_date')->nullable(),
                    TextInput::make('estimated_hours')->numeric()->suffix('h')->nullable(),
                    TagsInput::make('labels')->nullable()->columnSpanFull(),
                    Textarea::make('description')->nullable()->rows(4)->columnSpanFull(),
                ]),
            Section::make('Source')
                ->collapsible()
                ->collapsed()
                ->columns(2)
                ->schema([
                    Select::make('source')
                        ->options(['manual' => 'Manual', 'ai-chat' => 'AI Chat'])
                        ->default('manual'),
                    Textarea::make('original_input')
                        ->label('Original Input')
                        ->nullable()
                        ->rows(4)
                        ->columnSpanFull()
                        ->hidden(fn ($get) => $get('source') !== 'ai-chat'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    ->description(fn ($record) => $record->project?->name),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'feature' => 'info',
                        'bug'     => 'danger',
                        'change'  => 'gray',
                        default   => 'gray',
                    }),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'high'   => 'danger',
                        'medium' => 'warning',
                        'low'    => 'gray',
                        default  => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'backlog'     => 'gray',
                        'in-progress' => 'info',
                        'in-review'   => 'warning',
                        'done'        => 'success',
                        default       => 'gray',
                    }),
                TextColumn::make('assignee.name')->label('Assignee')->default('—')->toggleable(),
                TextColumn::make('due_date')
                    ->date()
                    ->color(fn ($record) => $record?->isOverdue() ? 'danger' : null)
                    ->toggleable(),
                TextColumn::make('estimated_hours')->suffix('h')->default('—')->toggleable(),
                TextColumn::make('source')->badge()->color('gray')->toggleable(),
                TextColumn::make('updated_at')->since()->toggleable()->label('Updated'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => KanbanColumn::orderBy('position')->pluck('name', 'slug')->toArray()),
                SelectFilter::make('type')
                    ->options(['bug' => 'Bug', 'feature' => 'Feature', 'change' => 'Change']),
                SelectFilter::make('priority')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::pluck('name', 'id')->toArray()),
                SelectFilter::make('assigned_to')
                    ->label('Assignee')
                    ->options(fn () => User::pluck('name', 'id')->toArray()),
                Filter::make('overdue')
                    ->label('Overdue only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now())
                        ->where('status', '!=', 'done')),
                Filter::make('high_priority')
                    ->label('High priority only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('priority', 'high')),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('advance')
                    ->label(fn (Task $record) => match ($record->status) {
                        'backlog'     => 'Start',
                        'in-progress' => 'Done',
                        default       => 'Re-open',
                    })
                    ->icon(fn (Task $record) => match ($record->status) {
                        'backlog'     => 'heroicon-o-play',
                        'in-progress' => 'heroicon-o-check',
                        default       => 'heroicon-o-arrow-uturn-left',
                    })
                    ->color(fn (Task $record) => match ($record->status) {
                        'backlog'     => 'info',
                        'in-progress' => 'success',
                        default       => 'gray',
                    })
                    ->action(function (Task $record) {
                        $next = match ($record->status) {
                            'backlog'     => 'in-progress',
                            'in-progress' => 'done',
                            default       => 'backlog',
                        };
                        $record->update([
                            'status'       => $next,
                            'completed_at' => $next === 'done' ? now() : null,
                        ]);
                    }),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_start')
                        ->label('Move to In Progress')
                        ->icon('heroicon-o-play')
                        ->action(fn ($records) => $records->each->update(['status' => 'in-progress']))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('bulk_review')
                        ->label('Move to In Review')
                        ->icon('heroicon-o-eye')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['status' => 'in-review']))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('bulk_done')
                        ->label('Mark as Done')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'done', 'completed_at' => now()]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('Assign to...')
                        ->icon('heroicon-o-user')
                        ->form([
                            Select::make('assigned_to')
                                ->label('Assignee')
                                ->options(fn () => User::pluck('name', 'id')->toArray())
                                ->required(),
                        ])
                        ->action(fn ($records, array $data) => $records->each->update(['assigned_to' => $data['assigned_to']])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'kanban' => Pages\KanbanBoard::route('/kanban'),
            'view'   => Pages\ViewTask::route('/{record}'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
