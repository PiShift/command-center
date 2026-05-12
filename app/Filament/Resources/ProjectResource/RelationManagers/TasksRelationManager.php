<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\KanbanColumn;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->columnSpanFull(),
            Textarea::make('description')->rows(3)->nullable()->columnSpanFull(),
            Select::make('type')
                ->options(['bug' => 'Bug', 'feature' => 'Feature', 'change' => 'Change'])
                ->default('feature'),
            Select::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                ->default('medium'),
            Select::make('status')
                ->options(fn () => KanbanColumn::orderBy('position')->pluck('name', 'slug'))
                ->default('backlog'),
            Select::make('assigned_to')
                ->label('Assignee')
                ->options(fn () => User::pluck('name', 'id')->toArray())
                ->searchable()
                ->nullable(),
            DatePicker::make('due_date')->nullable(),
            TextInput::make('estimated_hours')->numeric()->suffix('h')->nullable(),
            TagsInput::make('labels')->nullable()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->wrap()->weight('medium'),
                TextColumn::make('type')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'feature' => 'info',
                        'bug'     => 'danger',
                        'change'  => 'gray',
                        default   => 'gray',
                    }),
                TextColumn::make('priority')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'high'   => 'danger',
                        'medium' => 'warning',
                        'low'    => 'gray',
                        default  => 'gray',
                    }),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'backlog'     => 'gray',
                        'in-progress' => 'info',
                        'in-review'   => 'warning',
                        'done'        => 'success',
                        default       => 'gray',
                    }),
                TextColumn::make('assignee.name')->label('Assignee')->default('—'),
                TextColumn::make('due_date')->date()
                    ->color(fn ($record) => $record?->isOverdue() ? 'danger' : null),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([
                Action::make('move')
                    ->label('→ Next Status')
                    ->icon('heroicon-m-arrow-right-circle')
                    ->color('gray')
                    ->action(function (Task $record) {
                        $next = match ($record->status) {
                            'backlog'     => 'in-progress',
                            'in-progress' => 'in-review',
                            'in-review'   => 'done',
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
            ->filters([
                SelectFilter::make('status')->options(fn () => KanbanColumn::orderBy('position')->pluck('name', 'slug')->toArray()),
                SelectFilter::make('priority')->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']),
                SelectFilter::make('type')->options(['bug' => 'Bug', 'feature' => 'Feature', 'change' => 'Change']),
            ]);
    }
}
