<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('advance_status')
                ->label(fn () => match ($this->record->status) {
                    'backlog'     => '▶ Start Progress',
                    'in-progress' => '👁 Send to Review',
                    'in-review'   => '✓ Mark Done',
                    default       => '↩ Re-open',
                })
                ->color(fn () => match ($this->record->status) {
                    'backlog'     => 'info',
                    'in-progress' => 'warning',
                    'in-review'   => 'success',
                    default       => 'gray',
                })
                ->action(function () {
                    $next = match ($this->record->status) {
                        'backlog'     => 'in-progress',
                        'in-progress' => 'in-review',
                        'in-review'   => 'done',
                        default       => 'backlog',
                    };
                    $this->record->update([
                        'status'       => $next,
                        'completed_at' => $next === 'done' ? now() : null,
                    ]);
                    Notification::make()->title('Status updated to ' . $next)->success()->send();
                    $this->refreshFormData(['status', 'completed_at']);
                }),
            EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Task Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('title')->weight('bold')->columnSpan(3),
                    TextEntry::make('project.name')->label('Project'),
                    TextEntry::make('assignee.name')->label('Assignee')->default('Unassigned'),
                    TextEntry::make('status')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'backlog'     => 'gray',
                            'in-progress' => 'info',
                            'in-review'   => 'warning',
                            'done'        => 'success',
                            default       => 'gray',
                        }),
                    TextEntry::make('type')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'feature' => 'info',
                            'bug'     => 'danger',
                            'change'  => 'gray',
                            default   => 'gray',
                        }),
                    TextEntry::make('priority')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'high'   => 'danger',
                            'medium' => 'warning',
                            'low'    => 'gray',
                            default  => 'gray',
                        }),
                    TextEntry::make('source')->badge()->color('gray'),
                    TextEntry::make('due_date')
                        ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y') : 'No deadline')
                        ->color(fn ($record) => $record?->isOverdue() ? 'danger' : null),
                    TextEntry::make('estimated_hours')->suffix('h')->default('—'),
                    TextEntry::make('completed_at')
                        ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y H:i') : 'Not yet completed'),
                ]),
            Section::make('Description')
                ->collapsible()
                ->schema([
                    TextEntry::make('description')->prose()->default('No description.'),
                ]),
            Section::make('Original Input')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record) => filled($record?->original_input))
                ->schema([
                    TextEntry::make('original_input')->prose(),
                ]),
        ]);
    }
}
