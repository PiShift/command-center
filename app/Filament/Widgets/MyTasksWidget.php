<?php

namespace App\Filament\Widgets;

use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class MyTasksWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.my-tasks-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /** Bound to the project filter <select> via wire:model.live */
    public string $projectFilter = '';

    public function updatedProjectFilter(): void
    {
        unset($this->tasks, $this->taskCount);
    }

    #[\Livewire\Attributes\Computed]
    public function projects(): Collection
    {
        return Project::orderBy('name')->get(['id', 'name']);
    }

    #[\Livewire\Attributes\Computed]
    public function statusOptions(): Collection
    {
        return KanbanColumn::orderBy('position')->pluck('name', 'slug');
    }

    #[\Livewire\Attributes\Computed]
    public function tasks(): Collection
    {
        return Task::query()
            ->with(['project:id,name', 'assignee:id,name'])
            ->where('status', '!=', 'done')
            ->when($this->projectFilter !== '', fn ($q) => $q->where('project_id', (int) $this->projectFilter))
            ->orderByRaw("CASE WHEN priority = 'high' THEN 0 WHEN priority = 'medium' THEN 1 ELSE 2 END")
            ->orderBy('due_date')
            ->limit(30)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function taskCount(): int
    {
        return Task::query()
            ->where('status', '!=', 'done')
            ->when($this->projectFilter !== '', fn ($q) => $q->where('project_id', (int) $this->projectFilter))
            ->count();
    }

    public function markDone(int $taskId): void
    {
        Task::findOrFail($taskId)->update([
            'status'       => 'done',
            'completed_at' => now(),
        ]);

        unset($this->tasks, $this->taskCount);
    }

    public function quickAddAction(): Action
    {
        return Action::make('quickAdd')
            ->label('Quick Add')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading('Add New Task')
            ->modalDescription('Create a task and assign it to a project.')
            ->modalWidth('lg')
            ->form([
                Select::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->placeholder('Select a project'),

                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('What needs to be done?'),

                Select::make('status')
                    ->label('Status')
                    ->options(fn () => KanbanColumn::orderBy('position')->pluck('name', 'slug'))
                    ->default('in-progress')
                    ->required(),

                Select::make('priority')
                    ->label('Priority')
                    ->options([
                        'low'    => 'Low',
                        'medium' => 'Medium',
                        'high'   => 'High',
                    ])
                    ->default('medium')
                    ->required(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('Brief description… (optional)'),
            ])
            ->action(function (array $data): void {
                Task::create([
                    'title'       => $data['title'],
                    'description' => $data['description'] ?? null,
                    'project_id'  => $data['project_id'],
                    'status'      => $data['status'],
                    'priority'    => $data['priority'],
                    'assigned_to' => auth()->id(),
                ]);

                unset($this->tasks, $this->taskCount);

                Notification::make()
                    ->title('Task created successfully')
                    ->success()
                    ->send();
            });
    }

    public function viewTaskAction(): Action
    {
        return Action::make('viewTask')
            ->modalHeading(fn (array $arguments): string => Task::find($arguments['taskId'] ?? 0)?->title ?? 'Task Details')
            ->modalWidth('2xl')
            ->modalContent(function (array $arguments) {
                $task = Task::with(['project', 'assignee'])->find($arguments['taskId'] ?? 0);

                return view('filament.widgets.task-detail-modal', compact('task'));
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->action(fn () => null);
    }
}
