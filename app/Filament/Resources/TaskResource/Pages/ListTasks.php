<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    public function mount(): void
    {
        parent::mount();

        if (session('tasks_view_preference') === 'kanban') {
            $this->redirect(TaskResource::getUrl('kanban'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Kanban Board')
                ->icon('heroicon-o-view-columns')
                ->url(TaskResource::getUrl('kanban'))
                ->color('gray')
                ->action(fn () => session(['tasks_view_preference' => 'kanban'])),
            CreateAction::make(),
        ];
    }
}
