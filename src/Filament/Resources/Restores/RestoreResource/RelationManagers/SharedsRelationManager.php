<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\RelationManagers;

use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectColumnFromField;
use Callcocam\DbRestore\Forms\Components\SelectColumnToField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Models\Column;
use Callcocam\DbRestore\Models\Shared;
use Callcocam\DbRestore\Models\SharedItem;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Callcocam\DbRestore\Traits\WithSections;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SharedsRelationManager extends RelationManager
{
    use WithColumns, WithFormSchemas, HasTraduction, WithSections;

    protected static string $relationship = 'shareds';

    public function form(Form $form): Form
    {
        $ownerRecord = $this->ownerRecord;
        return $form
            ->schema([
                SelectColumnField::make('shared_id')
                    ->relationship('shared', 'name')
                    ->columnSpanFull()
                    ->manageOptionForm(function () use ($ownerRecord) {
                        return [
                            Group::make([
                                TextInputField::make('name')
                                    ->columnSpan([
                                        'md' => 4
                                    ])
                                    ->required(),
                                SelectTableFromField::makeTable('table_from', $ownerRecord)
                                    ->columnSpan([
                                        'md' => 4
                                    ])
                                    ->required(),
                                SelectTableToField::makeTable('table_to', $ownerRecord)
                                    ->columnSpan([
                                        'md' => 4
                                    ])
                                    ->required(),
                                SelectColumnToField::makeFromOptions('column_from', $ownerRecord, 'table_from')
                                    ->columnSpan([
                                        'md' => 6
                                    ])
                                    ->required(),
                                SelectColumnToField::makeToOptions('column_to', $ownerRecord, 'table_to')
                                    ->columnSpan([
                                        'md' => 6
                                    ])
                                    ->required(),
                                Forms\Components\Section::make()
                                    ->visible(fn (Shared | null $record = null) => $record)
                                    ->schema(function ($record = null) use ($ownerRecord) {
                                        if (!$record) {
                                            return [];
                                        }
                                        return [
                                            Forms\Components\Section::make($this->getTraduction('columns', 'restore', 'form',  'label'))
                                                ->description($this->getTraduction('columns', 'restore', 'form', 'description'))
                                                ->visible($record->table_from && $record->table_to)
                                                ->collapsed()
                                                ->schema(function () use ($ownerRecord, $record) {

                                                    return  [
                                                        Forms\Components\Repeater::make('columns')
                                                            ->relationship('columns')
                                                            ->hiddenLabel()
                                                            ->schema(function () use ($ownerRecord, $record) {
                                                                $cloneRecord = clone $record;
                                                                $cloneRecord->connectionTo = $ownerRecord->connectionTo;
                                                                $cloneRecord->connectionFrom = $ownerRecord->connectionFrom;
                                                                // if (!$record->columns->count()) {
                                                                //     $this->getColumnOptions($cloneRecord, $ownerRecord->connectionFrom, $ownerRecord->connectionTo);
                                                                // }
                                                                return $this->getColumnsSchemaForm($cloneRecord);
                                                            })
                                                            ->columns(12)
                                                            ->columnSpanFull()
                                                    ];
                                                }),
                                            $this->getSectionFiltersSchema(
                                                record: $ownerRecord,
                                                connection: $ownerRecord->connectionFrom,
                                                tableTo: $record->table_from,
                                                connectionTo: $ownerRecord->connectionTo
                                            )->visible($ownerRecord->table_from),
                                            $this->getSectionOrderingsSchema($ownerRecord)->visible($ownerRecord->table_from),
                                        ];
                                    }),
                            ])->columns(12)->columnSpanFull(),
                        ];
                    })
                    ->required(),
                Group::make(function (SharedItem $SharedItem) use ($ownerRecord) {
                    $shared = $SharedItem->shared;
                    if (!$shared) {
                        return [];
                    }
                    $cloneRecord = clone $shared;
                    $cloneRecord->connectionTo = $ownerRecord->connectionTo;
                    $cloneRecord->connectionFrom = $ownerRecord->connectionFrom;
                    return [
                        SelectColumnField::make('restore_model_id')
                            ->relationship('restoreMomdel', 'name')
                            ->columnSpan([
                                'md' => 6
                            ])
                            ->required(),
                        SelectColumnFromField::makeColumn('morph_column_type', $cloneRecord)
                            ->columnSpan([
                                'md' => 3
                            ])
                            ->required(),
                        SelectColumnFromField::makeColumn('morph_column_id', $cloneRecord)
                            ->columnSpan([
                                'md' => 3
                            ])
                            ->required(),
                    ];
                })->columns(12)->columnSpanFull(),
            ])->columns(12);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('shared_id')
            ->columns([
                Tables\Columns\TextColumn::make('shared.name')
                    ->label($this->getTraductionTableLabel('shared_id')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function getsearchDefaultValueSchemaFormAction($record)
    {

        return  null;
    }
}
