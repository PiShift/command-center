<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->columnSpanFull(),
            Textarea::make('description')->rows(3)->nullable()->columnSpanFull(),
            Select::make('status')
                ->options(['active' => 'Active', 'paused' => 'Paused', 'complete' => 'Complete'])
                ->default('active'),
            Select::make('health')
                ->options(['on-track' => 'On Track', 'at-risk' => 'At Risk', 'blocked' => 'Blocked'])
                ->default('on-track'),
            TextInput::make('stack')->nullable()->placeholder('Laravel + Vue'),
            TextInput::make('github_repo')->nullable()->placeholder('org/repo'),
            DatePicker::make('start_date')->nullable(),
            DatePicker::make('deadline')->nullable(),
            TextInput::make('budget')->numeric()->prefix('$')->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('tasks'))
            ->columns([
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active'   => 'success',
                        'paused'   => 'warning',
                        'complete' => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('health')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'on-track' => 'success',
                        'at-risk'  => 'warning',
                        'blocked'  => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('tasks_count')->label('Tasks')->alignCenter(),
                TextColumn::make('deadline')->date()->color(fn ($record) => $record?->isOverdue() ? 'danger' : null),
                TextColumn::make('stack')->toggleable()->limit(30),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['active' => 'Active', 'paused' => 'Paused', 'complete' => 'Complete']),
                SelectFilter::make('health')->options(['on-track' => 'On Track', 'at-risk' => 'At Risk', 'blocked' => 'Blocked']),
            ]);
    }
}
