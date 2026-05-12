<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\RelationManagers\ProjectsRelationManager;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Contact Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->weight('bold'),
                    TextEntry::make('company')->default('—'),
                    TextEntry::make('status')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'active'   => 'success',
                            'prospect' => 'warning',
                            'churned'  => 'danger',
                            default    => 'gray',
                        }),
                    TextEntry::make('email')->copyable()->default('—'),
                    TextEntry::make('phone')->copyable()->default('—'),
                    TextEntry::make('website')->url(fn ($record) => $record->website)->openUrlInNewTab()->default('—'),
                    TextEntry::make('industry')->default('—'),
                ]),
            Section::make('Notes')
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')->prose()->markdown()->default('No notes yet.'),
                ]),
        ]);
    }

    public function getRelationManagers(): array
    {
        return [
            ProjectsRelationManager::class,
        ];
    }
}
