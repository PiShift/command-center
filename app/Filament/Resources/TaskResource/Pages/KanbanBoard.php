<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class KanbanBoard extends Page
{
    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.task-resource.kanban-board';

    protected static ?string $title = 'Kanban Board';

    protected static ?string $navigationLabel = 'Kanban';

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    #[Url]
    public ?int $projectFilter = null;

    #[Url]
    public string $priorityFilter = '';

    public function mount(): void
    {
        session(['tasks_view_preference' => 'kanban']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_task')
                ->label('New Task')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Quick Create Task')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Create Task')
                ->form([
                    TextInput::make('title')
                        ->required()
                        ->autofocus()
                        ->placeholder('What needs to be done?')
                        ->columnSpanFull(),
                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('status')
                        ->options(fn () => KanbanColumn::orderBy('position')->pluck('name', 'slug'))
                        ->default('backlog')
                        ->required(),
                    Select::make('priority')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                        ->default('medium')
                        ->required(),
                    Select::make('type')
                        ->options(['feature' => 'Feature', 'bug' => 'Bug', 'change' => 'Change'])
                        ->default('feature')
                        ->required(),
                    Select::make('assigned_to')
                        ->label('Assignee')
                        ->options(fn () => User::pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    DatePicker::make('due_date')->nullable(),
                    Textarea::make('description')->nullable()->rows(2)->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    Task::create($data);
                    Notification::make()->title('Task created')->success()->send();
                    unset($this->columns);
                }),

            Action::make('add_column')
                ->label('Add Column')
                ->icon('heroicon-o-plus-circle')
                ->color('gray')
                ->modalHeading('New Column')
                ->modalWidth('sm')
                ->modalSubmitActionLabel('Add Column')
                ->form([
                    TextInput::make('name')
                        ->label('Column name')
                        ->required()
                        ->placeholder('e.g. QA Testing'),
                    Select::make('color')
                        ->label('Color')
                        ->options(KanbanColumn::colorOptions())
                        ->default('slate')
                        ->required(),
                    TextInput::make('icon')
                        ->label('Icon (emoji)')
                        ->default('📌')
                        ->maxLength(4),
                ])
                ->action(function (array $data) {
                    $nextPosition = KanbanColumn::max('position') + 1;
                    KanbanColumn::create([
                        'name'     => $data['name'],
                        'slug'     => Str::slug($data['name'], '-'),
                        'color'    => $data['color'],
                        'icon'     => $data['icon'] ?? '📌',
                        'position' => $nextPosition,
                    ]);
                    Notification::make()->title('Column "' . $data['name'] . '" added')->success()->send();
                    unset($this->columns);
                }),

            Action::make('list_view')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->action(function () {
                    session(['tasks_view_preference' => 'list']);
                    $this->redirect(TaskResource::getUrl('index'));
                }),
        ];
    }

    // ── Computed properties ────────────────────────────────────────────────────

    #[Computed]
    public function projects(): \Illuminate\Database\Eloquent\Collection
    {
        return Project::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function columns(): array
    {
        $priorityOrder = "CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END";

        return KanbanColumn::orderBy('position')
            ->get()
            ->map(function (KanbanColumn $col) use ($priorityOrder) {
                $query = Task::with(['project:id,name', 'assignee:id,name'])
                    ->where('status', $col->slug)
                    ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
                    ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter));

                $tasks = $col->slug === 'done'
                    ? $query->orderBy('completed_at', 'desc')->limit(25)->get()
                    : $query->orderByRaw($priorityOrder)->orderBy('due_date')->get();

                return [
                    'id'           => $col->id,
                    'key'          => $col->slug,
                    'label'        => $col->name,
                    'color'        => $col->color,
                    'icon'         => $col->icon,
                    'is_protected' => $col->is_protected,
                    'tasks'        => $tasks,
                ];
            })
            ->toArray();
    }

    // ── User actions called from JS / blade ────────────────────────────────────

    /** Called by SortableJS when a card crosses columns */
    public function moveTask(int $taskId, string $newStatus): void
    {
        $allowed = KanbanColumn::pluck('slug')->all();

        if (! in_array($newStatus, $allowed)) {
            return;
        }

        Task::findOrFail($taskId)->update([
            'status'       => $newStatus,
            'completed_at' => $newStatus === 'done' ? now() : null,
        ]);

        unset($this->columns);
    }

    /** Called by SortableJS when columns themselves are reordered */
    public function reorderColumns(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            KanbanColumn::where('id', (int) $id)->update(['position' => $position]);
        }

        unset($this->columns);
    }

    /** Rename a column inline */
    public function renameColumn(int $columnId, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }
        KanbanColumn::findOrFail($columnId)->update(['name' => $name]);
        unset($this->columns);
    }

    /** Delete a column — reassigns its tasks to 'backlog' */
    public function deleteColumn(int $columnId): void
    {
        $col = KanbanColumn::findOrFail($columnId);

        if ($col->is_protected) {
            Notification::make()->title('This column cannot be deleted')->danger()->send();
            return;
        }

        Task::where('status', $col->slug)->update(['status' => 'backlog']);
        $col->delete();

        // Re-sequence positions
        KanbanColumn::orderBy('position')
            ->get()
            ->each(fn ($c, $idx) => $c->update(['position' => $idx]));

                Notification::make()->title('Column deleted — tasks moved to Backlog')->success()->send();
        unset($this->columns);
    }
}
