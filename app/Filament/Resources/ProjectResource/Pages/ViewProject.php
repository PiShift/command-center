<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Project Overview')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->weight('bold')->columnSpan(2),
                    TextEntry::make('status')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'active'   => 'success',
                            'paused'   => 'warning',
                            'complete' => 'gray',
                            default    => 'gray',
                        }),
                    TextEntry::make('customer.name')->label('Customer')->default('—'),
                    TextEntry::make('health')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'on-track' => 'success',
                            'at-risk'  => 'warning',
                            'blocked'  => 'danger',
                            default    => 'gray',
                        }),
                    TextEntry::make('stack')->default('—'),
                    TextEntry::make('start_date')
                        ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y') : '—'),
                    TextEntry::make('deadline')
                        ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y') : '—')
                        ->color(fn ($record) => $record?->isOverdue() ? 'danger' : null),
                    TextEntry::make('budget')
                        ->formatStateUsing(fn ($state) => $state ? '$' . number_format($state, 2) : '—'),
                    TextEntry::make('github_repo')
                        ->label('GitHub')
                        ->url(fn ($record) => $record->github_repo ? 'https://github.com/' . $record->github_repo : null)
                        ->openUrlInNewTab()
                        ->default('—'),
                ]),
            Section::make('Description')
                ->collapsible()
                ->schema([
                    TextEntry::make('description')->prose()->default('No description.'),
                ]),
        ]);
    }

    public function getRelationManagers(): array
    {
        return [
            TasksRelationManager::class,
        ];
    }
}
